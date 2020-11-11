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
class xarGraphQLObjectType extends xarGraphQLBaseType
{
    public static $_xar_name   = 'Object';
    public static $_xar_type   = 'object';
    public static $_xar_object = 'objects';
    public static $_xar_list   = 'objects';
    public static $_xar_item   = 'object';

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object)
    {
        //$clazz = xarGraphQL::get_type_class("buildtype");
        $fields = [
            'objectid' => Type::nonNull(Type::id()),
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
            //'module_id' => $clazz::get_deferred_field('module_id', 'module'),
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
            'config' => xarGraphQL::get_type("serial"),
            'config_l' => [
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
            //'category' => $clazz::get_deferred_field('category', 'category'),
            'properties' => Type::listOf(xarGraphQL::get_type("property")),
        ];
        return $fields;
    }

    /**
     * Get the list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_list_query_resolver($type, $object = null)
    {
        //$clazz = xarGraphQL::get_type_class("buildtype");
        //return $clazz::list_query_resolver($type, $object);
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r("objects resolve");
            $fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            $args = array('name' => $object);
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
        };
        return $resolver;
    }

    /**
     * Get the item query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_item_query_resolver($type, $object = null)
    {
        //$clazz = xarGraphQL::get_type_class("buildtype");
        //return $clazz::item_query_resolver($type, $object);
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r("object resolve");
            //print_r($rootValue);
            $fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            if (empty($args['id'])) {
                throw new Exception('Unknown ' . $type);
            }
            $args = array('name' => $object, 'itemid' => $args['id']);
            $objectref = DataObjectMaster::getObject($args);
            $itemid = $objectref->getItem();
            if ($itemid != $args['itemid']) {
                throw new Exception('Unknown ' . $type);
            }
            // pass along the object to field resolvers, e.g. for keys? Doesn't work...
            //$context['object'] = $objectref;
            //foreach ($objectref->getProperties() as $key => $property) {
            //    print("        '" . $property->name . "' => Type::" . $property->basetype . "(),\n");
            //}
            $values = $objectref->getFieldValues();
            // @checkme where do we unserialize best - or do we simply re-use what DD already did for us?
            //if (in_array('access', $fields)) {
            //}
            //  skip this for now and do it using the context object in field resolvers, e.g. for keys
            //if (array_key_exists('keys', $fields)) {
            //    $object_keys = array_keys($objectref->descriptor->getArgs());
            //    //$object_keys = array_filter(array_keys($objectlist->descriptor->getArgs()), function($k) { return strpos($k, 'object_') !== 0; });
            //    $values['keys'] = $object_keys;
            //}
            $values['_objectref'] = &$objectref;
            //$values['fieldlist'] = $objectref->fieldlist;
            if (array_key_exists('properties', $fields)) {
                $properties = $objectref->getProperties();
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
                //print_r($objectref->config);
                if (!empty($objectref->config)) {
                    //$values['config'] = @unserialize($objectref->config);
                    $values['config'] = $objectref->config;
                } else {
                    $values['config'] = null;
                }
            }
            return $values;
        };
        return $resolver;
    }
}
