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
use GraphQL\Type\Definition\InputObjectType;

/**
 * GraphQL ObjectType and query fields for "base" dynamicdata object type
 */
class xarGraphQLBaseType extends ObjectType
{
    public static $_xar_name   = '';
    public static $_xar_type   = '';
    public static $_xar_object = '';
    public static $_xar_list   = '';
    public static $_xar_item   = '';
    protected static $_xar_todo = [];
    protected static $_xar_cache = [];

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public function __construct($config = null)
    {
        if (empty($config)) {
            $config = [
                'name' => static::$_xar_name,
                'fields' => static::_xar_get_object_fields(static::$_xar_object),
            ];
        }
        // you need to pass the type config to the parent here, if you want to override the constructor
        parent::__construct($config);
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object)
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
        ];
        return $fields;
    }

    /**
     * Make a generic Input Object Type for create/update mutations
     */
    public static function _xar_get_input_type()
    {
        $input = static::$_xar_name . '_Input';
        $description = "$input: input " . static::$_xar_type . " type for " . static::$_xar_object . " objects";
        $fields = static::_xar_get_object_fields(static::$_xar_object);
        if (!empty($fields['id'])) {
            //unset($fields['id']);
            $fields['id'] = Type::id();  // allow null for create here
        }
        if (!empty($fields['keys'])) {
            unset($fields['keys']);
        }
        $newType = new InputObjectType([
            'name' => $input,
            'description' => $description,
            'fields' => $fields,
            //'resolveField' => self::object_field_resolver($type, $object),
        ]);
        return $newType;
    }

    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * instead of "self" here - see https://www.php.net/manual/en/language.oop5.late-static-bindings.php
     */
    public static function _xar_get_query_field($name)
    {
        // @todo switch to get_list_query and get_item_query format
        $fields = [];
        if (!empty(static::$_xar_list)) {
            $fields[static::$_xar_list] = [
                'type' => Type::listOf(xarGraphQL::get_type(static::$_xar_type)),
                'resolve' => static::_xar_list_query_resolver(static::$_xar_type, static::$_xar_object),
            ];
        }
        if (!empty(static::$_xar_item)) {
            $fields[static::$_xar_item] = [
                'type' => xarGraphQL::get_type(static::$_xar_type),
                'args' => [
                    'id' => Type::nonNull(Type::id())
                ],
                'resolve' => static::_xar_item_query_resolver(static::$_xar_type, static::$_xar_object),
            ];
        }
        if (!empty($name) && array_key_exists($name, $fields)) {
            return array($name => $fields[$name]);
        }
    }

    /**
     * Get list query field for this object type
     */
    public static function _xar_get_list_query($list, $type, $object)
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
            'resolve' => static::_xar_list_query_resolver($type, $object),
        ];
    }

    /**
     * Get the list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_list_query_resolver($type, $object = null)
    {
        $clazz = xarGraphQL::get_type_class("buildtype");
        return $clazz::list_query_resolver($type, $object);
    }

    /**
     * Get item query field for this object type
     */
    public static function _xar_get_item_query($item, $type, $object)
    {
        return [
            'name' => $item,
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'id' => Type::nonNull(Type::id())
            ],
            'resolve' => static::_xar_item_query_resolver($type, $object),
        ];
    }

    /**
     * Get the item query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_item_query_resolver($type, $object = null)
    {
        $clazz = xarGraphQL::get_type_class("buildtype");
        return $clazz::item_query_resolver($type, $object);
    }

    /**
     * Get the field resolver for a deferred field - looking up the user names for example
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     */
    public static function _xar_deferred_field_resolver($prop_name)
    {
        $resolver = function($values, $args, $context, ResolveInfo $info) use ($prop_name) {
            $fields = $info->getFieldSelection(0);
            if (array_key_exists('id', $fields) && count($fields) < 2) {
                return array('id' => $values[$prop_name]);
            }
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('User'));
            //if (!in_array('name', $queryPlan->subFields('User'))) {
            //    return array('id' => $values[$prop_name]);
            //}
            static::_xar_add_deferred($values[$prop_name], array_keys($fields));

            return new GraphQL\Deferred(function () use ($values, $prop_name) {
                return static::_xar_get_deferred($values[$prop_name]);
            });
        };
        return $resolver;
    }

    public static function _xar_add_deferred($id, $fieldlist = null)
    {
        // @todo preserve fieldlist to optimize loading afterwards too
        // @todo use common [type][id] for todo and cache, or override in inherited class?
        //print_r("Adding $id");
        if (!array_key_exists("$id", static::$_xar_cache) && !in_array($id, static::$_xar_todo)) {
            static::$_xar_todo[] = $id;
        }
    }

    /**
     * Load values for a deferred field - looking up the user names for example
     *
     * This method *should* be overridden for each specific object type
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     */
    public static function _xar_load_deferred()
    {
        //print_r("Loading " . implode(",", static::$_xar_todo));
        if (empty(static::$_xar_todo)) {
            return;
        }
        $idlist = implode(",", static::$_xar_todo);
        //print_r("Loading " . $idlist);
        // @todo lookup usernames
        foreach (static::$_xar_todo as $id) {
            static::$_xar_cache["$id"] = array('id' => $id, 'name' => "override_me_" . $id);
        }
        static::$_xar_todo = [];
    }

    public static function _xar_get_deferred($id)
    {
        if (!empty(static::$_xar_todo)) {
            static::_xar_load_deferred();
        }
        //print_r("Getting $id");
        if (array_key_exists("$id", static::$_xar_cache)) {
            return static::$_xar_cache["$id"];
        }
        return array('id' => $id);
    }

    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * instead of "self" here - see https://www.php.net/manual/en/language.oop5.late-static-bindings.php
     */
    public static function _xar_get_mutation_field($name)
    {
        $action = strtolower(substr($name, 0, 6));
        switch ($action) {
            case 'create':
                return static::_xar_get_create_mutation($name, static::$_xar_type, static::$_xar_object);
                break;
            case 'update':
                return static::_xar_get_update_mutation($name, static::$_xar_type, static::$_xar_object);
                break;
            case 'delete':
                return static::_xar_get_delete_mutation($name, static::$_xar_type, static::$_xar_object);
                break;
            default:
                throw new Exception('Unknown mutation ' . $name);
	}
    }

    /**
     * Get create mutation field for this object type
     */
    public static function _xar_get_create_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'input' => xarGraphQL::get_input_type($type)
            ],
            'resolve' => static::_xar_create_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the create mutation resolver for the object type
     */
    public static function _xar_create_mutation_resolver($type, $object = null)
    {
        // when using type config decorator and object_query_resolver
        //if (!isset($object)) {
        //    list($name, $type, $object, $list, $item) = self::sanitize($type);
        //}
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r($rootValue);
            $fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            if (empty($args['input'])) {
                throw new Exception('Unknown input ' . $type);
            }
            if (!empty($args['input']['id'])) {
                //$params = array('name' => $object, 'itemid' => $args['input']['id']);
                unset($args['input']['id']);
            }
            $params = array('name' => $object);
            //print_r($params);
            $objectitem = DataObjectMaster::getObject($params);
            $itemid = $objectitem->createItem($args['input']);
            if (!empty($params['itemid']) && $itemid != $params['itemid']) {
                throw new Exception('Unknown item ' . $type);
            }
            $values = $objectitem->getFieldValues();
            return $values;
        };
        return $resolver;
    }

    /**
     * Get update mutation field for this object type
     */
    public static function _xar_get_update_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'input' => xarGraphQL::get_input_type($type)
            ],
            'resolve' => static::_xar_update_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the update mutation resolver for the object type
     */
    public static function _xar_update_mutation_resolver($type, $object = null)
    {
        // when using type config decorator and object_query_resolver
        //if (!isset($object)) {
        //    list($name, $type, $object, $list, $item) = self::sanitize($type);
        //}
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r($rootValue);
            $fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            if (empty($args['input']) || empty($args['input']['id'])) {
                throw new Exception('Unknown input ' . $type);
            }
            $params = array('name' => $object, 'itemid' => $args['input']['id']);
            //print_r($params);
            $objectitem = DataObjectMaster::getObject($params);
            $itemid = $objectitem->updateItem($args['input']);
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown item ' . $type);
            }
            $values = $objectitem->getFieldValues();
            return $values;
        };
        return $resolver;
    }

    /**
     * Get delete mutation field for this object type
     */
    public static function _xar_get_delete_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'type' => Type::nonNull(Type::id()),
            'args' => [
                'id' => Type::nonNull(Type::id())
            ],
            'resolve' => static::_xar_delete_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the delete mutation resolver for the object type
     */
    public static function _xar_delete_mutation_resolver($type, $object = null)
    {
        // when using type config decorator and object_query_resolver
        //if (!isset($object)) {
        //    list($name, $type, $object, $list, $item) = self::sanitize($type);
        //}
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            //print_r($rootValue);
            $fields = $info->getFieldSelection(1);
            //print_r($fields);
            //$queryPlan = $info->lookAhead();
            //print_r($queryPlan->queryPlan());
            //print_r($queryPlan->subFields('Property'));
            if (empty($args['id'])) {
                throw new Exception('Unknown id ' . $type);
            }
            $params = array('name' => $object, 'itemid' => $args['id']);
            //print_r($params);
            $objectitem = DataObjectMaster::getObject($params);
            $itemid = $objectitem->deleteItem();
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown item ' . $type);
            }
            return $itemid;
        };
        return $resolver;
    }

}
