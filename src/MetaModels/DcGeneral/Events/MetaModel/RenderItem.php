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

namespace MetaModels\DcGeneral\Events\MetaModel;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\View\Event\RenderReadablePropertyValueEvent;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\DataDefinition\IMetaModelDataDefinition;
use MetaModels\IItem;
use MetaModels\Items;
use MetaModels\Render\Setting\Factory;
use MetaModels\Render\Setting\ICollection;
use MetaModels\Render\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Render a MetaModel item in the backend using the render setting attached to the active input screen.
 */
class RenderItem
{
    /**
     * Remove invariant attributes from the render setting.
     *
     * This is done by cloning the input collection of render settings and removing any invariant attribute.
     *
     * @param IItem       $nativeItem    The native item.
     *
     * @param ICollection $renderSetting The render setting to be used.
     *
     * @return ICollection
     */
    protected static function removeInvariantAttributes(IItem $nativeItem, ICollection $renderSetting)
    {
        $model = $nativeItem->getMetaModel();

        if ($model->hasVariants() && !$nativeItem->isVariantBase()) {
            // Create a clone to have a separate copy of the object as we are going to manipulate it here.
            $renderSetting = clone $renderSetting;

            // Loop over all attributes and remove those from rendering that are not desired.
            foreach (array_keys($model->getInVariantAttributes()) as $strAttrName) {
                $renderSetting->setSetting($strAttrName, null);
            }
        }

        return $renderSetting;
    }

    /**
     * Render the current item using the specified render setting.
     *
     * @param ModelToLabelEvent $event The event.
     *
     * @return void
     */
    public static function render(ModelToLabelEvent $event)
    {
        $environment = $event->getEnvironment();
        /** @var IMetaModelDataDefinition $definition */
        $definition = $environment->getDataDefinition();

        /** @var Model $model */
        $model = $event->getModel();

        if (!($model instanceof Model)) {
            return;
        }

        $nativeItem = $model->getItem();
        $metaModel  = $nativeItem->getMetaModel();

        $renderSetting = Factory::byId(
            $metaModel,
            $definition->getMetaModelDefinition()->getActiveRenderSetting()
        );

        if (!$renderSetting) {
            return;
        }

        $template      = new Template($renderSetting->get('template'));
        $renderSetting = self::removeInvariantAttributes($nativeItem, $renderSetting);

        $template->settings = $renderSetting;
        $template->items    = new Items(array($nativeItem));
        $template->view     = $renderSetting;
        $template->data     = array($nativeItem->parseValue('html5', $renderSetting));

        $event->setArgs(array($template->parse('html5', true)));
    }

    /**
     * Render a model for use in a group header.
     *
     * @param RenderReadablePropertyValueEvent $event The event.
     *
     * @return void
     */
    public static function getReadableValue(RenderReadablePropertyValueEvent $event)
    {
        $environment = $event->getEnvironment();
        /** @var IMetaModelDataDefinition $definition */
        $definition = $environment->getDataDefinition();

        /** @var Model $model */
        $model = $event->getModel();

        if (!($model instanceof Model)) {
            return;
        }

        $nativeItem = $model->getItem();
        $metaModel  = $nativeItem->getMetaModel();

        $renderSetting = Factory::byId(
            $metaModel,
            $definition->getMetaModelDefinition()->getActiveRenderSetting()
        );

        if (!$renderSetting) {
            return;
        }

        $result = $nativeItem->parseAttribute($event->getProperty()->getName(), 'text', $renderSetting);
        $event->setRendered($result['text']);
    }

    /**
     * Register to the event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher The event dispatcher.
     *
     * @return void
     */
    public static function register($dispatcher)
    {
        $dispatcher->addListener(ModelToLabelEvent::NAME, array(__CLASS__, 'render'));
        $dispatcher->addListener(RenderReadablePropertyValueEvent::NAME, array(__CLASS__, 'getReadableValue'));
    }
}
