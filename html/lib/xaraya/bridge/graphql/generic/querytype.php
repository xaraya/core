<?php

/**
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

namespace Xaraya\Bridge\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * Summary of QueryType
 */
class QueryType extends ObjectType
{
    /** @var array<string> */
    public static $query_types = ['dummytype', 'sampletype', 'objecttype', 'propertytype', 'moduleapitype'];  // 'nodetype'

    public function __construct()
    {
        $config = $this->get_type_config('Query');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_type_config($typename = 'Query', $object = null)
    {
        return [
            'name' => $typename,
            'fields' => function () {
                return $this->get_query_fields();
            },
        ];
    }

    /**
     * Get all root query fields for the GraphQL Query type from the query_types above
     * @return array<mixed>
     */
    public static function get_query_fields(): array
    {
        $fields = [];
        foreach (static::$query_types as $type) {
            $add_fields = static::add_query_fields($type);
            if (!empty($add_fields)) {
                $fields = array_merge($fields, $add_fields);
            }
        }
        if (!empty(GraphQLTypes::getExtraTypes())) {
            // @checkme not possible to override page/list/item resolvers in child class by type here
            foreach (GraphQLTypes::getExtraTypes() as $name) {
                $add_fields = BuildType::get_query_fields($name);
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
    public static function add_query_fields($type)
    {
        $clazz = GraphQLTypes::getTypeClass($type);
        return $clazz::get_query_fields();
    }

    /**
     * Add a root query field as defined in the GraphQL Object Type class (page, list, item, other...)
     * @param mixed $name
     * @param mixed $type
     * @return mixed
     */
    public static function add_query_field($name, $type)
    {
        $clazz = GraphQLTypes::getTypeClass($type);
        return $clazz::get_query_field($name);
    }
}
