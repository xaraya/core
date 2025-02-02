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
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL UnionType for possibly recursive config value = unserialized in "propertie(s)" - NOT USED
 */
class MultiValType extends UnionType
{
    public function __construct()
    {
        $config = $this->get_type_config('MultiVal');
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
            'types' => [
                Type::string(),
                //Type::listOf(GraphQLTypes::getType("keyval")),
                GraphQLTypes::getTypeList("keyval"),
            ],
            'resolveType' => function ($value, $context, ResolveInfo $info) {
                GraphQLHandler::tracePath(array_merge($info->path, ["multival type"]));
                if (!is_array($value)) {
                    return Type::string();
                }
                //return Type::listOf(GraphQLTypes::getType("keyval"));
                return GraphQLTypes::getTypeList("keyval");
            },
        ];
    }
}
