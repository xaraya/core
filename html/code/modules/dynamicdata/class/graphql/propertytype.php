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
class xarGraphQLPropertyType extends ObjectType
{
    public static $_xar_name   = 'Property';
    public static $_xar_type   = 'property';
    public static $_xar_object = 'properties';
    public static $_xar_list   = 'properties';
    public static $_xar_item   = 'property';

    public function __construct()
    {
        $config = [
            'name' => self::$_xar_name,
            'fields' => [
                'id' => Type::nonNull(Type::id()),
                //'keys' => Type::listOf(Type::string()),
                'keys' => [
                    'type' => Type::listOf(Type::string()),
                    'resolve' => function ($property, $args, $context, ResolveInfo $info) {
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
                    }
                ],
                // @checkme name is not returned by getProperties() because it's DISPLAYONLY?
                'name' => Type::string(),
                'label' => Type::string(),
                'objectid' => Type::string(),
                'type' => Type::string(),
                'defaultvalue' => Type::string(),
                'source' => Type::string(),
                'status' => Type::int(),
                'translatable' => Type::boolean(),
                'seq' => Type::int(),
                //'configuration' => Type::string(),
                'configuration' => [
                    'type' => Type::listOf(xarGraphQL::get_type("keyval")),
                    'resolve' => function ($property, $args, $context, ResolveInfo $info) {
                        //print_r("property config resolve");
                        if (is_array($property) && isset($property['configuration'])) {
                            $values = @unserialize($property['configuration']);
                            if (empty($values)) {
                                return array();
                            }
                            if (!is_array($values)) {
                                $values = array('' => $values);
                            }
                            $config = array();
                            foreach ($values as $key => $value) {
                                if (is_array($value)) {
                                    $value = json_encode($value);
                                }
                                $config[] = array('key' => $key, 'value' => $value);
                            }
                            return $config;
                        }
                        if (property_exists($property, 'configuration') && isset($property->configuration)) {
                            $values = @unserialize($property->configuration);
                            if (empty($values)) {
                                return array();
                            }
                            if (!is_array($values)) {
                                $values = array('' => $values);
                            }
                            $config = array();
                            foreach ($values as $key => $value) {
                                if (is_array($value)) {
                                    $value = json_encode($value);
                                }
                                $config[] = array('key' => $key, 'value' => $value);
                            }
                            return $config;
                        }
                        return null;
                    }
                ],
                //'objectref' => xarGraphQL::get_type("object"),
                'args' => Type::listOf(Type::string()),
            ],
        ];
        parent::__construct($config);
    }

    public static function _xar_get_query_field($name)
    {
        $fields = [
            self::$_xar_list => [
                'type' => Type::listOf(xarGraphQL::get_type(self::$_xar_type)),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    //print_r($rootValue);
                    $fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    $args = array('name' => self::$_xar_object, 'fieldlist' => array_keys($fields));
                    $objectlist = DataObjectMaster::getObjectList($args);
                    $items = $objectlist->getItems();
                    //if (array_key_exists('name', $fields)) {
                    //    foreach ($items as $key => $item) {
                    //        $items[$key]['name'] = $objectlist->items[$key]['name'];
                    //    }
                    //}
                    return $items;
                }
            ],
            self::$_xar_item => [
                'type' => xarGraphQL::get_type(self::$_xar_type),
                'args' => [
                    'id' => Type::nonNull(Type::id())
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    //print_r($rootValue);
                    //$fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    if (empty($args['id'])) {
                        throw new Exception('Unknown ' . self::$_xar_type);
                    }
                    $args = array('name' => self::$_xar_object, 'itemid' => $args['id']);
                    $object = DataObjectMaster::getObject($args);
                    $itemid = $object->getItem();
                    if ($itemid != $args['itemid']) {
                        throw new Exception('Unknown ' . self::$_xar_type);
                    }
                    $values = $object->getFieldValues();
                    return $values;
                }
            ],
        ];
        return array($name => $fields[$name]);
    }
}
