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
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use Exception;

/**
 * GraphQL InterfaceType for getting DD object items using global object identification
 */
class NodeInterfaceType extends InterfaceType
{
    public function __construct()
    {
        $config = $this->get_type_config('Node');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'Node interface for global object identification',
            'fields' => [
                'id' => ['type' => Type::nonNull(Type::id())],
            ],
            'resolveType' => function ($value, $context, ResolveInfo $info) {
                $context->tracePath(__CLASS__ . '::get_type_config: resolveType', $info->path);
                //$context->tracePath($value);
                //$context->tracePath(GraphQLObjects::getTypes());
                if (!is_array($value)) {
                    return Type::string();
                }
                //if (!empty($value['object']) && !empty(GraphQLObjects::getType($value['object']))) {
                //    return GraphQLObjects::getType($value['object']);
                //}
                return GraphQLTypes::getType("ddnode");
            },
        ];
    }

    /**
     * Summary of get_query_fields
     * @return array<string, mixed>
     */
    public static function get_query_fields()
    {
        return [
            'node' => [
                'name' => 'node',
                'description' => 'Get object item using global object identification',
                'type' => GraphQLTypes::getType("node"),
                'args' => [
                    'id' => ['type' => Type::nonNull(Type::id())],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve');
                    [$object, $id] = explode(':', $args['id']);
                    return ['global_id' => $args['id'], 'id' => $id, 'object' => $object];
                },
                //'interfaces' => [
                //    GraphQLTypes::getType("node")
                //],
            ],
        ];
    }

    /**
     * Summary of get_query_field
     * @param mixed $name
     * @return array<string, mixed>
     */
    public static function get_query_field($name)
    {
        $fields = static::get_query_fields();
        if (!empty($fields[$name])) {
            return $fields[$name];
        }
        throw new Exception("Unknown query '$name'");
    }
}
