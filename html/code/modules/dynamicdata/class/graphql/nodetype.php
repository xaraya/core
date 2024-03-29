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
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL InterfaceType for getting DD object items using global object identification
 */
class xarGraphQLNodeType extends InterfaceType
{
    public function __construct()
    {
        $config = static::_xar_get_type_config('Node');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'Node interface for global object identification',
            'fields' => [
                'id' => ['type' => Type::nonNull(Type::id())],
            ],
            'resolveType' => function ($value, $context, ResolveInfo $info) {
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = array_merge($info->path, ["node type"]);
                    xarGraphQL::$paths[] = $value;
                    xarGraphQL::$paths[] = xarGraphQL::$object_type;
                }
                if (!is_array($value)) {
                    return Type::string();
                }
                //if (!empty($value['object']) && !empty(xarGraphQL::$object_type[$value['object']])) {
                //    return xarGraphQL::$object_type[$value['object']];
                //}
                return xarGraphQL::get_type("ddnode");
            },
        ];
    }

    /**
     * Summary of _xar_get_query_fields
     * @return array<string, mixed>
     */
    public static function _xar_get_query_fields()
    {
        return [
            'node' => [
                'name' => 'node',
                'description' => 'Get object item using global object identification',
                'type' => xarGraphQL::get_type("node"),
                'args' => [
                    'id' => ['type' => Type::nonNull(Type::id())],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    [$object, $id] = explode(':', $args['id']);
                    return ['global_id' => $args['id'], 'id' => $id, 'object' => $object];
                },
                //'interfaces' => [
                //    xarGraphQL::get_type("node")
                //],
            ],
        ];
    }

    /**
     * Summary of _xar_get_query_field
     * @param mixed $name
     * @return array<string, mixed>
     */
    public static function _xar_get_query_field($name)
    {
        $fields = static::_xar_get_query_fields();
        if (!empty($fields[$name])) {
            return $fields[$name];
        }
        throw new Exception("Unknown query '$name'");
    }
}
