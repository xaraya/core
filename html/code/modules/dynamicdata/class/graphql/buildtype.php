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
use GraphQL\Deferred;
use GraphQL\Executor\Executor;

/**
 * Build GraphQL ObjectType, query fields and resolvers for generic dynamicdata object type
 */
//class xarGraphQLBuildType extends ObjectType
class xarGraphQLBuildType implements xarGraphQLQueriesInterface, xarGraphQLMutationsInterface
{
    use xarGraphQLQueriesTrait;
    use xarGraphQLMutationsTrait;
    //use xarGraphQLObjectTrait;
    //use xarGraphQLDeferredTrait;
    //use xarGraphQLInputTrait;

    /** @var array<string, int> */
    public static $property_id = [];
    /** @var array<int, string> */
    public static $known_proptype_ids = [];

    /**
     * Make a generic Object Type for a dynamicdata object type by name = "Module" for modules etc.
     *
     * Use inline style to define Object Type here instead of inheritance
     * https://webonyx.github.io/graphql-php/type-system/object-types/
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return ObjectType
     */
    public static function make_type($name, $type = null, $object = null)
    {
        xarGraphQL::setTimer('make type ' . $name);
        // name=Property, type=property, object=properties
        [$name, $type, $object] = xarGraphQLInflector::sanitize($name, $type, $object);
        $description = "$object item";
        // $fields = self::get_object_fields($object);
        $newType = new ObjectType([
            'name' => $name,
            'description' => $description,
            // 'fields' => $fields,
            'fields' => function () use ($object) {
                return self::get_object_fields($object);
            },
            'resolveField' => self::object_field_resolver($type, $object),
        ]);
        // xarGraphQL::setTimer('made type ' . $name);
        return $newType;
    }

    /**
     * Make a generic Object Type with pagination
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return ObjectType
     */
    public static function make_page_type($name, $type = null, $object = null)
    {
        // xarGraphQL::setTimer('make page type ' . $name);
        // name=Property, type=property, object=properties
        [$name, $type, $object] = xarGraphQLInflector::sanitize($name, $type, $object);
        // page=Property_Page
        $page = $name . '_Page';
        // list=properties
        $list = $object;
        $description = "Paginated list of $object items";
        $fields = [
            'order' => Type::string(),
            'offset' => Type::int(),
            'limit' => Type::int(),
            'count' => Type::int(),
            'filter' => Type::listOf(Type::string()),
            //$list => Type::listOf(xarGraphQL::get_type($type)),
            $list => xarGraphQL::get_type_list($type),
        ];
        $newType = new ObjectType([
            'name' => $page,
            'description' => $description,
            'fields' => $fields,
            //'resolveField' => self::object_field_resolver($type, $object),
        ]);
        // xarGraphQL::setTimer('made page type ' . $name);
        return $newType;
    }

    /**
     * Make a generic Input Object Type for create/update mutations
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return InputObjectType
     */
    public static function make_input_type($name, $type = null, $object = null)
    {
        // xarGraphQL::setTimer('make input type ' . $name);
        // name=Property, type=property, object=properties
        [$name, $type, $object] = xarGraphQLInflector::sanitize($name, $type, $object);
        // page=Property_Input
        $input = $name . '_Input';
        $description = "Input for $object item";
        // @todo adapt object fields to InputObjectType where needed, e.g. KeyVal to Mixed?
        // $fields = self::get_input_fields($object);
        $newType = new InputObjectType([
            'name' => $input,
            'description' => $description,
            //'fields' => $fields,
            'fields' => function () use ($object) {
                return self::get_input_fields($object);
            },
            //'parseValue' => self::input_value_parser($type, $object),
        ]);
        // xarGraphQL::setTimer('made input type ' . $name);
        return $newType;
    }

    /**
     * Get the object type fields for this dynamicdata object type
     * @checkme when the query contains some fragments from other types, those are loaded too even if they're unused
     * Using resolve in each object field instead of the overall resolveField means we can't cache this information here
     * before the query plan is even checked
     * @param mixed $object
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_object_fields($object)
    {
        // xarGraphQL::setTimer('get object fields ' . $object);
        $fieldspecs = self::find_object_fieldspecs($object);
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
            'keys' => Type::listOf(Type::string()),
        ];
        $basetypes = self::get_field_basetypes();
        foreach ($fieldspecs as $fieldname => $fieldspec) {
            $fieldtype = array_shift($fieldspec);
            $typename = array_shift($fieldspec);
            if ($fieldtype == 'deferred') {
                // @todo should we pass along the object too?
                $fields[$fieldname] = self::get_deferred_field($fieldname, $typename);
                continue;
            }
            if ($fieldtype == 'deferitem') {
                $defername = array_shift($fieldspec);
                $fields[$fieldname] = self::get_deferred_item($fieldname, $typename, $defername, $object);
                continue;
            }
            if ($fieldtype == 'deferlist') {
                $defername = array_shift($fieldspec);
                $fields[$fieldname] = self::get_deferred_list($fieldname, $typename, $defername, $object);
                continue;
            }
            if ($fieldtype == 'defermany') {
                $defername = array_shift($fieldspec);
                // @checkme we need the itemid here!
                $fields[$fieldname] = self::get_deferred_many($fieldname, $typename, $defername, $object);
                continue;
            }
            if ($fieldtype == 'typelist') {
                //$fields[$fieldname] = Type::listOf(xarGraphQL::get_type($typename));
                $fields[$fieldname] = xarGraphQL::get_type_list($typename);
                //$fields[$fieldname] = xarGraphQL::get_type_list("mixed");
                continue;
            }
            if ($fieldtype == 'bsonprop') {
                //$fields[$fieldname] = xarGraphQL::get_type_list("mixed");
                $fields[$fieldname] = xarGraphQL::get_type($typename);
                continue;
            }
            if ($fieldtype == 'basetype') {
                $fields[$fieldname] = $basetypes[$typename];
                continue;
            }
            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in object ' . $object);
        }
        // xarGraphQL::setTimer('got object fields ' . $object);
        return $fields;
    }

    /**
     * Summary of get_field_basetypes
     * @return array<string, mixed>
     */
    public static function get_field_basetypes()
    {
        return [
            'string' => Type::string(),
            'integer' => Type::int(),
            'decimal' => Type::float(),
            'checkbox' => Type::boolean(),
            'dropdown' => Type::string(),  // @todo use EnumType here?
            'time' => Type::int(),
            //'array' => xarGraphQL::get_type("serial"),
        ];
    }

    /**
     * Get the input type fields for this dynamicdata object type
     * @param mixed $object
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_input_fields($object)
    {
        // return self::_xar_get_object_fields($object);
        $fieldspecs = self::find_object_fieldspecs($object);
        $fields = [
            'id' => Type::id(),  // allow null for create here
            'name' => Type::string(),
        ];
        $basetypes = self::get_field_basetypes();
        foreach ($fieldspecs as $fieldname => $fieldspec) {
            $fieldtype = array_shift($fieldspec);
            $typename = array_shift($fieldspec);
            if ($fieldtype == 'deferred') {
                $fields[$fieldname] = xarGraphQL::get_input_type($typename);
                continue;
            }
            if ($fieldtype == 'deferitem') {
                $defername = array_shift($fieldspec);
                $fields[$fieldname] = xarGraphQL::get_input_type($typename);
                continue;
            }
            if ($fieldtype == 'deferlist') {
                $defername = array_shift($fieldspec);
                $fields[$fieldname] = xarGraphQL::get_input_type_list($typename);
                continue;
            }
            if ($fieldtype == 'defermany') {
                $defername = array_shift($fieldspec);
                // @checkme we need the itemid here!
                $fields[$fieldname] = xarGraphQL::get_input_type_list($typename);
                continue;
            }
            if ($fieldtype == 'typelist') {
                //$fields[$fieldname] = Type::listOf(xarGraphQL::get_type($typename));
                $fields[$fieldname] = xarGraphQL::get_input_type_list($typename);
                //$fields[$fieldname] = xarGraphQL::get_type_list("mixed");
                continue;
            }
            if ($fieldtype == 'bsonprop') {
                //$fields[$fieldname] = xarGraphQL::get_input_type_list("mixed");
                $fields[$fieldname] = xarGraphQL::get_input_type($typename);
                continue;
            }
            if ($fieldtype == 'basetype') {
                $fields[$fieldname] = $basetypes[$typename];
                continue;
            }
            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in input ' . $object);
        }
        return $fields;
    }

    /**
     * Summary of get_property_id
     * @param string $name
     * @return int
     */
    public static function get_property_id($name)
    {
        if (empty(self::$property_id[$name])) {
            $proptypes = DataPropertyMaster::getPropertyTypes();
            foreach ($proptypes as $typeid => $proptype) {
                if ($proptype['name'] == $name) {
                    self::$property_id[$name] = $typeid;
                    break;
                }
            }
        }
        return self::$property_id[$name];
    }

    /**
     * Summary of find_object_fieldspecs
     * @param mixed $object
     * @param mixed $refresh
     * @throws \Exception
     * @return mixed
     */
    public static function find_object_fieldspecs($object, $refresh = false)
    {
        xarGraphQL::loadObjects();
        if (!empty(xarGraphQL::$objectFieldSpecs[$object]) && !$refresh) {
            return xarGraphQL::$objectFieldSpecs[$object];
        }
        xarGraphQL::setTimer('find object fieldspecs ' . $object);
        //$args = array('name' => $object, 'numitems' => 1);
        //$objectlist = DataObjectFactory::getObjectList($args);
        //print_r($objectlist->getItems());
        $params = ['name' => $object];
        $objectref = DataObjectFactory::getObject($params);
        if (!is_object($objectref)) {
            throw new Exception('Invalid object ' . $object);
        }
        if (empty(self::$known_proptype_ids)) {
            self::$known_proptype_ids = [
                self::get_property_id('username') => 'user',
                self::get_property_id('userlist') => 'user',
                self::get_property_id('object') => 'object',
                //self::get_property_id('objectref') => 'object',  // @todo look at configuration
                self::get_property_id('propertyref') => 'property',
                //self::get_property_id('module') => 'module',
                //self::get_property_id('categories') => 'category',
            ];
        }
        $fieldspecs = [];
        // @todo add fields based on object descriptor?
        foreach ($objectref->getProperties() as $key => $property) {
            if (array_key_exists($property->type, self::$known_proptype_ids)) {
                // @todo should we pass along the object too?
                $typename = self::$known_proptype_ids[$property->type];
                $fieldspecs[$property->name] = ['deferred', $typename];
                continue;
            }
            if ($property instanceof DeferredItemProperty) {
                if ($property->type == self::get_property_id('deferitem')) {
                    $typename = self::find_property_typename($property);
                    $fieldspecs[$property->name] = ['deferitem', $typename, $property->defername];
                    continue;
                }
                if ($property->type == self::get_property_id('deferlist')) {
                    $typename = self::find_property_typename($property);
                    $fieldspecs[$property->name] = ['deferlist', $typename, $property->defername];
                    continue;
                }
                if ($property->type == self::get_property_id('defermany')) {
                    $typename = self::find_property_typename($property);
                    // @checkme we need the itemid here!
                    $fieldspecs[$property->name] = ['defermany', $typename, $property->defername];
                    continue;
                }
            }
            if ($property->type == self::get_property_id('configuration')) {
                $typename = "keyval";
                $fieldspecs[$property->name] = ['typelist', $typename];
                continue;
            }
            if ($property->type == self::get_property_id('mongodb_bson')) {
                $typename = "mixed";
                $fieldspecs[$property->name] = ['bsonprop', $typename];
                continue;
            }
            if (!array_key_exists($property->name, $fieldspecs)) {
                $typename = $property->basetype;
                $fieldspecs[$property->name] = ['basetype', $typename];
            }
        }
        xarGraphQL::$objectFieldSpecs[$object] = $fieldspecs;
        xarGraphQL::setTimer('found object fieldspecs ' . $object);
        return $fieldspecs;
    }

    /**
     * Summary of find_property_typename
     * @param mixed $property
     * @return mixed
     */
    public static function find_property_typename($property)
    {
        if (empty($property->objectname)) {
            return "mixed";
        }
        if (!empty(xarGraphQL::$object_type[$property->objectname])) {
            $typename = xarGraphQL::$object_type[$property->objectname];
        } else {
            $typename = xarGraphQLInflector::singularize($property->objectname);
        }
        if (!xarGraphQL::has_type($typename)) {
            $typename = "mixed";
        }
        return $typename;
    }

    /**
     * Summary of get_deferred_field
     * @param mixed $fieldname
     * @param mixed $typename
     * @param mixed $islist
     * @return array<string, mixed>
     */
    public static function get_deferred_field($fieldname, $typename, $islist = false)
    {
        // xarGraphQL::setTimer('get deferred field ' . $fieldname);
        return [
            'name' => $fieldname,
            'type' => ($islist ? xarGraphQL::get_type_list($typename) : xarGraphQL::get_type($typename)),
            // @todo move to resolveField?
            // @todo should we pass along the object instead of the type here?
            'resolve' => self::deferred_field_resolver($typename, $fieldname),
        ];
    }

    /**
     * Summary of get_deferred_item
     * @param mixed $fieldname
     * @param mixed $typename
     * @param mixed $defername
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function get_deferred_item($fieldname, $typename, $defername, $object)
    {
        // xarGraphQL::setTimer('get deferred item ' . $fieldname);
        // check if we can identify the type from the objectname and possibly re-use the resolver here
        //$type = "mixed";
        //$type = $property->objectname;
        //if (count($property->fieldlist) > 1) {
        //$typename = self::find_property_typename($property);
        $type = xarGraphQL::get_type($typename);
        // @checkme use deferred load resolver for deferitem, deferlist, defermany properties here!?
        return [
            'name' => $fieldname,
            'type' => $type,
            // @todo move to resolveField?
            'resolve' => self::deferred_field_resolver($defername, $fieldname, $object),
        ];
    }

    /**
     * Summary of get_deferred_list
     * @param mixed $fieldname
     * @param mixed $typename
     * @param mixed $defername
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function get_deferred_list($fieldname, $typename, $defername, $object)
    {
        // xarGraphQL::setTimer('get deferred list ' . $fieldname);
        // check if we can identify the type from the objectname and possibly re-use the resolver here
        //$type = "mixed";
        //$type = $property->objectname;
        //if (count($property->fieldlist) > 1) {
        //$typename = self::find_property_typename($property);
        //$type = xarGraphQL::get_type($typename);
        $typelist = xarGraphQL::get_type_list($typename);
        //$typelist = xarGraphQL::get_page_type($type);
        // @checkme use deferred load resolver for deferitem, deferlist, defermany properties here!?
        return [
            'name' => $fieldname,
            // @checkme we get back a list of deferred items here
            //'type' => Type::listOf($typename),
            'type' => $typelist,
            // @checkme limit the # of children per itemid when we use data loader?
            'args' => [
                'offset' => [
                    'type' => Type::int(),
                    'defaultValue' => 0,
                ],
                'limit' => [
                    'type' => Type::int(),
                    'defaultValue' => 20,
                ],
            ],
            // @todo move to resolveField?
            'resolve' => self::deferred_field_resolver($defername, $fieldname, $object),
        ];
    }

    /**
     * Summary of get_deferred_many
     * @param mixed $fieldname
     * @param mixed $typename
     * @param mixed $defername
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function get_deferred_many($fieldname, $typename, $defername, $object)
    {
        // xarGraphQL::setTimer('get deferred many ' . $fieldname);
        // check if we can identify the type from the objectname and possibly re-use the resolver here
        //$type = "mixed";
        //$type = $property->targetname;
        //if (!empty($property->targetname) && count($property->fieldlist) > 1) {
        //$typename = self::find_property_typename($property);
        //$type = xarGraphQL::get_type($typename);
        $typelist = xarGraphQL::get_type_list($typename);
        //$typelist = xarGraphQL::get_page_type($type);
        // @checkme use deferred load resolver for deferitem, deferlist, defermany properties here!?
        return [
            'name' => $fieldname,
            // @checkme we get back a list of deferred items here
            //'type' => Type::listOf($type),
            'type' => $typelist,
            // @checkme limit the # of children per itemid when we use data loader?
            'args' => [
                'offset' => [
                    'type' => Type::int(),
                    'defaultValue' => 0,
                ],
                'limit' => [
                    'type' => Type::int(),
                    'defaultValue' => 20,
                ],
            ],
            // @todo move to resolveField?
            // @checkme we use the itemid here, but we need the fieldname to find the property
            //'resolve' => self::deferred_field_resolver($defername, 'id', $object),
            'resolve' => self::deferred_field_resolver($defername, $fieldname, $object),
        ];
    }

    /**
     * Summary of deferred_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $object
     * @return mixed
     */
    public static function deferred_field_resolver($typename, $fieldname, $object = null)
    {
        // we only need the type class here, not the type instance
        if (!empty($object)) {
            $clazz = xarGraphQL::get_type_class('basetype');
        } else {
            $clazz = xarGraphQL::get_type_class($typename);
        }
        // @todo should we pass along the object instead of the type here?
        return $clazz::_xar_deferred_field_resolver($typename, $fieldname, $object);
    }

    /**
     * Get the field resolver for the object type fields
     * @param mixed $type
     * @param mixed $object
     * @return Closure
     */
    public static function object_field_resolver($type, $object = null)
    {
        // @checkme use type classes by default for get_schema()
        return self::default_field_resolver(true);
        /**
        static $field_resolvers = [];
        xarGraphQL::$paths[] = "field resolver $type";

        // xarGraphQL::setTimer('object field resolver ' . $type);
        // when using type config decorator
        if (!isset($object)) {
            $type = xarGraphQLInflector::singularize($type);
            [$name, $type, $object] = xarGraphQLInflector::sanitize($type);
        }
        $field_resolvers[$object] ??= [];

        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($type, $object, &$field_resolvers) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, [$info->parentType->name, "object field " . $object, gettype($values), $args]);
            }

            // @checkme use standard default field resolver for any known types - will we need this?
            if ($info->parentType->isBuiltInType()) {
                $field_resolver = self::find_field_resolver();
                return call_user_func($field_resolver, $values, $args, $context, $info);
            }
            $typename = $info->parentType->name;
            $fieldname = $info->fieldName;
            // @checkme try finding field resolver for any other types and fields - will we need this?
            $field_resolver = self::find_field_resolver($typename, $fieldname);
            return call_user_func($field_resolver, $values, $args, $context, $info);
        };
        return $resolver;
         */
    }

    /**
     * Get a default field resolver for all type fields - @checkme don't use type classes by default for BuildSchema?
     * @param mixed $useTypeClasses
     * @return Closure
     */
    public static function default_field_resolver($useTypeClasses = true)
    {
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($useTypeClasses) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, [$info->parentType->name . '.' . $info->fieldName, gettype($values), $args]);
            }

            // @checkme use standard default field resolver for any known types - will we need this?
            if ($info->parentType->isBuiltInType()) {
                $field_resolver = self::find_field_resolver();
                return call_user_func($field_resolver, $values, $args, $context, $info);
            }

            $typename = $info->parentType->name;
            $fieldname = $info->fieldName;
            // try finding field resolver for any other types and fields
            $field_resolver = self::find_field_resolver($typename, $fieldname, $useTypeClasses);
            return call_user_func($field_resolver, $values, $args, $context, $info);
        };
        return $resolver;
    }

    /**
     * Summary of keys_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @return Closure
     */
    public static function keys_field_resolver($typename, $fieldname)
    {
        xarGraphQL::$paths[] = "use keys field resolver for type $typename field $fieldname";
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname) {
            if (empty($values)) {
                return;
            }
            if (is_array($values)) {
                if (isset($values[$fieldname])) {
                    return $values[$fieldname];
                }
                return array_keys($values);
            }
            if (is_object($values)) {
                if (property_exists($values, $fieldname)) {
                    return $values->{$fieldname};
                }
                if (property_exists($values, 'descriptor')) {
                    return array_keys($values->descriptor->getArgs());
                }
                return $values->getPublicProperties();
            }
            return ["???", gettype($values), "???"];
        };
        return $resolver;
    }

    /**
     * Summary of serial_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @return Closure
     */
    public static function serial_field_resolver($typename, $fieldname)
    {
        xarGraphQL::$paths[] = "use serial field resolver for type $typename field $fieldname";
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname) {
            // @todo handle case where values is object
            if (is_string($values[$fieldname]) && !empty($values[$fieldname])) {
                $result = @unserialize($values[$fieldname]);
                if ($result !== false) {
                    return $result;
                }
            }
            return $values[$fieldname];
        };
        return $resolver;
    }

    /**
     * Summary of bson_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @return Closure
     */
    public static function bson_field_resolver($typename, $fieldname)
    {
        xarGraphQL::$paths[] = "use bson field resolver for type $typename field $fieldname";
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname) {
            // handle case where values is object - see MongoDB\Model\BSONDocument and MongoDB\Model\BSONArray
            if (is_object($values[$fieldname]) && !empty($values[$fieldname])) {
                $result = $values[$fieldname]->jsonSerialize();
                if ($result !== false) {
                    return $result;
                }
            }
            return $values[$fieldname];
        };
        return $resolver;
    }

    /**
     * Summary of alias_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $fieldalias
     * @return Closure
     */
    public static function alias_field_resolver($typename, $fieldname, $fieldalias)
    {
        xarGraphQL::$paths[] = "use alias field resolver for type $typename field $fieldname = $fieldalias";
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname, $fieldalias) {
            if (is_array($values)) {
                return $values[$fieldname] ?? ($values[$fieldalias] ?? null);
            }
            if (is_object($values)) {
                return $values->{$fieldname} ?? ($values->{$fieldalias} ?? null);
            }
        };
        return $resolver;
    }

    /**
     * Summary of keyval_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $fieldalias
     * @return Closure
     */
    public static function keyval_field_resolver($typename, $fieldname, $fieldalias)
    {
        xarGraphQL::$paths[] = "use keyval field resolver for type $typename field $fieldname";
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname, $fieldalias) {
            $result = null;
            if (is_array($values)) {
                $result = $values[$fieldname] ?? ($values[$fieldalias] ?? null);
            }
            if (is_object($values)) {
                $result = $values->{$fieldname} ?? ($values->{$fieldalias} ?? null);
            }
            if (is_string($result) && !empty($result)) {
                $values = @unserialize($result);
            } else {
                $values = $result;
            }
            if (empty($values)) {
                return [];
            }
            if (!is_array($values)) {
                $values = ['' => $values];
            }
            $result = [];
            foreach ($values as $key => $value) {
                //if (is_array($value)) {
                //    $value = json_encode($value);
                //}
                $result[] = ['key' => $key, 'value' => $value];
            }
            return $result;
        };
        return $resolver;
    }

    /**
     * Summary of basetype_field_resolver
     * @param mixed $typename
     * @param mixed $fieldname
     * @return Closure
     */
    public static function basetype_field_resolver($typename, $fieldname)
    {
        xarGraphQL::$paths[] = "use basetype field resolver for type $typename field $fieldname";
        // @checkme use standard default field resolver here?
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname) {
            if (is_array($values)) {
                return $values[$fieldname] ?? null;
            }
            if (is_object($values)) {
                if (property_exists($values, 'properties') && in_array($fieldname, $values->properties)) {
                    // @checkme bypass getValue() and get the raw values from the properties to allow deferred handling
                    //return $values->properties[$fieldname]->getValue();
                    return $values->properties[$fieldname]->value;
                }
                if (property_exists($values, $fieldname)) {
                    return $values->{$fieldname};
                }
            }
        };
        return $resolver;
    }

    /**
     * Find the appropriate field resolver for a particular type and field
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $useTypeClasses
     * @throws \Exception
     * @return mixed
     */
    public static function find_field_resolver($typename = '*', $fieldname = '*', $useTypeClasses = true)
    {
        // initialize with the standard default field resolver
        static $field_resolvers = [
            // default typename
            '*' => [
                // default fieldname
                '*' => [Executor::class, 'defaultFieldResolver'],
            ],
        ];
        static $type_checked = [];

        $typename = strtolower($typename);
        $field_resolvers[$typename] ??= [];

        // use known field resolver first
        //$fieldname = strtolower($fieldname);
        if (isset($field_resolvers[$typename][$fieldname])) {
            return $field_resolvers[$typename][$fieldname];
        }

        // use the same field resolver for all fields of this type, e.g. _page
        if (!empty($field_resolvers[$typename]['*'])) {
            return $field_resolvers[$typename]['*'];
        }

        // use object query resolver for query type
        if ($typename == 'query' && !$useTypeClasses) {
            // @todo check if type class corresponding to fieldname has overridden _xar_*_query_resolver (objecttype)
            // @todo check if field type corresponding to fieldname has specific resolve Fn (tokentype)
            // @checkme not possible to override page/list/item resolvers in child class by type here
            $field_resolver = self::_xar_query_field_resolver($typename);
            $field_resolvers[$typename]['*'] = $field_resolver;
            xarGraphQL::$paths[] = "use query field resolver for type $typename";
            return $field_resolver;
        }

        // use object mutation resolver for mutation type
        if ($typename == 'mutation' && !$useTypeClasses) {
            // @todo check if type class corresponding to fieldname has overridden _xar_*_mutation_resolver
            // @todo check if field type corresponding to fieldname has specific resolve Fn (tokentype)
            // @checkme not possible to override create/update/delete resolvers in child class by type here
            $field_resolver = self::_xar_mutation_field_resolver($typename);
            $field_resolvers[$typename]['*'] = $field_resolver;
            xarGraphQL::$paths[] = "use mutation field resolver for type $typename";
            return $field_resolver;
        }

        // use standard default field resolver for _page types: order, offset, ..., [list items]
        $page_ext = '_page';
        if (substr($typename, -strlen($page_ext)) === $page_ext) {
            $field_resolver = $field_resolvers['*']['*'];
            $field_resolvers[$typename]['*'] = $field_resolver;
            xarGraphQL::$paths[] = "use default field resolver for page type $typename";
            return $field_resolver;
        }

        // check for existing class with field resolver(s)?
        if ($useTypeClasses && empty($type_checked[$typename]) && array_key_exists($typename, xarGraphQL::$type_mapper)) {
            $type_checked[$typename] = true;
            $clazz = xarGraphQL::get_type_class($typename);
            if (!is_subclass_of($clazz, ObjectType::class)) {
                $field_resolver = $field_resolvers['*']['*'];
                $field_resolvers[$typename]['*'] = $field_resolver;
                xarGraphQL::$paths[] = "use default field resolver for type $typename = class " . $clazz;
                return $field_resolver;
            }
            //$type_config = $clazz::_xar_get_type_config($typename);
            $type_def = self::object_type_definition($typename);
            if ($type_def) {
                // use resolveField for type if available - @checkme shouldn't this come after field resolver(s)?
                if ($type_def->resolveFieldFn) {
                    $field_resolver = $type_def->resolveFieldFn;
                    $field_resolvers[$typename]['*'] = $field_resolver;
                    xarGraphQL::$paths[] = "use resolveField fn for type $typename = " . (string) $type_def;
                    return $field_resolver;
                }
                // use resolve function for field if available
                try {
                    foreach ($type_def->getFields() as $field_def) {
                        if ($field_def->resolveFn) {
                            $field_resolvers[$typename][$field_def->name] = $field_def->resolveFn;
                            xarGraphQL::$paths[] = "use resolve fn for type $typename = " . (string) $type_def . " field " . $field_def->name;
                        }
                    }
                    if (isset($field_resolvers[$typename][$fieldname])) {
                        return $field_resolvers[$typename][$fieldname];
                    }
                } catch (Exception $e) {
                    xarGraphQL::$paths[] = "Unknown fields for type $typename = " . (string) $type_def . ": " . $e->getMessage();
                }
            }
        }

        // @checkme handle keys field early
        if ($fieldname == 'keys') {
            $field_resolver = self::keys_field_resolver($typename, $fieldname);
            $field_resolvers[$typename][$fieldname] = $field_resolver;
            return $field_resolver;
        }

        // look in field specs for corresponding object
        $object = xarGraphQLInflector::pluralize($typename);
        try {
            $fieldspecs = self::find_object_fieldspecs($object);
        } catch (Exception $e) {
            $field_resolver = $field_resolvers['*']['*'];
            $field_resolvers[$typename]['*'] = $field_resolver;
            xarGraphQL::$paths[] = "Unknown object $object - use default field resolver for type $typename";
            return $field_resolver;
        }
        if (empty($fieldspecs)) {
            throw new Exception("FieldResolver: Unknown object $object for type $typename");
        }
        if (empty($fieldspecs[$fieldname])) {
            throw new Exception("FieldResolver: Unknown field $fieldname in object $object for type $typename");
        }

        // see resolvers used by get_object_fields
        $fieldtype = array_shift($fieldspecs[$fieldname]);
        $fieldspec = '';
        if ($fieldtype == 'fieldtype') {
            $fieldspec = array_shift($fieldspecs[$fieldname]);
            $fieldtype = array_shift($fieldspecs[$fieldname]);
        }
        $objecttype = array_shift($fieldspecs[$fieldname]);

        if ($fieldtype == 'deferred') {
            $field_resolver = self::deferred_field_resolver($objecttype, $fieldname);
            xarGraphQL::$paths[] = "use deferred field resolver for type $typename field $fieldname";
        } elseif (in_array($fieldtype, ['deferitem', 'deferlist', 'defermany'])) {
            $defername = array_shift($fieldspecs[$fieldname]);
            $field_resolver = self::deferred_field_resolver($defername, $fieldname, $object);
            xarGraphQL::$paths[] = "use $fieldtype property resolver for object $object property $fieldname [$defername]";
        } elseif ($fieldtype == 'typelist') {
            $field_resolver = self::serial_field_resolver($typename, $fieldname);
        } elseif ($fieldtype == 'basetype' && $fieldspec == 'Serial') {
            $field_resolver = self::serial_field_resolver($typename, $fieldname);
        } elseif ($fieldtype == 'bsonprop') {
            $field_resolver = self::bson_field_resolver($typename, $fieldname);
        } elseif ($fieldtype == 'basetype') {
            // @checkme use standard default field resolver here?
            $field_resolver = self::basetype_field_resolver($typename, $fieldname);
            // this field doesn't have a field spec because it is added by the object field resolver
        } elseif (empty($fieldtype) && in_array($fieldname, ["properties", "_objectref"])) {
            // @checkme use standard default field resolver here?
            $field_resolver = self::basetype_field_resolver($typename, $fieldname);
            // this field contains a _ which typically means it refers to another field
        } elseif (empty($fieldtype) && strpos($fieldname, '_') !== false) {
            // fieldname starts with _
            if (substr($fieldname, 0, 1) === '_' && !empty($fieldspecs[substr($fieldname, 1)])) {
                $fieldalias = substr($fieldname, 1);
                $field_resolver = self::alias_field_resolver($typename, $fieldname, $fieldalias);
                // fieldname ends with _kv
            } elseif (substr($fieldname, -3) === '_kv' && !empty($fieldspecs[substr($fieldname, 0, -3)])) {
                $fieldalias = substr($fieldname, 0, -3);
                $field_resolver = self::keyval_field_resolver($typename, $fieldname, $fieldalias);
            } else {
                throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in object ' . $object);
            }
        } else {
            xarGraphQL::$paths[] = ["object field $object.$fieldname", $fieldspecs[$fieldname]];
            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in object ' . $object);
        }

        $field_resolvers[$typename][$fieldname] = $field_resolver;
        return $field_resolver;
    }

    /**
     * Get the root query fields for this object for the GraphQL Query type (list, item)
     * @todo Move to queries trait
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return array<mixed>
     */
    public static function get_query_fields($name, $type = null, $object = null)
    {
        // name=Property, type=property, object=properties
        [$name, $type, $object] = xarGraphQLInflector::sanitize($name, $type, $object);
        // page=properties_page
        $page = $object . '_page';
        // list=properties
        $list = $object;
        // item=property
        $item = $type;
        // @checkme not possible to override page/list/item resolvers in child class by type here
        $fields = [
            self::_xar_get_page_query($page, $type, $object),
            //self::_xar_get_list_query($list, $type, $object),
            self::_xar_get_item_query($item, $type, $object),
        ];
        return $fields;
    }

    /**
     * Get the root mutation fields for this object for the GraphQL Mutation type (create..., update..., delete...)
     * @todo Move to mutations trait
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return array<mixed>
     */
    public static function get_mutation_fields($name, $type = null, $object = null)
    {
        // name=Property, type=property, object=properties
        [$name, $type, $object] = xarGraphQLInflector::sanitize($name, $type, $object);
        // @checkme not possible to override create/update/delete resolvers in child class by type here
        $fields = [
            //self::_xar_get_create_mutation('create' . $name, $type, $object),
            //self::_xar_get_update_mutation('update' . $name, $type, $object),
            //self::_xar_get_delete_mutation('delete' . $name, $type, $object),
        ];
        return $fields;
    }

    /**
     * Add to the type resolver for the object type - when using BuildSchema
     * @param mixed $name
     * @return mixed
     */
    public static function object_type_resolver($name)
    {
        //xarGraphQL::$paths[] = "type resolver $name";
        return self::object_field_resolver($name);
    }

    /**
     * Get the type definition for the object type - used by the default field resolver now
     * @param mixed $name
     * @return mixed
     */
    public static function object_type_definition($name)
    {
        $found = xarGraphQL::get_type($name);
        if (!empty($found)) {
            if (is_string($found)) {
                $type = $found();
            } elseif ($found instanceof Closure) {
                $type = $found();
            } else {
                $type = $found;
            }
            //$type->getFields();
            //$field_resolver = self::object_field_resolver($name);
        } else {
            $type = false;
        }
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = "object type $name = " . (string) $type;
        }
        return $type;
    }
}
