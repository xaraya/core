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
 * GraphQL ObjectType and query fields for "objects" dynamicdata object type
 */
class xarGraphQLObjectType extends ObjectType
{
    public static $_xar_name   = 'Object';
    public static $_xar_type   = 'object';
    public static $_xar_object = 'objects';
    public static $_xar_list   = 'objects';
    public static $_xar_item   = 'object';

    public function __construct()
    {
        $config = [
            'name' => self::$_xar_name,
            'fields' => [
                'objectid' => Type::id(),
                //'fieldlist' => Type::listOf(Type::string()),
                //'keys' => Type::listOf(Type::string()),
                'keys' => [
                    'type' => Type::listOf(Type::string()),
                    'resolve' => function ($object, $args, $context, ResolveInfo $info) {
                        //print_r("object keys resolve");
                        if (empty($object['_objectref'])) {
                            return null;
                        }
                        return array_keys($object['_objectref']->descriptor->getArgs());
                    }
                ],
                'name' => Type::string(),
                'label' => Type::string(),
                'module_id' => Type::string(),
                'itemtype' => Type::int(),
                'class' => Type::string(),
                'urlparam' => Type::string(),
                // @checkme where do we unserialize best - or do we simply re-use what DD already did for us?
                //'access' => xarGraphQL::get_type("access"),
                'access' => [
                    'type' => xarGraphQL::get_type("access"),
                    'resolve' => function ($object, $args) {
                        //print_r("access resolve");
                        if (empty($object['access'])) {
                            return null;
                        }
                        //print_r($object['access']);
                        return @unserialize($object['access']);
                    }
                ],
                // this is not returned via getFieldValues()
                'config' => [
                    'type' => Type::listOf(Type::string()),
                    'resolve' => function ($object, $args) {
                        // Note: this may not be filled in by object(s) resolve above
                        //print_r("config resolve");
                        if (empty($object['config'])) {
                            return null;
                        }
                        //print_r($object['config']);
                        return @unserialize($object['config']);
                    }
                ],
                'maxid' => Type::int(),
                'isalias' => Type::boolean(),
                'category' => Type::string(),
                'properties' => Type::listOf(xarGraphQL::get_type("property")),
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
                    //print_r("objects resolve");
                    $fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    $args = array('name' => self::$_xar_object);
                    $objectlist = DataObjectMaster::getObjectList($args);
                    //foreach ($objectlist->getProperties() as $key => $property) {
                    //    print($property->name . ': ' . $property->basetype . "\n");
                    //}
                    $items = $objectlist->getItems();
                    // @checkme where do we unserialize best - or do we simply re-use what DD already did for us?
                    //if (in_array('access', $fields)) {
                    //}
                    // pass along the object to field resolvers, e.g. for keys? Doesn't work...
                    //$context['object'] = $objectlist;
                    //if (array_key_exists('keys', $fields)) {
                    //    $object_keys = array_keys($objectlist->descriptor->getArgs());
                    //    //$object_keys = array_filter(array_keys($objectlist->descriptor->getArgs()), function($k) { return strpos($k, 'object_') !== 0; });
                    //    foreach ($items as $key => $item) {
                    //        $items[$key]['keys'] = $object_keys;
                    //    }
                    //}
                    foreach ($items as $key => $item) {
                        $items[$key]['_objectref'] = &$objectlist;
                    }
                    if (array_key_exists('properties', $fields)) {
                        $properties = $objectlist->getProperties();
                        /**
                        if (is_array($fields['properties']) && in_array('keys', $fields['properties'])) {
                            foreach ($properties as $property) {
                                // @checkme name is not returned by getProperties() because it's DISPLAYONLY?
                                //$property->keys = array_keys(get_object_vars($property));
                                //$property->keys = array_keys($property->getPublicProperties());
                                //$property->keys = array_keys($property->descriptor->getArgs());
                                $property->keys = array_filter(array_keys($property->descriptor->getArgs()), function($k) { return strpos($k, 'object_') !== 0; });
                            }
                        }
                         */
                        foreach ($items as $key => $item) {
                            $items[$key]['properties'] = $properties;
                        }
                    }
                    //if (in_array('config', $fields)) {
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
                    //print_r("object resolve");
                    //print_r($rootValue);
                    $fields = $info->getFieldSelection(1);
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
                    // pass along the object to field resolvers, e.g. for keys? Doesn't work...
                    //$context['object'] = $object;
                    //foreach ($object->getProperties() as $key => $property) {
                    //    print("        '" . $property->name . "' => Type::" . $property->basetype . "(),\n");
                    //}
                    $values = $object->getFieldValues();
                    // @checkme where do we unserialize best - or do we simply re-use what DD already did for us?
                    //if (in_array('access', $fields)) {
                    //}
                    //  skip this for now and do it using the context object in field resolvers, e.g. for keys
                    //if (array_key_exists('keys', $fields)) {
                    //    $object_keys = array_keys($object->descriptor->getArgs());
                    //    //$object_keys = array_filter(array_keys($objectlist->descriptor->getArgs()), function($k) { return strpos($k, 'object_') !== 0; });
                    //    $values['keys'] = $object_keys;
                    //}
                    $values['_objectref'] = &$object;
                    //$values['fieldlist'] = $object->fieldlist;
                    if (array_key_exists('properties', $fields)) {
                        $properties = $object->getProperties();
                        /**
                        if (is_array($fields['properties']) && in_array('keys', $fields['properties'])) {
                            foreach ($properties as $property) {
                                // @checkme name is not returned by getProperties() because it's DISPLAYONLY?
                                //$property->keys = array_keys(get_object_vars($property));
                                //$property->keys = array_keys($property->getPublicProperties());
                                //$property->keys = array_keys($property->descriptor->getArgs());
                                $property->keys = array_filter(array_keys($property->descriptor->getArgs()), function($k) { return strpos($k, 'object_') !== 0; });
                            }
                        }
                         */
                        $values['properties'] = $properties;
                        if (!empty($values['category']) && !empty($values['category'][0])) {
                            $values['category'] = $values['category'][0]['name'];
                        } else {
                            $values['category'] = '';
                        }
                    }
                    // this is not returned via getFieldValues()
                    if (in_array('config', $fields)) {
                        //print_r("object resolve");
                        //print_r($object->config);
                        if (!empty($object->config)) {
                            //$values['config'] = @unserialize($object->config);
                            $values['config'] = $object->config;
                        } else {
                            $values['config'] = null;
                        }
                    }
                    return $values;
                }
            ],
        ];
        return array($name => $fields[$name]);
    }
}
