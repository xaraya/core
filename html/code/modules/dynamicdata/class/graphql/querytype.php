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
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Summary of xarGraphQLQueryType
 */
class xarGraphQLQueryType extends ObjectType
{
    /** @var array<string> */
    public static $query_types = ['dummytype', 'sampletype', 'objecttype', 'propertytype', 'moduleapitype'];  // 'nodetype'

    public function __construct()
    {
        $config = static::_xar_get_type_config('Query');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_type_config($typename = 'Query', $object = null)
    {
        return [
            'name' => $typename,
            'fields' => function () {
                return static::_xar_get_query_fields();
            },
        ];
    }

    /**
     * Get all root query fields for the GraphQL Query type from the query_types above
     * @return array<mixed>
     */
    public static function _xar_get_query_fields(): array
    {
        $fields = [];
        foreach (static::$query_types as $type) {
            $add_fields = static::_xar_add_query_fields($type);
            if (!empty($add_fields)) {
                $fields = array_merge($fields, $add_fields);
            }
        }
        if (!empty(xarGraphQL::$extra_types)) {
            // @checkme not possible to override page/list/item resolvers in child class by type here
            foreach (xarGraphQL::$extra_types as $name) {
                $add_fields = xarGraphQLBuildType::get_query_fields($name);
                if (!empty($add_fields)) {
                    $fields = array_merge($fields, $add_fields);
                }
            }
        }
        return $fields;
    }

    /**
     * Add the query fields defined in the GraphQL Object Type class (page, list, item, other...)
     * @param mixed $type
     * @return mixed
     */
    public static function _xar_add_query_fields($type)
    {
        $clazz = xarGraphQL::get_type_class($type);
        return $clazz::_xar_get_query_fields();
    }

    /**
     * Add a root query field as defined in the GraphQL Object Type class (page, list, item, other...)
     * @param mixed $name
     * @param mixed $type
     * @return mixed
     */
    public static function _xar_add_query_field($name, $type)
    {
        $clazz = xarGraphQL::get_type_class($type);
        return $clazz::_xar_get_query_field($name);
    }
}
