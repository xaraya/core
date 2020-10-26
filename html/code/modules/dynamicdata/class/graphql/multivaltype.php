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
 * GraphQL ObjectType and (no) query fields for possibly recursive config value = unserialized in "propertie(s)"
 */
class xarGraphQLMultiValType extends UnionType
{
    public function __construct()
    {
        $config = [
            'name' => 'MultiVal',
            'types' => [
                Type::string(),
                Type::listOf(xarGraphQL::get_type("keyval")),
            ],
            'resolveType' => function ($value, $context, ResolveInfo $info) {
                if (!is_array($value)) {
                    return Type::string();
                }
                return Type::listOf(xarGraphQL::get_type("keyval"));
            }
        ];
        parent::__construct($config);
    }

    public static function _xar_get_query_field($name)
    {
        $fields = [
        ];
        return array($name => $fields[$name]);
    }
}
