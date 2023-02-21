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
 * GraphQL ObjectType and query fields for "base" dynamicdata object type
 */
class xarGraphQLBaseType extends ObjectType implements xarGraphQLQueriesInterface, xarGraphQLMutationsInterface, xarGraphQLObjectInterface, xarGraphQLDeferredInterface, xarGraphQLInputInterface
{
    use xarGraphQLQueriesTrait;
    use xarGraphQLMutationsTrait;
    use xarGraphQLObjectTrait;
    use xarGraphQLDeferredTrait;
    use xarGraphQLInputTrait;

    public static $_xar_name   = '';
    public static $_xar_type   = '';
    public static $_xar_object = '';
    public static $_xar_security = true;
    public static $_xar_queries = [];
    public static $_xar_mutations = [];

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public function __construct($config = null)
    {
        if (empty($config)) {
            $config = static::_xar_get_type_config(static::$_xar_name, static::$_xar_object);
        }
        xarGraphQL::setTimer('new ' . $config['name']);
        // you need to pass the type config to the parent here, if you want to override the constructor
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config($typename, $object = null): array
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
    public static function _xar_get_object_fields($object): array
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
    public static function _xar_object_field_resolver($typename, $object = null): ?callable
    {
        return null;
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
}
