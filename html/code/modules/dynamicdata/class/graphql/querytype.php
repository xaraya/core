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

class xarGraphQLQueryType extends ObjectType
{
    public static $query_mapper = [
        'hello'      => 'dummytype',
        'echo'       => 'dummytype',
        'schema'     => 'dummytype',
        'whoami'     => 'dummytype',
        'samples'    => 'sampletype',
        'sample'     => 'sampletype',
        'objects'    => 'objecttype',
        'object'     => 'objecttype',
        'properties' => 'propertytype',
        'property'   => 'propertytype',
        //'user'       => 'usertype',  // disable querying user directly
        //'node'       => 'nodetype',
    ];
    //public static $extra_types = [];

    public function __construct()
    {
        $config = static::_xar_get_type_config();
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config()
    {
        return [
            'name' => 'Query',
            'fields' => function () {
                return static::get_query_fields();
            },
        ];
    }

    /**
     * Get all root query fields for the GraphQL Query type from the query_mapper above
     */
    public static function get_query_fields()
    {
        $fields = [];
        foreach (static::$query_mapper as $name => $type) {
            $add_field = static::add_query_field($name, $type);
            if (!empty($add_field)) {
                array_push($fields, $add_field);
            }
        }
        if (!empty(xarGraphQL::$extra_types)) {
            $clazz = xarGraphQL::get_type_class("buildtype");
            foreach (xarGraphQL::$extra_types as $name) {
                $add_fields = $clazz::get_query_fields($name);
                if (!empty($add_fields)) {
                    $fields = array_merge($fields, $add_fields);
                }
            }
        }
        return $fields;
    }

    /**
     * Add a root query field as defined in the GraphQL Object Type class (list, item, other...)
     */
    public static function add_query_field($name, $type)
    {
        $clazz = xarGraphQL::get_type_class($type);
        return $clazz::_xar_get_query_field($name);
    }
}
