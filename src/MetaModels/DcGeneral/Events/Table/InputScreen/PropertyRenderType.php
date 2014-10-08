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

namespace MetaModels\DcGeneral\Events\Table\InputScreen;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;

/**
 * Manipulate the data definition for the property "rendertype" in table "tl_dca".
 *
 * @package MetaModels\DcGeneral\Events\Table\InputScreen
 */
class PropertyRenderType
{
    /**
     * Populates an array with all valid "rendertype".
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public static function getRenderTypes(GetPropertyOptionsEvent $event)
    {
        $event->setOptions(array('standalone', 'ctable'));
    }
}
