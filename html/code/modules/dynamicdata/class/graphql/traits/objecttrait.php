<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * Trait to handle default object types for dataobjects
 */
trait xarGraphQLObjectTrait
{
    /**
     * Make a generic Object Type for a dynamicdata object type by name = "Module" for modules etc.
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     *
     * Use inline style to define Object Type here instead of inheritance
     * https://webonyx.github.io/graphql-php/type-system/object-types/
     */
    public static function _xar_get_object_type($typename, $object = null)
    {
        $object ??= xarGraphQLInflector::pluralize($typename);
        // https://webonyx.github.io/graphql-php/type-definitions/object-types/#recurring-and-circular-types
        // $fields = static::_xar_get_object_fields($object);
        $newType = new ObjectType(
            static::_xar_get_type_config($typename, $object)
        );
        return $newType;
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        $object ??= xarGraphQLInflector::pluralize($typename);
        return [
            'name' => ucwords($typename, '_'),
            'description' => 'DD ' . $object . ' item',
            'fields' => function () use ($object) {
                return static::_xar_get_object_fields($object);
            },
            'resolveField' => static::_xar_object_field_resolver($typename, $object),
        ];
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object)
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
        ];
        return $fields;
    }

    /**
     * Get the object field resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_object_field_resolver($typename, $object = null)
    {
        // $clazz = xarGraphQL::get_type_class("buildtype");
        // return $clazz::object_field_resolver($typename, $object);
    }
}
