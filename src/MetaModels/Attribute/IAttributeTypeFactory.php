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

namespace MetaModels\Attribute;

use MetaModels\IMetaModel;

/**
 * This is the factory interface to query instances of attributes.
 * Usually this is only used internally from within the MetaModel class.
 *
 * @package    MetaModels
 * @subpackage Interfaces
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
interface IAttributeTypeFactory
{
    /**
     * Flag for filtering translated attributes.
     */
    const FLAG_INCLUDE_TRANSLATED = 1;

    /**
     * Flag for translated attributes.
     */
    const FLAG_INCLUDE_SIMPLE = 2;

    /**
     * Flag for complex attributes.
     */
    const FLAG_INCLUDE_COMPLEX = 4;

    /**
     * Flag for retrieving all attribute types.
     */
    const FLAG_ALL = 7;

    /**
     * Flag for filtering untranslated attributes.
     */
    const FLAG_ALL_UNTRANSLATED = 6;

    /**
     * Return the type name - this is the internal type name used by MetaModels.
     *
     * @return string
     */
    public function getTypeName();

    /**
     * Create a new instance with the given information.
     *
     * @param array      $information The attribute information.
     *
     * @param IMetaModel $metaModel   The MetaModel instance the attribute shall be created for.
     *
     * @return IAttribute|null
     */
    public function createInstance($information, $metaModel);

    /**
     * Check if the type is translated.
     *
     * @return bool
     */
    public function isTranslatedType();

    /**
     * Check if the type is of simple nature.
     *
     * @return bool
     */
    public function isSimpleType();

    /**
     * Check if the type is of complex nature.
     *
     * @return bool
     */
    public function isComplexType();
}
