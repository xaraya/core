<?php

/**
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */

namespace Xaraya\Bridge\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Deferred;
use GraphQL\Executor\Executor;
use DataObjectFactory;
use DataPropertyMaster;
use DeferredItemProperty;
use Closure;
use Exception;

/**
 * Build GraphQL ObjectType, query fields and resolvers for generic dynamicdata object type
 */
class BuildType
{
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
        // GraphQLHandler::setTimer('make type ' . $name);
        // name=Property, type=property, object=properties
        [$name, $type, $object] = GraphQLInflector::sanitize($name, $type, $object);
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
        // GraphQLHandler::setTimer('made type ' . $name);
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
        // GraphQLHandler::setTimer('make page type ' . $name);
        // name=Property, type=property, object=properties
        [$name, $type, $object] = GraphQLInflector::sanitize($name, $type, $object);
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
            //$list => Type::listOf(GraphQLTypes::getType($type)),
            $list => GraphQLTypes::getTypeList($type),
        ];
        $newType = new ObjectType([
            'name' => $page,
            'description' => $description,
            'fields' => $fields,
            // use standard default field resolver for _page types: order, offset, ..., [list items]
            //'resolveField' => self::object_field_resolver($type, $object),
        ]);
        // GraphQLHandler::setTimer('made page type ' . $name);
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
        // GraphQLHandler::setTimer('make input type ' . $name);
        // name=Property, type=property, object=properties
        [$name, $type, $object] = GraphQLInflector::sanitize($name, $type, $object);
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
        // GraphQLHandler::setTimer('made input type ' . $name);
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
        // GraphQLHandler::setTimer('get object fields ' . $object);
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
                //$fields[$fieldname] = Type::listOf(GraphQLTypes::getType($typename));
                $fields[$fieldname] = GraphQLTypes::getTypeList($typename);
                //$fields[$fieldname] = GraphQLTypes::getTypeList("mixed");
                continue;
            }
            if ($fieldtype == 'bsonprop') {
                //$fields[$fieldname] = GraphQLTypes::getTypeList("mixed");
                $fields[$fieldname] = GraphQLTypes::getType($typename);
                continue;
            }
            if ($fieldtype == 'basetype') {
                $fields[$fieldname] = $basetypes[$typename];
                continue;
            }
            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in object ' . $object);
        }
        // GraphQLHandler::setTimer('got object fields ' . $object);
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
            //'array' => GraphQLTypes::getType("serial"),
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
        // return self::get_object_fields($object);
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
                $fields[$fieldname] = GraphQLTypes::getInputType($typename);
                continue;
            }
            if ($fieldtype == 'deferitem') {
                $defername = array_shift($fieldspec);
                $fields[$fieldname] = GraphQLTypes::getInputType($typename);
                continue;
            }
            if ($fieldtype == 'deferlist') {
                $defername = array_shift($fieldspec);
                $fields[$fieldname] = GraphQLTypes::getInputTypeList($typename);
                continue;
            }
            if ($fieldtype == 'defermany') {
                $defername = array_shift($fieldspec);
                // @checkme we need the itemid here!
                $fields[$fieldname] = GraphQLTypes::getInputTypeList($typename);
                continue;
            }
            if ($fieldtype == 'typelist') {
                //$fields[$fieldname] = Type::listOf(GraphQLTypes::getType($typename));
                $fields[$fieldname] = GraphQLTypes::getInputTypeList($typename);
                //$fields[$fieldname] = GraphQLTypes::getTypeList("mixed");
                continue;
            }
            if ($fieldtype == 'bsonprop') {
                //$fields[$fieldname] = GraphQLTypes::getInputTypeList("mixed");
                $fields[$fieldname] = GraphQLTypes::getInputType($typename);
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
        GraphQLObjects::loadObjects();
        if (GraphQLObjects::hasFieldSpecs($object) && !$refresh) {
            return GraphQLObjects::getFieldSpecs($object);
        }
        // GraphQLHandler::setTimer('find object fieldspecs ' . $object);
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
        GraphQLObjects::setFieldSpecs($object, $fieldspecs);
        // GraphQLHandler::setTimer('found object fieldspecs ' . $object);
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
        if (GraphQLObjects::hasType($property->objectname)) {
            $typename = GraphQLObjects::getType($property->objectname);
        } else {
            $typename = GraphQLInflector::singularize($property->objectname);
        }
        if (!GraphQLTypes::hasType($typename)) {
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
        // GraphQLHandler::setTimer('get deferred field ' . $fieldname);
        return [
            'name' => $fieldname,
            'type' => ($islist ? GraphQLTypes::getTypeList($typename) : GraphQLTypes::getType($typename)),
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
        // GraphQLHandler::setTimer('get deferred item ' . $fieldname);
        // check if we can identify the type from the objectname and possibly re-use the resolver here
        //$type = "mixed";
        //$type = $property->objectname;
        //if (count($property->fieldlist) > 1) {
        //$typename = self::find_property_typename($property);
        $type = GraphQLTypes::getType($typename);
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
        // GraphQLHandler::setTimer('get deferred list ' . $fieldname);
        // check if we can identify the type from the objectname and possibly re-use the resolver here
        //$type = "mixed";
        //$type = $property->objectname;
        //if (count($property->fieldlist) > 1) {
        //$typename = self::find_property_typename($property);
        //$type = GraphQLTypes::getType($typename);
        $typelist = GraphQLTypes::getTypeList($typename);
        //$typelist = GraphQLTypes::getPageType($type);
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
        // GraphQLHandler::setTimer('get deferred many ' . $fieldname);
        // check if we can identify the type from the objectname and possibly re-use the resolver here
        //$type = "mixed";
        //$type = $property->targetname;
        //if (!empty($property->targetname) && count($property->fieldlist) > 1) {
        //$typename = self::find_property_typename($property);
        //$type = GraphQLTypes::getType($typename);
        $typelist = GraphQLTypes::getTypeList($typename);
        //$typelist = GraphQLTypes::getPageType($type);
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
            $clazz = GraphQLTypes::getTypeClass('basetype');
        } else {
            $clazz = GraphQLTypes::getTypeClass($typename);
        }
        // @todo should we pass along the object instead of the type here?
        return $clazz::deferred_field_resolver($typename, $fieldname, $object);
    }

    /**
     * Get the field resolver for the object type fields
     * @param mixed $type
     * @param mixed $object
     * @return Closure
     */
    public static function object_field_resolver($type, $object = null)
    {
        // @checkme use type classes by default for getSchema()
        return self::default_field_resolver(true);
    }

    /**
     * Get a default field resolver for all type fields - @checkme don't use type classes by default for BuildSchema?
     * @param mixed $useTypeClasses
     * @return Closure
     */
    public static function default_field_resolver($useTypeClasses = true)
    {
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($useTypeClasses) {
            //$context->tracePath(__CLASS__ . '::default_field_resolver: ' . $info->parentType->name . '.' . $info->fieldName, $info->path);

            // @checkme use standard default field resolver for any known types - will we need this?
            if ($info->parentType->isBuiltInType()) {
                $field_resolver = self::find_field_resolver($context);
                return call_user_func($field_resolver, $values, $args, $context, $info);
            }

            $typename = $info->parentType->name;
            $fieldname = $info->fieldName;
            // try finding field resolver for any other types and fields
            $field_resolver = self::find_field_resolver($context, $typename, $fieldname, $useTypeClasses);
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
        //$context->tracePath("use keys field resolver for type $typename field $fieldname");
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname) {
            $context->tracePath(__CLASS__ . '::keys_field_resolver: ' . $typename . '.' . $fieldname);
            if (empty($values)) {
                return;
            }
            if (is_array($values)) {
                return $values[$fieldname] ?? array_keys($values);
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
        //$context->tracePath("use serial field resolver for type $typename field $fieldname");
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname) {
            $context->tracePath(__CLASS__ . '::serial_field_resolver: ' . $typename . '.' . $fieldname);
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
        //$context->tracePath("use bson field resolver for type $typename field $fieldname");
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname) {
            $context->tracePath(__CLASS__ . '::bson_field_resolver: ' . $typename . '.' . $fieldname);
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
        //$context->tracePath("use alias field resolver for type $typename field $fieldname = $fieldalias");
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname, $fieldalias) {
            $context->tracePath(__CLASS__ . '::alias_field_resolver: ' . $typename . '.' . $fieldname);
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
        //$context->tracePath("use keyval field resolver for type $typename field $fieldname");
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname, $fieldalias) {
            $context->tracePath(__CLASS__ . '::keyval_field_resolver: ' . $typename . '.' . $fieldname);
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
        //$context->tracePath("use basetype field resolver for type $typename field $fieldname");
        // @checkme use standard default field resolver here?
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($typename, $fieldname) {
            $context->tracePath(__CLASS__ . '::basetype_field_resolver: ' . $typename . '.' . $fieldname);
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
     * @param mixed $context
     * @param mixed $typename
     * @param mixed $fieldname
     * @param mixed $useTypeClasses
     * @throws \Exception
     * @return mixed
     */
    public static function find_field_resolver($context = null, $typename = '*', $fieldname = '*', $useTypeClasses = true)
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
            // @todo check if type class corresponding to fieldname has overridden *_query_resolver (objecttype)
            // @todo check if field type corresponding to fieldname has specific resolve Fn (tokentype)
            // @checkme not possible to override page/list/item resolvers in child class by type here
            $field_resolver = Queries::query_field_resolver($typename);
            $field_resolvers[$typename]['*'] = $field_resolver;
            $context->tracePath("use query field resolver for type $typename");
            return $field_resolver;
        }

        // use object mutation resolver for mutation type
        if ($typename == 'mutation' && !$useTypeClasses) {
            // @todo check if type class corresponding to fieldname has overridden *_mutation_resolver
            // @todo check if field type corresponding to fieldname has specific resolve Fn (tokentype)
            // @checkme not possible to override create/update/delete resolvers in child class by type here
            $field_resolver = Mutations::mutation_field_resolver($typename);
            $field_resolvers[$typename]['*'] = $field_resolver;
            $context->tracePath("use mutation field resolver for type $typename");
            return $field_resolver;
        }

        // use standard default field resolver for _page types: order, offset, ..., [list items]
        $page_ext = '_page';
        if (str_ends_with($typename, needle: $page_ext)) {
            $field_resolver = $field_resolvers['*']['*'];
            $field_resolvers[$typename]['*'] = $field_resolver;
            $context->tracePath("use default field resolver for page type $typename");
            return $field_resolver;
        }

        // check for existing class with field resolver(s)?
        if ($useTypeClasses && empty($type_checked[$typename]) && array_key_exists($typename, GraphQLTypes::getTypeMapper())) {
            $type_checked[$typename] = true;
            $clazz = GraphQLTypes::getTypeClass($typename);
            if (!is_subclass_of($clazz, ObjectType::class)) {
                $field_resolver = $field_resolvers['*']['*'];
                $field_resolvers[$typename]['*'] = $field_resolver;
                $context->tracePath("use default field resolver for type $typename = class " . $clazz);
                return $field_resolver;
            }
            //$type_config = $clazz::get_type_config($typename);
            $type_def = self::object_type_definition($typename, $context);
            if ($type_def) {
                // use resolveField for type if available - @checkme shouldn't this come after field resolver(s)?
                if ($type_def->resolveFieldFn) {
                    $field_resolver = $type_def->resolveFieldFn;
                    $field_resolvers[$typename]['*'] = $field_resolver;
                    $context->tracePath("use resolveField fn for type $typename = " . (string) $type_def);
                    return $field_resolver;
                }
                // use resolve function for field if available
                try {
                    foreach ($type_def->getFields() as $field_def) {
                        if ($field_def->resolveFn) {
                            $field_resolvers[$typename][$field_def->name] = $field_def->resolveFn;
                            $context->tracePath("use resolve fn for type $typename = " . (string) $type_def . " field " . $field_def->name);
                        }
                    }
                    if (isset($field_resolvers[$typename][$fieldname])) {
                        return $field_resolvers[$typename][$fieldname];
                    }
                } catch (Exception $e) {
                    $context->tracePath("Unknown fields for type $typename = " . (string) $type_def . ": " . $e->getMessage());
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
        $object = GraphQLInflector::pluralize($typename);
        try {
            $fieldspecs = self::find_object_fieldspecs($object);
        } catch (Exception) {
            $field_resolver = $field_resolvers['*']['*'];
            $field_resolvers[$typename]['*'] = $field_resolver;
            $context->tracePath("Unknown object $object - use default field resolver for type $typename");
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
            $context->tracePath("use deferred field resolver for type $typename field $fieldname");
        } elseif (in_array($fieldtype, ['deferitem', 'deferlist', 'defermany'])) {
            $defername = array_shift($fieldspecs[$fieldname]);
            $field_resolver = self::deferred_field_resolver($defername, $fieldname, $object);
            $context->tracePath("use $fieldtype property resolver for object $object property $fieldname [$defername]");
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
        } elseif (empty($fieldtype) && str_contains($fieldname, '_')) {
            // fieldname starts with _
            if (str_starts_with($fieldname, '_') && !empty($fieldspecs[substr($fieldname, 1)])) {
                $fieldalias = substr($fieldname, 1);
                $field_resolver = self::alias_field_resolver($typename, $fieldname, $fieldalias);
                // fieldname ends with _kv
            } elseif (str_ends_with($fieldname, '_kv') && !empty($fieldspecs[substr($fieldname, 0, -3)])) {
                $fieldalias = substr($fieldname, 0, -3);
                $field_resolver = self::keyval_field_resolver($typename, $fieldname, $fieldalias);
            } else {
                throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in object ' . $object);
            }
        } else {
            $context->tracePath("object field $object.$fieldname", ['fieldspecs' => $fieldspecs[$fieldname]]);
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
        [$name, $type, $object] = GraphQLInflector::sanitize($name, $type, $object);
        // page=properties_page
        $page = $object . '_page';
        // list=properties
        $list = $object;
        // item=property
        $item = $type;
        // @checkme not possible to override page/list/item resolvers in child class by type here
        $fields = [
            QueryPage::get_page_query($page, $type, $object),
            //QueryList::get_list_query($list, $type, $object),
            QueryItem::get_item_query($item, $type, $object),
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
        [$name, $type, $object] = GraphQLInflector::sanitize($name, $type, $object);
        // @checkme not possible to override create/update/delete resolvers in child class by type here
        $fields = [
            //MutationCreate::get_create_mutation('create' . $name, $type, $object),
            //MutationUpdate::get_update_mutation('update' . $name, $type, $object),
            //MutationDelete::get_delete_mutation('delete' . $name, $type, $object),
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
        //$context->tracePath("type resolver $name");
        return self::object_field_resolver($name);
    }

    /**
     * Get the type definition for the object type - used by the default field resolver now
     * @param mixed $name
     * @param mixed $context
     * @return mixed
     */
    public static function object_type_definition($name, $context = null)
    {
        $found = GraphQLTypes::getType($name);
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
        $context->tracePath("object type $name = " . (string) $type);
        return $type;
    }
}
