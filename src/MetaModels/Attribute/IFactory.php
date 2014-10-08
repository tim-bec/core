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
interface IFactory
{
    /**
     * Create an attribute instance from an information array.
     *
     * @param array      $information The attribute information.
     *
     * @param IMetaModel $metaModel   The MetaModel instance for which the attribute shall be created.
     *
     * @return IAttribute|null
     */
    public function createAttribute($information, $metaModel);

    /**
     * Add a type factory to this factory.
     *
     * @param IAttributeTypeFactory $typeFactory The type factory to add.
     *
     * @return IFactory
     */
    public function addTypeFactory(IAttributeTypeFactory $typeFactory);

    /**
     * Retrieve a type factory from this factory.
     *
     * @param string $typeFactory The name of the type factory to retrieve.
     *
     * @return IAttributeTypeFactory
     */
    public function getTypeFactory($typeFactory);

    /**
     * Check if the attribute matches the flags.
     *
     * @param string $factory The name of the factory to check.
     *
     * @param int    $flags   The flags to match.
     *
     * @return bool
     */
    public function attributeTypeMatchesFlags($factory, $flags);

    /**
     * Retrieve the type names registered in the factory.
     *
     * @param bool|int $flags The flags for retrieval. See the interface constants for the different values.
     *
     * @return string[]
     */
    public function getTypeNames($flags = false);

    /**
     * Collect all attribute information for a MetaModel.
     *
     * The resulting information will then get passed to the attribute factories to create attribute instances.
     *
     * @param IMetaModel $metaModel The MetaModel for which attribute information shall be retrieved.
     *
     * @return array
     */
    public function collectAttributeInformation(IMetaModel $metaModel);

    /**
     * Create all attribute instances for the given MetaModel.
     *
     * @param IMetaModel $metaModel The MetaModel to create the attributes for.
     *
     * @return IAttribute[]
     */
    public function createAttributesForMetaModel($metaModel);

    /**
     * Instantiate a attribute from an array.
     *
     * @param array $arrData The attribute information data.
     *
     * @return IAttribute|null The instance of the attribute or NULL if the class could not be determined
     *
     * @deprecated Use an instance of the factory and method createAttribute().
     */
    public static function createFromArray($arrData);

    /**
     * Instantiate a attribute from an array.
     *
     * @param \Database\Result $objRow The attribute information data.
     *
     * @return IAttribute|null The instance of the attribute or NULL if the class could not be determined.
     *
     * @deprecated Use an instance of the factory and method createAttribute().
     */
    public static function createFromDB($objRow);

    /**
     * Instantiate all attributes for the given MetaModel instance.
     *
     * @param IMetaModel $objMetaModel The MetaModel instance for which all attributes shall be returned.
     *
     * @return IAttribute[] The instances of the attributes.
     *
     * @deprecated Use an instance of the factory and method createAttribute().
     */
    public static function getAttributesFor($objMetaModel);

    /**
     * Returns an array of all registered attribute types.
     *
     * @return string[] All attribute types.
     *
     * @deprecated Will not be in available anymore - if you need this, file a ticket.
     */
    public static function getAttributeTypes();

    /**
     * Checks whether the given attribute type name is registered in the system.
     *
     * @param string $strFieldType The attribute type name to check.
     *
     * @return bool True if the attribute type is valid, false otherwise.
     *
     * @deprecated Will not be in available anymore - if you need this, file a ticket.
     */
    public static function isValidAttributeType($strFieldType);
}
