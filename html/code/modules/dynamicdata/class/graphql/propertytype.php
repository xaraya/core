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
 * GraphQL ObjectType and query fields for "properties" dynamicdata object type
 */
class xarGraphQLPropertyType extends xarGraphQLBaseType
{
    public static $_xar_name   = 'Property';
    public static $_xar_type   = 'property';
    public static $_xar_object = 'properties';
    public static $_xar_list   = 'properties';
    public static $_xar_item   = 'property';

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object)
    {
        //$clazz = xarGraphQL::get_type_class("buildtype");
        $fields = [
            'id' => Type::nonNull(Type::id()),
            //'keys' => Type::listOf(Type::string()),
            'keys' => [
                'type' => Type::listOf(Type::string()),
                'resolve' => function ($property, $args, $context, ResolveInfo $info) {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = array_merge($info->path, ["property keys"]);
                    }
                    //print_r("property keys resolve");
                    if (is_array($property)) {
                        return array_keys($property);
                    }
                    if (!property_exists($property, 'keys')) {
                        //print_r("set property keys for " . $property->name);
                        $property->keys = array_filter(array_keys($property->descriptor->getArgs()), function ($k) {
                            return strpos($k, 'object_') !== 0;
                        });
                    }
                    return $property->keys;
                },
            ],
            // @checkme name is not returned by getProperties() because it's DISPLAYONLY?
            'name' => Type::string(),
            'label' => Type::string(),
            'objectid' => Type::string(),
            //'objectid' => xarGraphQL::get_type('object'),
            //'object_id' => $clazz::get_deferred_field('object_id', 'object'),
            'type' => Type::string(),
            'defaultvalue' => Type::string(),
            'source' => Type::string(),
            'status' => Type::int(),
            'translatable' => Type::boolean(),
            'seq' => Type::int(),
            'configuration' => xarGraphQL::get_type('serial'),
            'configuration_kv' => [
                //'type' => Type::listOf(xarGraphQL::get_type("keyval")),
                'type' => xarGraphQL::get_type_list("keyval"),
                'resolve' => function ($property, $args, $context, ResolveInfo $info) {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = array_merge($info->path, ["property configuration_kv"]);
                    }
                    if (is_array($property) && isset($property['configuration'])) {
                        $values = @unserialize($property['configuration']);
                        if (empty($values)) {
                            return [];
                        }
                        if (!is_array($values)) {
                            $values = ['' => $values];
                        }
                        $config = [];
                        foreach ($values as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = ['key' => $key, 'value' => $value];
                        }
                        return $config;
                    }
                    if (is_object($property) && property_exists($property, 'configuration') && isset($property->configuration)) {
                        $values = @unserialize($property->configuration);
                        if (empty($values)) {
                            return [];
                        }
                        if (!is_array($values)) {
                            $values = ['' => $values];
                        }
                        $config = [];
                        foreach ($values as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = ['key' => $key, 'value' => $value];
                        }
                        return $config;
                    }
                    return null;
                },
            ],
            //'objectref' => xarGraphQL::get_type("object"),
            'args' => Type::listOf(Type::string()),
        ];
        return $fields;
    }
}
