<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\BackendIntegration;

use ContaoCommunityAlliance\DcGeneral\Event\EventPropagator;
use MetaModels\BackendIntegration\Events\BackendIntegrationEvent;
use MetaModels\Dca\MetaModelDcaBuilder;

/**
 * This class is the abstract base class used in the backend to build the menu.
 * See the concrete implementation in the ContaoX folders (depending on Contao Core version).
 *
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class Boot
{
    /**
     * Returns the proper user object for the current context.
     *
     * Returns the BackendUser when TL_MODE == 'BE', the FrontendUser when TL_MODE == 'FE' or null otherwise.
     *
     * @return \BackendUser|\FrontendUser|null
     */
    protected static function getUser()
    {
        if (TL_MODE == 'BE') {
            return \BackendUser::getInstance();
        } elseif (TL_MODE == 'FE') {
            return \FrontendUser::getInstance();
        }
        return null;
    }

    /**
     * This initializes the Contao Singleton object stack in the correct order.
     *
     * Needed when using singletons within the config.php file of an Extension.
     *
     * @return bool
     */
    protected static function initializeContaoObjectStack()
    {
        if (!file_exists(TL_ROOT . '/system/config/localconfig.php')) {
            return false;
        }

        // All of these getInstance calls are necessary to keep the instance stack intact
        // and therefore prevent an Exception in unknown on line 0.
        // Hopefully this will get fixed with Contao Reloaded or Contao 3.3.
        require_once(TL_ROOT . '/system/config/localconfig.php');
        \Config::getInstance();
        \Environment::getInstance();
        \Input::getInstance();
        self::getUser();

        \Database::getInstance();

        return true;
    }

    /**
     * Check if the database has been correctly configured.
     *
     * @return bool
     */
    protected static function isDBInitialized()
    {
        // When coming from install.php or somewhere else when localconfig.php
        // has not yet completely been initialized, we will run into an exception here.
        try {
            $objDB = \Database::getInstance();
            return $objDB
                && $objDB->tableExists('tl_metamodel', null)
                && $objDB->tableExists('tl_metamodel_dcasetting_condition', null);
        } catch (\Exception $e) {
            // Swallow the exceptions and return false below.
        }

        return false;
    }

    /**
     * Authenticate the BackendUser.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected static function authenticateBackendUser()
    {
        $objUser = self::getUser();
        // Work around as the TL_PATH constant is set after this routine has been run.
        // If this is not in place, BackendUser::authenticate() will redirect us to contao/index.php.
        // If no user is properly logged in (note the missing slash in the middle right after .tld).
        // We also have to fix up the "script" parameter, as this one will otherwise try to redirect from
        // "contao/index.php" to "/contao/index.php" therefore creating an infinite redirect loop.
        $env = \Environment::getInstance();

        // Issue #66 - contao/install.php is not working anymore. Thanks to Stefan Lindecke (@lindesbs).
        if (strpos($env->request, 'install.php') !== false) {
            return;
        }

        // Fix issue #397 - the security patch rendered our redirect method non working (websitePath can now be null).
        $path        = constant('TL_PATH') ?: $GLOBALS['TL_CONFIG']['websitePath'];
        $env->base   = $env->url . $path . '/';
        $env->script = preg_replace(
            '/^' . preg_quote($path, '/') . '\/?/i',
            '',
            $env->scriptName
        );

        // Bugfix: If the user is not authenticated, contao will redirect to contao/index.php
        // But in this moment the TL_PATH is not defined, so the $this->Environment->request
        // generate a url without replacing the basepath(TL_PATH) with an empty string.
        if (!defined('TL_PATH')) {
            define('TL_PATH', $path);
        }

        $objUser->authenticate();
        // Restore initial settings.
        $env->base   = null;
        $env->script = null;
    }

    /**
     * Perform the backend module booting.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public static function perform()
    {
        // Do not execute anything if we are on the index page because no User is logged in.
        if (strpos(\Environment::getInstance()->script, 'contao/index.php') !== false) {
            return;
        }

        if (!(self::initializeContaoObjectStack() && self::isDBInitialized())) {
            return;
        }

        // If no backend user authenticated, we will get redirected.
        self::authenticateBackendUser();

        $propagator = new EventPropagator($GLOBALS['container']['event-dispatcher']);

        $propagator->propagate(
            BackendIntegrationEvent::NAME,
            new BackendIntegrationEvent()
        );

        MetaModelDcaBuilder::getInstance()->injectBackendMenu();
        MetaModelDcaBuilder::getInstance()->injectIntoBackendModules();
    }
}
