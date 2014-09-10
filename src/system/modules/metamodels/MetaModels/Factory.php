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

namespace MetaModels;

/**
 * This is the MetaModel factory interface.
 * To create a MetaModel instance, either call @link{MetaModelFactory::byId()} or @link{MetaModelFactory::byTableName()}
 *
 * @package    MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class Factory implements IFactory
{
    /**
     * All MetaModel instances.
     *
     * Association: id => object
     *
     * @var array
     */
    protected static $arrInstances = array();

    /**
     * All MetaModel instances.
     *
     * Association: tableName => object
     *
     * @var array
     */
    protected static $arrInstancesByTable = array();

    /**
     * The table names.
     *
     * @var array
     */
    protected static $tableNames = null;

    /**
     * Returns the proper user object for the current context.
     *
     * @return \BackendUser|\FrontendUser|null The BackendUser when TL_MODE == 'BE',
     *                                         the FrontendUser when TL_MODE == 'FE'
     *                                         or null otherwise
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
     * This initializes the Contao Singleton object stack as it must be.
     *
     * When using singletons within the config.php file of an Extension.
     *
     * @return void
     */
    protected static function initializeContaoObjectStack()
    {
        // All of these getInstance calls are necessary to keep the instance stack intact
        // and therefore prevent an Exception in unknown on line 0.
        // Hopefully this will get fixed with Contao Reloaded or Contao 3.
        \Config::getInstance();
        \Environment::getInstance();
        \Input::getInstance();

        // Request token became available in 2.11.
        if (version_compare(VERSION, '2.11', '>=')) {
            \RequestToken::getInstance();
        }

        self::getUser();

        \Database::getInstance();
    }

    /**
     * Determines the correct factory from a metamodel table name.
     *
     * @param string $strTableName The table name of the metamodel for which the factory class shall be fetched for.
     *
     * @return string The factory class name which handles instantiation of the MetaModel or NULL if no class could
     *                be found.
     */
    protected static function getModelFactory($strTableName)
    {
        if (isset($GLOBALS['METAMODELS']['factories'][$strTableName])) {
            return $GLOBALS['METAMODELS']['factories'][$strTableName];
        }

        return null;
    }

    /**
     * Create a MetaModel instance with the given information.
     *
     * @param array $arrData The meta information for the MetaModel.
     *
     * @return \MetaModels\IMetaModel the meta model
     */
    protected static function createInstance($arrData)
    {
        $objMetaModel = null;
        if ($arrData) {
            // NOTE: we allow other devs to override the factory via a lookup table. This way
            // another (sub)class can be defined to create the instances.
            // reference is via tableName => classname.
            $strFactoryClass = self::getModelFactory($arrData['tableName']);
            if ($strFactoryClass) {
                $objMetaModel = call_user_func_array(array($strFactoryClass, 'createInstance'), array($arrData));
            } else {
                $objMetaModel = new MetaModel($arrData);
            }
            self::$arrInstances[$arrData['id']] =
            self::$arrInstancesByTable[$arrData['tableName']] =
                $objMetaModel;
        }
        return $objMetaModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function byId($intId)
    {
        if (array_key_exists($intId, self::$arrInstances)) {
            return self::$arrInstances[$intId];
        }
        $objData = \Database::getInstance()->prepare('SELECT * FROM tl_metamodel WHERE id=?')
            ->limit(1)
            ->execute($intId);
        return ($objData->numRows)?self::createInstance($objData->row()):null;
    }

    /**
     * {@inheritdoc}
     */
    public static function byTableName($strTablename)
    {
        if (array_key_exists($strTablename, self::$arrInstancesByTable)) {
            return self::$arrInstancesByTable[$strTablename];
        }
        $objData = \Database::getInstance()->prepare('SELECT * FROM tl_metamodel WHERE tableName=?')
            ->limit(1)
            ->execute($strTablename);
        return ($objData->numRows)?self::createInstance($objData->row()):null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAllTables()
    {
        if (self::$tableNames !== null) {
            return self::$tableNames;
        }

        self::initializeContaoObjectStack();

        $objDB = \Database::getInstance();
        if ($objDB) {
            if (!$objDB->tableExists('tl_metamodel')) {
                // I can't work without a properly installed database.
                return array();
            }

            self::$tableNames = $objDB->execute('SELECT * FROM tl_metamodel')
                ->fetchEach('tableName');

            return self::$tableNames;
        }

        return array();
    }
}
