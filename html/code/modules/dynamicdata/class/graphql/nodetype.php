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
 * GraphQL ObjectType and (no) query fields for possibly recursive config value = unserialized in "propertie(s)"
 */
class xarGraphQLNodeType extends InterfaceType
{
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
            'name' => 'Node',
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

    public static function _xar_get_query_field($name)
    {
        $fields = [
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
        if (!empty($fields[$name])) {
            return $fields[$name];
        }
    }
}
