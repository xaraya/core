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
class xarGraphQLDDNodeType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'DDNode',
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
        parent::__construct($config);
    }
}
