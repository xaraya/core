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
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL UnionType for possibly recursive config value = unserialized in "propertie(s)" - NOT USED
 */
class xarGraphQLMultiValType extends UnionType
{
    public function __construct()
    {
        $config = static::_xar_get_type_config('MultiVal');
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
            'types' => [
                Type::string(),
                //Type::listOf(xarGraphQL::get_type("keyval")),
                xarGraphQL::get_type_list("keyval"),
            ],
            'resolveType' => function ($value, $context, ResolveInfo $info) {
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = array_merge($info->path, ["multival type"]);
                }
                if (!is_array($value)) {
                    return Type::string();
                }
                //return Type::listOf(xarGraphQL::get_type("keyval"));
                return xarGraphQL::get_type_list("keyval");
            },
        ];
    }
}
