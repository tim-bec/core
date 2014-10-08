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
 * @author     Christian de la Haye <service@delahaye.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Dca;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Backend\AddToUrlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\LoadDataContainerEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Image\GenerateHtmlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Image\ResizeImageEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LoadLanguageFileEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\IdSerializer;
use MetaModels\BackendIntegration\InputScreen\IInputScreen;
use MetaModels\BackendIntegration\ViewCombinations;
use MetaModels\Factory;
use MetaModels\Helper\ToolboxFile;
use MetaModels\IMetaModel;

/**
 * Collects the dca combinations for each MetaModel, that is matching the current user.
 *
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    MetaModels
 * @subpackage Core
 */
class MetaModelDcaBuilder
{
    /**
     * Cache for "dcasetting id" <=> "MM attribute colname" mapping.
     *
     * @var array
     */
    protected static $arrColNameChache = array();

    /**
     * All parent tables.
     *
     * Key is table name.
     *
     * @var IMetaModel[]
     */
    protected $arrPTables = array();

    /**
     * The singleton instance.
     *
     * @var MetaModelDcaBuilder
     */
    protected static $objInstance;

    /**
     * Retrieve the singleton.
     *
     * @return MetaModelDcaBuilder
     */
    public static function getInstance()
    {
        if (!self::$objInstance) {
            self::$objInstance = new MetaModelDcaBuilder();
        }
        return self::$objInstance;
    }

    /**
     * Get Contao Database instance.
     *
     * @return \Database
     */
    protected static function getDB()
    {
        return \Database::getInstance();
    }

    /**
     * Retrieve the event dispatcher from the DIC.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected static function getDispatcher()
    {
        return $GLOBALS['container']['event-dispatcher'];
    }

    /**
     * Get a 16x16 pixel resized icon of the passed image if it exists, return the default icon otherwise.
     *
     * @param string $icon        The icon to resize.
     *
     * @param string $defaultIcon The default icon.
     *
     * @return string
     */
    public static function getBackendIcon(
        $icon,
        $defaultIcon = 'system/modules/metamodels/assets/images/icons/metamodels.png'
    ) {
        $dispatcher = self::getDispatcher();
        // Determine image to use.
        if ($icon && file_exists(TL_ROOT . '/' . $icon)) {
            $event = new ResizeImageEvent($icon, 16, 16);
            $dispatcher->dispatch(ContaoEvents::IMAGE_RESIZE, $event);
            return $event->getResultImage();
        }

        return $defaultIcon;
    }

    /**
     * Inject child tables for the given table name as operations.
     *
     * @param string $strTable The table to inject into.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function injectChildTablesIntoDCA($strTable)
    {
        try {
            $objDB = \Database::getInstance();
            if (!($objDB
                && $objDB->tableExists('tl_metamodel', null)
                && $objDB->tableExists('tl_metamodel_dcasetting_condition', null)
            )) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        $arrTableDCA = &$GLOBALS['TL_DCA'][$strTable];

        $dispatcher = self::getDispatcher();
        $event      = new LoadLanguageFileEvent('default');
        $dispatcher->dispatch(ContaoEvents::SYSTEM_LOAD_LANGUAGE_FILE, $event);

        $screens = ViewCombinations::getParentedInputScreens();

        foreach ($screens as $screen) {
            if ($screen->getParentTable() !== $strTable) {
                continue;
            }

            $metaModel = $screen->getMetaModel();

            $arrCaption = array(
                sprintf(
                    $GLOBALS['TL_LANG']['MSC']['metamodel_edit_as_child']['label'],
                    $metaModel->getName()
                ),
                ''
            );

            foreach ($screen->getBackendCaption() as $arrLangEntry) {
                if ($arrLangEntry['label'] != '' && $arrLangEntry['langcode'] == $GLOBALS['TL_LANGUAGE']) {
                    $arrCaption = array($arrLangEntry['label'], $arrLangEntry['description']);
                }
            }

            $arrTableDCA['list']['operations']['edit_' . $metaModel->getTableName()] = array
            (
                'label'               => &$arrCaption,
                'href'                => 'table='.$metaModel->getTableName(),
                'icon'                => self::getBackendIcon($screen->getIcon()),
                'attributes'          => 'onclick="Backend.getScrollOffset()"',
            );

            // Is the destination table a metamodel with variants?
            if ($metaModel->hasVariants()) {
                $arrTableDCA['list']['operations']['edit_' . $metaModel->getTableName()]['idparam'] = 'id_'.$strTable;
            } else {
                $arrTableDCA['list']['operations']['edit_' . $metaModel->getTableName()]['idparam'] = 'pid';
            }

            // Compatibility with DC_Table.
            if ($arrTableDCA['config']['dataContainer'] !== 'General') {
                $arrTableDCA['list']['operations']['edit_' . $metaModel->getTableName()]['button_callback'] =
                    array(
                        __CLASS__,
                        'buildChildButton'
                    );
            }
        }
    }

    /**
     * This method exists only for being compatible when MetaModels are being used as child table from DC_Table context.
     *
     * @param array  $arrRow     The current data row.
     *
     * @param string $href       The href to be appended.
     *
     * @param string $label      The operation label.
     *
     * @param string $name       The operation name.
     *
     * @param string $icon       The icon path.
     *
     * @param string $attributes The button attributes.
     *
     * @param string $table      The table name.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function buildChildButton($arrRow, $href, $label, $name, $icon, $attributes, $table)
    {
        if (preg_match('#class="([^"]*)"#i', $attributes, $matches)) {
            $operation = $matches[1];
        } else {
            $operation = $name;
        }

        $dispatcher = self::getDispatcher();
        $idparam    = $GLOBALS['TL_DCA'][$table]['list']['operations'][$operation]['idparam'];
        $modelId    = IdSerializer::fromValues($table, $arrRow['id']);
        $urlEvent   = new AddToUrlEvent($href. '&amp;' . $idparam . '=' . $modelId->getSerialized());

        $dispatcher->dispatch(ContaoEvents::BACKEND_ADD_TO_URL, $urlEvent);

        $imageEvent = new GenerateHtmlEvent($this->getBackendIcon($icon), $label);
        $dispatcher->dispatch(ContaoEvents::IMAGE_GET_HTML, $imageEvent);

        $title = sprintf($label ?: $name, $arrRow['id']);
        return '<a href="' . $urlEvent->getUrl() . '" title="' .
            specialchars($title) . '"' . $attributes . '>' . $imageEvent->getHtml() .
        '</a> ';
    }

    /**
     * Handle stand alone integration in the backend.
     *
     * @param IInputScreen $inputScreen The input screen containing the information.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    protected function handleStandalone($inputScreen)
    {
        $metaModel  = $inputScreen->getMetaModel();
        $dispatcher = self::getDispatcher();

        $strModuleName = 'metamodel_' . $metaModel->getTableName();

        $strTableCaption = $metaModel->getName();

        $icon = ToolboxFile::convertValueToPath($inputScreen->getIcon());
        // Determine image to use.
        if ($icon && file_exists(TL_ROOT . '/' . $icon)) {
            $event = new ResizeImageEvent($icon, 16, 16);

            $dispatcher->dispatch(ContaoEvents::IMAGE_RESIZE, $event);
            $strIcon = $event->getResultImage();
        } else {
            $strIcon = 'system/modules/metamodels/assets/images/icons/metamodels.png';
        }

        $section = $inputScreen->getBackendSection();

        if (!$section) {
            $section = 'metamodels';
        }

        $GLOBALS['BE_MOD'][$section][$strModuleName] = array
        (
            'tables'   => array($metaModel->getTableName()),
            'icon'     => $strIcon,
            'callback' => 'MetaModels\BackendIntegration\Module'
        );

        $arrCaption = array($strTableCaption);
        foreach (deserialize($inputScreen->getBackendCaption(), true) as $arrLangEntry) {
            if ($arrLangEntry['langcode'] == 'en') {
                $arrCaption = array($arrLangEntry['label'], $arrLangEntry['description']);
            }

            if (!empty($arrLangEntry['label']) && ($arrLangEntry['langcode'] == $GLOBALS['TL_LANGUAGE'])) {
                $arrCaption = array($arrLangEntry['label'], $arrLangEntry['description']);
                break;
            }
        }
        $GLOBALS['TL_LANG']['MOD'][$strModuleName] = $arrCaption;
    }

    /**
     * Inject MetaModels in the backend menu.
     *
     * @return void
     */
    public function injectBackendMenu()
    {
        foreach (ViewCombinations::getStandaloneInputScreens() as $inputScreen) {
            $this->handleStandalone($inputScreen);
        }
    }

    /**
     * Inject all meta models into their corresponding parent tables.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function injectIntoBackendModules()
    {
        $screens = ViewCombinations::getParentedInputScreens();

        $pTables = array();
        foreach ($screens as $screen) {
            $ptable = $screen->getParentTable();

            $pTables[$ptable][] = $screen->getMetaModel();
        }

        $this->arrPTables = $pTables;

        $intCount = count($pTables);
        // Loop until all tables are injected or until there was no injection during one run.
        // This is important, as we might have models that are child of another model.
        while ($pTables) {
            foreach ($pTables as $strTable => $arrModels) {
                foreach ($GLOBALS['BE_MOD'] as $strGroup => $arrModules) {
                    foreach ($arrModules as $strModule => $arrConfig) {
                        if (isset($arrConfig['tables']) && in_array($strTable, $arrConfig['tables'])) {
                            $arrSubTables = array();
                            foreach ($arrModels as $metaModel) {
                                /** @var IMetaModel $metaModel */
                                $arrSubTables[] = $metaModel->getTableName();
                            }
                            $GLOBALS['BE_MOD'][$strGroup][$strModule]['tables'] = array_merge(
                                $GLOBALS['BE_MOD'][$strGroup][$strModule]['tables'],
                                $arrSubTables
                            );
                            unset($pTables[$strTable]);
                        }
                    }
                }
            }
            if (count($pTables) == $intCount) {
                break;
            }
            $intCount = count($pTables);
        }
    }

    /**
     * Create the data container of a metamodel table.
     *
     * @param string $strTableName The name of the meta model table that shall be created.
     *
     * @return bool true on success, false otherwise.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function createDataContainer($strTableName)
    {
        if (in_array($strTableName, Factory::getAllTables())) {
            $dispatcher = self::getDispatcher();
            $event      = new LoadDataContainerEvent('tl_metamodel_item');
            $dispatcher->dispatch(
                ContaoEvents::SYSTEM_LOAD_LANGUAGE_FILE,
                new LoadLanguageFileEvent('tl_metamodel_item')
            );
            $dispatcher->dispatch(ContaoEvents::CONTROLLER_LOAD_DATA_CONTAINER, $event);

            if (!isset($GLOBALS['TL_DCA'][$strTableName])) {
                $GLOBALS['TL_DCA'][$strTableName] = array();
            }

            $GLOBALS['TL_DCA'][$strTableName] = array_replace_recursive(
                (array)$GLOBALS['TL_DCA']['tl_metamodel_item'],
                (array)$GLOBALS['TL_DCA'][$strTableName]
            );

            return true;
        }

        $this->injectChildTablesIntoDCA($strTableName);

        return true;
    }
}
