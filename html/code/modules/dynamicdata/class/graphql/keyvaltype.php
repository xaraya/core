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
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL ObjectType and (no) query fields for assoc array configuration = unserialized in "propertie(s)"
 */
class xarGraphQLKeyValType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'KeyVal',
            'fields' => [
                'key' => Type::string(),
                'value' => Type::string(),
                // @checkme this causes memory problems!
                //'value' => xarGraphQL::get_type("multival"),
            ],
            /**
            // see recurring and circular types at https://webonyx.github.io/graphql-php/type-system/object-types/
            'fields' => function() {
                return [
                    'key' => Type::string(),
                    'value' => xarGraphQL::get_type("multival"),
                ];
            }
             */
            /**
            'resolveField' => function ($object, $args, $context, ResolveInfo $info) {
                if (empty($object)) {
                    return null;
                }
                print_r($object);
                if (array_key_exists($info->fieldName, $object)) {
                    return $object[$info->fieldName];
                }
                //return $info->fieldName;
                return null;
            }
             */
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
