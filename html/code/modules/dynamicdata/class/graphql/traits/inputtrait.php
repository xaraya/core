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
use GraphQL\Type\Definition\InputObjectType;

/**
 * For documentation purposes only - available via xarGraphQLInputTrait
 */
interface xarGraphQLInputInterface
{
    public static function _xar_get_input_type($typename, $object = null): InputObjectType;
    public static function _xar_get_input_fields($object, &$newType): array;
    public static function _xar_input_value_parser($typename, $object): ?callable;
}

/**
 * Trait to handle default input object types for dataobjects
 */
trait xarGraphQLInputTrait
{
    /**
     * Make a generic Input Object Type for create/update mutations
     */
    public static function _xar_get_input_type($typename, $object = null): InputObjectType
    {
        $object ??= xarGraphQLInflector::pluralize($typename);
        $description = "Input for DD " . $object . " item";
        // https://webonyx.github.io/graphql-php/type-definitions/object-types/#recurring-and-circular-types
        // $fields = static::_xar_get_input_fields($object);
        $newType = new InputObjectType([
            'name' => ucwords($typename, '_'),
            'description' => $description,
            'fields' => function () use ($object, &$newType) {
                return static::_xar_get_input_fields($object, $newType);
            },
            'parseValue' => static::_xar_input_value_parser($typename, $object),
        ]);
        return $newType;
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_input_fields($object, &$newType): array
    {
        // return static::_xar_get_object_fields($object);
        $fields = [
            'id' => Type::id(),  // allow null for create here
            'name' => Type::string(),
        ];
        return $fields;
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_input_value_parser($typename, $object): ?callable
    {
        return null;
    }
}
