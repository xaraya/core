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
use GraphQL\Deferred;

/**
 * Build GraphQL ObjectType, query fields and resolvers for generic dynamicdata object type
 */
//class xarGraphQLBuildType extends ObjectType
class xarGraphQLBuildType
{
    public static $property_id = [];

    /**
     * Make a generic Object Type for a dynamicdata object type by name = "Module" for modules etc.
     *
     * Use inline style to define Object Type here instead of inheritance
     * https://webonyx.github.io/graphql-php/type-system/object-types/
     */
    public static function make_type($name, $type = null, $object = null, $list = null, $item = null)
    {
        // name=Property, type=property, object=properties, list=properties, item=property
        list($name, $type, $object, $list, $item) = self::sanitize($name, $type, $object, $list, $item);
        $description = "$name: generic $type type for $object objects ($list, $item)";
        $fields = self::get_object_fields($object);
        $newType = new ObjectType([
            'name' => $name,
            'description' => $description,
            'fields' => $fields,
            'resolveField' => self::object_field_resolver($type, $object),
        ]);
        return $newType;
    }

    /**
     * Make a generic Object Type with pagination
     */
    public static function make_page_type($name, $type = null, $object = null, $list = null, $item = null)
    {
        // name=Property, type=property, object=properties, list=properties, item=property
        list($name, $type, $object, $list, $item) = self::sanitize($name, $type, $object, $list, $item);
        $page = $name . '_Page';
        $description = "$page: paginated list of $list for $object objects";
        $fields = [
            'sort' => Type::string(),
            'offset' => Type::int(),
            'limit' => Type::int(),
            'count' => Type::int(),
            $list => Type::listOf(xarGraphQL::get_type($type)),
        ];
        $newType = new ObjectType([
            'name' => $page,
            'description' => $description,
            'fields' => $fields,
            //'resolveField' => self::object_field_resolver($type, $object),
        ]);
        return $newType;
    }

    /**
     * Sanitize name, type, object, list and item based on given name, e.g.:
     * name=Object, type=object, object=objects, list=objects, item=object
     * name=Property, type=property, object=properties, list=properties, item=property
     */
    public static function sanitize($name, $type = null, $object = null, $list = null, $item = null)
    {
        // Object -> object / Property -> property
        if (!isset($type)) {
            $type = strtolower($name);
        }
        // object -> objects / property -> properties
        if (!isset($object)) {
            // Basic pluralize for most common case(s)
            $object = self::pluralize($type);
        }
        // objects -> objects / properties -> properties
        if (!isset($list)) {
            $list = $object;
        }
        // object -> object / property-> property
        if (!isset($item)) {
            $item = $type;
        }
        if ($name === $type) {
            $name = ucfirst($name);
        }
        return array($name, $type, $object, $list, $item);
    }

    /**
     * Basic pluralize for most common case(s):
     * object -> objects / property -> properties
     */
    public static function pluralize($type)
    {
        if (substr($type, -1) === "y") {
            $object = substr($type, 0, strlen($type) - 1) . "ies";
        } elseif ($type !== "sample") {
            $object = $type . "s";
        } else {
            $object = $type;
        }
        return $object;
    }

    /**
     * Basic singularize for most common case(s):
     * objects -> object / properties -> property
     */
    public static function singularize($name)
    {
        if (substr($name, -3) === "ies") {
            $type = substr($name, 0, strlen($name) - 3) . "y";
        } elseif (substr($name, -1) === "s") {
            $type = substr($name, 0, strlen($name) - 1);
        } else {
            $type = $name;
        }
        return $type;
    }

    /**
     * Get the object type fields for this dynamicdata object type
     */
    public static function get_object_fields($object)
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
            'keys' => Type::listOf(Type::string()),
        ];
        //$args = array('name' => $object, 'numitems' => 1);
        //$objectlist = DataObjectMaster::getObjectList($args);
        //print_r($objectlist->getItems());
        $params = array('name' => $object);
        $objectref = DataObjectMaster::getObject($params);
        $basetypes = [
            'string' => Type::string(),
            'integer' => Type::int(),
            'decimal' => Type::float(),
            'dropdown' => Type::string(),  // @todo use EnumType here?
        ];
        // @todo add fields based on object descriptor?
        $user_prop_id = self::get_property_id('username');
        foreach ($objectref->getProperties() as $key => $property) {
            if ($property->type == $user_prop_id) {
                $fields[$property->name] = self::get_deferred_field($property->name, 'user');
                continue;
            }
            if ($property->name == 'configuration') {
                $fields[$property->name] = Type::listOf(xarGraphQL::get_type("keyval"));
                continue;
            }
            if (!array_key_exists($property->name, $fields)) {
                $fields[$property->name] = $basetypes[$property->basetype];
            }
        }
        return $fields;
    }

    public static function get_property_id($name)
    {
        if (empty(self::$property_id[$name])) {
            $proptypes = DataPropertyMaster::getPropertyTypes();
            foreach ($proptypes as $typeid => $proptype)
            {
                if($proptype['name'] == $name)
                {
                    self::$property_id[$name] = $typeid;
                    break;
                }
            }
        }
        return self::$property_id[$name];
    }

    public static function get_deferred_field($name, $type)
    {
        return [
            'name' => $name,
            'type' => xarGraphQL::get_type($type),
            'resolve' => self::deferred_field_resolver($type, $name),
        ];
    }

    public static function deferred_field_resolver($type, $prop_name)
    {
        // we only need the type class here, not the type instance
        $clazz = xarGraphQL::get_type_class($type);
        return $clazz::_xar_deferred_field_resolver($prop_name);
    }

    /**
     * Get the field resolver for the object type fields
     */
    public static function object_field_resolver($type, $object = null)
    {
        // when using type config decorator
        if (!isset($object)) {
            $type = self::singularize($type);
            list($name, $type, $object, $list, $item) = self::sanitize($type);
        }
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($type, $object) {
            $name = $info->fieldName;
            if (is_array($values)) {
                if ($name == 'keys') {
                    return array_keys($values);
                }
                if (array_key_exists($name, $values)) {
                    // see propertytype
                    if ($name == 'configuration' && is_string($values[$name]) && !empty($values[$name])) {
                        $result = @unserialize($values[$name]);
                        $config = array();
                        foreach ($result as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = array('key' => $key, 'value' => $value);
                        }
                        return $config;
                    }
                    return $values[$name];
                }
            }
            if (is_object($values)) {
                if ($name == 'keys') {
                    if (property_exists($values, 'descriptor')) {
                        return array_keys($values->descriptor->getArgs());
                    }
                    return $values->getPublicProperties();
                }
                if (property_exists($values, 'properties') && in_array($name, $values->properties)) {
                    return $values->properties[$name]->getValue();
                }
                if (property_exists($values, $name)) {
                    return $values->{$name};
                }
            }
            //var_dump($values);
            //return $values;
        };
        return $resolver;
    }

    /**
     * Get the root query fields for this object for the GraphQL Query type (list, item)
     */
    public static function get_query_fields($name, $type = null, $object = null, $list = null, $item = null)
    {
        // name=Property, type=property, object=properties, list=properties, item=property
        list($name, $type, $object, $list, $item) = self::sanitize($name, $type, $object, $list, $item);
        $fields = [
            self::get_page_query($list, $type, $object),
            //self::get_list_query($list, $type, $object),
            self::get_item_query($item, $type, $object),
        ];
        return $fields;
    }

    /**
     * Get paginated list query field for this object type - see also relay connection for cursor-based
     */
    public static function get_page_query($list, $type, $object)
    {
        return [
            'name' => $list . '_page',
            'type' => xarGraphQL::get_page_type($type),
            'args' => [
                'sort' => Type::string(),
                'offset' => [
                    'type' => Type::int(),
                    'defaultValue' => 0,
                ],
                'limit' => [
                    'type' => Type::int(),
                    'defaultValue' => 20,
                ],
                //'filters' => Type::string(),
            ],
            'resolve' => self::page_query_resolver($type, $object),
        ];
    }

    /**
     * Get the paginated list query resolver for the object type
     */
    public static function page_query_resolver($type, $object = null)
    {
        // when using type config decorator and object_query_resolver
        if (!isset($object)) {
            list($name, $type, $object, $list, $item) = self::sanitize($type);
        }
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php 
            $allowed = array_flip(['sort', 'offset', 'limit', 'filters', 'count']);
            $fields = $info->getFieldSelection(1);
            $args = array_intersect_key($args, $allowed);
            $todo = array_keys(array_diff_key($fields, $allowed));
            //print_r($todo);
            if (array_key_exists('count', $fields)) {
                $params = array('name' => $object);
                $objectlist = DataObjectMaster::getObjectList($params);
                $args['count'] = $objectlist->countItems();
                if (!empty($args['offset']) && !empty($args['count']) && $args['offset'] > $args['count']) {
                    throw new Exception('Invalid offset ' . $args['offset']);
                }
            }
            if (empty($todo)) {
                return $args;
            }
            $list = $todo[0];
            $list_resolver = self::list_query_resolver($type, $object);
            $args[$list] = call_user_func($list_resolver, $rootValue, $args, $context, $info);
            return $args;
        };
        return $resolver;
    }

    /**
     * Get list query field for this object type
     */
    public static function get_list_query($list, $type, $object)
    {
        return [
            'name' => $list,
            'type' => Type::listOf(xarGraphQL::get_type($type)),
            /**
            'args' => [
                'sort' => Type::string(),
                'offset' => [
                    'type' => Type::int(),
                    'defaultValue' => 0,
                ],
                'limit' => [
                    'type' => Type::int(),
                    'defaultValue' => 20,
                ],
                //'filters' => Type::string(),
            ],
             */
            'resolve' => self::list_query_resolver($type, $object),
        ];
    }

    /**
     * Get the list query resolver for the object type
     */
    public static function list_query_resolver($type, $object = null)
    {
        // when using type config decorator and object_query_resolver
        if (!isset($object)) {
            list($name, $type, $object, $list, $item) = self::sanitize($type);
        }
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r($rootValue);
            //$fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            $params = array('name' => $object);
            //$params = array('name' => $object, 'fieldlist' => array_keys($fields));
            //print_r($params);
            $objectlist = DataObjectMaster::getObjectList($params);
            // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php 
            $allowed = array_flip(['sort', 'offset', 'limit', 'filters']);
            $args = array_intersect_key($args, $allowed);
            //print_r($args);
            $params = array();
            if (!empty($args['sort'])) {
                $params['sort'] = array();
                $sorted = explode(',', $args['sort']);
                foreach ($sorted as $sortme) {
                    if (substr($sortme, 0, 1) === '-') {
                        $params['sort'][] = substr($sortme, 1) . ' DESC';
                        continue;
                    }
                    $params['sort'][] = $sortme;
                }
                //$params['sort'] = implode(',', $params['sort']);
            }
            if (!empty($args['offset'])) {
                $params['startnum'] = $args['offset'] + 1;
            }
            if (!empty($args['limit'])) {
                $params['numitems'] = $args['limit'];
            }
            //if (!empty($args['filters'])) {
            //    $params['filters'] = $args['filters'];
            //}
            $items = $objectlist->getItems($params);
            return $items;
        };
        return $resolver;
    }

    /**
     * Get item query field for this object type
     */
    public static function get_item_query($item, $type, $object)
    {
        return [
            'name' => $item,
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'id' => Type::nonNull(Type::id())
            ],
            'resolve' => self::item_query_resolver($type, $object),
        ];
    }

    /**
     * Get the item query resolver for the object type
     */
    public static function item_query_resolver($type, $object = null)
    {
        // when using type config decorator and object_query_resolver
        if (!isset($object)) {
            list($name, $type, $object, $list, $item) = self::sanitize($type);
        }
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r($rootValue);
            $fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            if (empty($args['id'])) {
                throw new Exception('Unknown ' . $type);
            }
            $params = array('name' => $object, 'itemid' => $args['id']);
            //print_r($params);
            $objectitem = DataObjectMaster::getObject($params);
            $itemid = $objectitem->getItem();
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown ' . $type);
            }
            $values = $objectitem->getFieldValues();
            // see objecttype
            if ($object == 'objects') {
                if (array_key_exists('properties', $fields)) {
                    $values['properties'] = $objectitem->getProperties();
                }
                if (array_key_exists('config', $fields) && !empty($objectitem->config)) {
                    //$values['config'] = @unserialize($objectitem->config);
                    $values['config'] = array($objectitem->config);
                }
            }
            return $values;
        };
        return $resolver;
    }

    /**
     * Add to the query resolver for the object type (list, item) - when using BuildSchema
     */
    public static function object_query_resolver($name)
    {
        // call either list_query_resolver or item_query_resolver here depending on $args['id']
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            $type = self::singularize($info->fieldName);
            if (!empty($args['id'])) {
                //print_r($info->parentType->name . "." . $info->fieldName . "[" . $args['id'] . "]");
                $item_resolver = self::item_query_resolver($type);
                return call_user_func($item_resolver, $rootValue, $args, $context, $info);
            }
            //print_r($info->parentType->name . "." . $info->fieldName);
            $list_resolver = self::list_query_resolver($type);
            return call_user_func($list_resolver, $rootValue, $args, $context, $info);
        };
        return $resolver;
    }
}
