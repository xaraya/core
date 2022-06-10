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
    use xarGraphQLInputTrait;

    public static $_xar_name   = 'KeyVal';

    public function __construct()
    {
        $config = static::_xar_get_type_config(static::$_xar_name);
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'Key Value combination for assoc arrays',
            'fields' => [
                'key' => Type::string(),
                //'value' => Type::string(),
                'value' => xarGraphQL::get_type('mixed'),
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
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = array_merge($info->path, ["keyval field"]);
                }
                if (empty($object)) {
                    return null;
                }
                //print_r($object);
                if (array_key_exists($info->fieldName, $object)) {
                    return $object[$info->fieldName];
                }
                //return $info->fieldName;
                return null;
            }
             */
        ];
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_input_fields($object, &$newType)
    {
        // return static::_xar_get_object_fields($object);
        $fields = [
            'key' => Type::string(),
            //'value' => Type::string(),
            'value' => xarGraphQL::get_type('mixed'),  // Scalar Type doesn't need an equivalent Input Type
        ];
        return $fields;
    }
}
