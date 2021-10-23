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
    protected static $_xar_deferred = [];
    public static $_xar_security = true;

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public function __construct($config = null)
    {
        if (empty($config)) {
            $config = [
                'name' => static::$_xar_name,
                'description' => 'DD ' . static::$_xar_object . ' item',
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
        $description = "Input for DD " . static::$_xar_object . " item";
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
        if (!empty(static::$_xar_list) && $name == static::$_xar_list) {
            return static::_xar_get_list_query(static::$_xar_list, static::$_xar_type, static::$_xar_object);
        }
        if (!empty(static::$_xar_item) && $name == static::$_xar_item) {
            return static::_xar_get_item_query(static::$_xar_item, static::$_xar_type, static::$_xar_object);
        }
    }

    /**
     * Get list query field for this object type
     */
    public static function _xar_get_list_query($list, $type, $object)
    {
        return [
            'name' => $list,
            'description' => 'List DD ' . $object . ' items',
            //'type' => Type::listOf(xarGraphQL::get_type($type)),
            'type' => xarGraphQL::get_type_list($type),
            'args' => [
                'order' => Type::string(),
                //'offset' => [
                //    'type' => Type::int(),
                //    'defaultValue' => 0,
                //],
                //'limit' => [
                //    'type' => Type::int(),
                //    'defaultValue' => 20,
                //],
                'filter' => Type::listOf(Type::string()),
            ],
            //'extensions' => [
            //    'access' => 'view',
            //],
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
            'description' => 'Get DD ' . $object . ' item',
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            //'extensions' => [
            //    'access' => 'display',
            //],
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
    public static function _xar_deferred_field_resolver($type, $prop_name, $property = null)
    {
        // @checkme use deferred load resolver for deferitem, deferlist, defermany properties here!?
        if (!empty($property)) {
            $resolver = function ($values, $args, $context, ResolveInfo $info) use ($type, $prop_name, $property) {
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = array_merge($info->path, ["deferred property " . $type, $args]);
                }
                $fields = $info->getFieldSelection(0);
                if (array_key_exists('id', $fields) && count($fields) < 2) {
                    return ['id' => $values[$prop_name]];
                }
                $fieldlist = array_keys($fields);
                if (!empty(xarGraphQL::$object_type[$property->objectname])) {
                    $objtype = strtolower(xarGraphQL::$object_type[$property->objectname]);
                    if (array_key_exists($objtype, xarGraphQL::$type_fields)) {
                        $fieldlist = xarGraphQL::$type_fields[$objtype];
                    }
                }
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = ["add deferred $type " . $values['id'] . " " . implode(',', $fieldlist)];
                }
                $loader = $property->getDeferredLoader();
                // @checkme limit the # of children per itemid when we use data loader?
                // @checkme preserve fieldlist to optimize loading afterwards too
                if ($loader->checkFieldlist && !empty($fieldlist)) {
                    $loader->mergeFieldlist($fieldlist);
                    $loader->parseQueryArgs($args);
                }
                // @todo  how to avoid setting this twice for lists?
                $value = $property->setDataToDefer($values['id'], $values[$prop_name]);

                return new GraphQL\Deferred(function () use ($type, $values, $prop_name, $property) {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = ["get deferred $type " . $values['id']];
                    }
                    $data = $property->getDeferredData(['value' => $values[$prop_name], '_itemid' => $values['id']]);
                    //print_r($data['value']);
                    // @checkme convert deferred data into assoc array or list of assoc array
                    //if (property_exists($property, 'linkname')) {
                    //    return array('count' => 0, 'filter' => array("$type,eq,".$values['id']), $property->objectname => $data['value']);
                    //}
                    return $data['value'];
                });
            };
            return $resolver;
        }
        if (!array_key_exists($type, static::$_xar_deferred)) {
            static::$_xar_deferred[$type] = new DataObjectLoader(static::$_xar_object, ['id']);
            // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
            $getValuesFunc = static::_xar_load_deferred($type);
            if (!empty($getValuesFunc)) {
                static::$_xar_deferred[$type]->setResolver($getValuesFunc);
            }
        }
        // @todo should we pass along the object instead of the type here?
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($type, $prop_name) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["deferred field " . $type, $args]);
            }
            $fields = $info->getFieldSelection(0);
            if (array_key_exists('id', $fields) && count($fields) < 2) {
                return ['id' => $values[$prop_name]];
            }
            if (array_key_exists($type, xarGraphQL::$type_fields)) {
                $fieldlist = xarGraphQL::$type_fields[$type];
            } else {
                $fieldlist = array_keys($fields);
            }
            //if (!in_array('name', $queryPlan->subFields('User'))) {
            //    return array('id' => $values[$prop_name]);
            //}
            $loader = static::$_xar_deferred[$type];
            // @checkme limit the # of children per itemid when we use data loader?
            // @checkme preserve fieldlist to optimize loading afterwards too
            if ($loader->checkFieldlist && !empty($fieldlist)) {
                $loader->mergeFieldlist($fieldlist);
                $loader->parseQueryArgs($args);
            }
            // @todo  handle value array for deferlist
            static::_xar_add_deferred($type, $values[$prop_name], $fieldlist);

            return new GraphQL\Deferred(function () use ($type, $values, $prop_name) {
                return static::_xar_get_deferred($type, $values[$prop_name]);
            });
        };
        return $resolver;
    }

    public static function _xar_add_deferred($type, $id, $fieldlist = null)
    {
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = ["add deferred $type $id " . implode(',', $fieldlist)];
        }
        // @todo handle value array for deferlist
        static::$_xar_deferred[$type]->add($id);
    }

    /**
     * Load values for a deferred field - looking up the user names for example
     *
     * This method *should* be overridden for each specific object type
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     */
    public static function _xar_load_deferred($type)
    {
        // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
    }

    public static function _xar_get_deferred($type, $id)
    {
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = ["get deferred $type $id"];
        }
        // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
        return static::$_xar_deferred[$type]->get($id);
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
            'description' => 'Create DD ' . $object . ' item',
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'input' => xarGraphQL::get_input_type($type),
            ],
            //'extensions' => [
            //    'access' => 'create',
            //],
            'resolve' => static::_xar_create_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the create mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_create_mutation_resolver($type, $object = null)
    {
        $clazz = xarGraphQL::get_type_class("buildtype");
        return $clazz::create_mutation_resolver($type, $object);
    }

    /**
     * Get update mutation field for this object type
     */
    public static function _xar_get_update_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'description' => 'Update DD ' . $object . ' item',
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'input' => xarGraphQL::get_input_type($type),
            ],
            //'extensions' => [
            //    'access' => 'update',
            //],
            'resolve' => static::_xar_update_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the update mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_update_mutation_resolver($type, $object = null)
    {
        $clazz = xarGraphQL::get_type_class("buildtype");
        return $clazz::update_mutation_resolver($type, $object);
    }

    /**
     * Get delete mutation field for this object type
     */
    public static function _xar_get_delete_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'description' => 'Delete DD ' . $object . ' item',
            'type' => Type::nonNull(Type::id()),
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            //'extensions' => [
            //    'access' => 'delete',
            //],
            'resolve' => static::_xar_delete_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the delete mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_delete_mutation_resolver($type, $object = null)
    {
        $clazz = xarGraphQL::get_type_class("buildtype");
        return $clazz::delete_mutation_resolver($type, $object);
    }
}
