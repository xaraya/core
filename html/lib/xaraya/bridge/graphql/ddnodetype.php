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

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL ObjectType for getting DD object items using global object identification
 */
class DDNodeType extends ObjectType
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
                GraphQLTypes::getType("node"),
            ],
        ];
    }
}
