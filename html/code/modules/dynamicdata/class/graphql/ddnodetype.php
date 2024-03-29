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
 * GraphQL ObjectType for getting DD object items using global object identification
 */
class xarGraphQLDDNodeType extends ObjectType
{
    public function __construct()
    {
        $config = static::_xar_get_type_config('DDNode');
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
            'description' => 'Get object item using global object identification',
            //'args' => [
            //    'id' => ['type' => Type::nonNull(Type::id())],
            //],
            'fields' => [
                'global_id' => ['type' => Type::nonNull(Type::id())],
                'id' => ['type' => Type::nonNull(Type::id())],
                'object' => ['type' => Type::string()],
            ],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                return $args;
            },
            'interfaces' => [
                xarGraphQL::get_type("node"),
            ],
        ];
    }
}
