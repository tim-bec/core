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

namespace MetaModels\BackendIntegration\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired when MetaModels boots and is about to inject itself into the backend menu.
 */
class BackendIntegrationEvent extends Event
{
    const NAME = 'metamodels.backend-integration';
}
