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
class xarGraphQLBuildType
{
    use xarGraphQLQueriesTrait;
    use xarGraphQLMutationsTrait;
    //use xarGraphQLObjectTrait;
    //use xarGraphQLDeferredTrait;
    //use xarGraphQLInputTrait;

    public static $property_id = [];
    public static $known_proptype_ids = [];

    /**
     * Make a generic Object Type for a dynamicdata object type by name = "Module" for modules etc.
     *
     * Use inline style to define Object Type here instead of inheritance
     * https://webonyx.github.io/graphql-php/type-system/object-types/
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
            if ($fieldtype == 'basetype') {
                $fields[$fieldname] = $basetypes[$typename];
                continue;
            }
            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in object ' . $object);
        }
        // xarGraphQL::setTimer('got object fields ' . $object);
        return $fields;
    }

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
            if ($fieldtype == 'basetype') {
                $fields[$fieldname] = $basetypes[$typename];
                continue;
            }
            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in input ' . $object);
        }
        return $fields;
    }

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

    public static function find_object_fieldspecs($object, $refresh = false)
    {
        xarGraphQL::loadObjects();
        if (!empty(xarGraphQL::$objectFieldSpecs[$object]) && !$refresh) {
            return xarGraphQL::$objectFieldSpecs[$object];
        }
        xarGraphQL::setTimer('find object fieldspecs ' . $object);
        //$args = array('name' => $object, 'numitems' => 1);
        //$objectlist = DataObjectMaster::getObjectList($args);
        //print_r($objectlist->getItems());
        $params = ['name' => $object];
        $objectref = DataObjectMaster::getObject($params);
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
            if ($property->type == self::get_property_id('configuration')) {
                $typename = "keyval";
                $fieldspecs[$property->name] = ['typelist', $typename];
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
     */
    public static function object_field_resolver($type, $object = null)
    {
        static $field_resolvers = [];
        xarGraphQL::$paths[] = "field resolver $type";

        // xarGraphQL::setTimer('object field resolver ' . $type);
        // when using type config decorator
        if (!isset($object)) {
            $type = xarGraphQLInflector::singularize($type);
            [$name, $type, $object] = xarGraphQLInflector::sanitize($type);
        }
        $field_resolvers[$object] = [];

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
            //$field_resolver = self::find_field_resolver($typename, $fieldname);
            //return call_user_func($field_resolver, $values, $args, $context, $info);

            $fieldspecs = self::find_object_fieldspecs($object);
            if (is_array($values)) {
                if ($fieldname == 'keys') {
                    return array_keys($values);
                }
                // @checkme are we sure we'll always have this available?
                // if (empty(xarGraphQL::$object_ref[$object])) {
                //     xarGraphQL::$object_ref[$object] = DataObjectMaster::getObjectList(['name' => $object]);
                // }
                // $property = (xarGraphQL::$object_ref[$object])->properties[$fieldname];
                if (array_key_exists($fieldname, $values)) {
                    $page_ext = '_page';
                    if (substr($type, -strlen($page_ext)) === $page_ext) {
                        return $values[$fieldname];
                    }
                    // see propertytype
                    if ($fieldname == 'configuration' && is_string($values[$fieldname]) && !empty($values[$fieldname])) {
                        $result = @unserialize($values[$fieldname]);
                        $config = [];
                        foreach ($result as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = ['key' => $key, 'value' => $value];
                        }
                        return $config;
                    }
                    if (empty($field_resolvers[$object][$fieldname])) {
                        $fieldtype = array_shift($fieldspecs[$fieldname]);
                        if ($fieldtype == 'fieldtype') {
                            $fieldtype = array_shift($fieldspecs[$fieldname]);
                            $fieldtype = array_shift($fieldspecs[$fieldname]);
                        }
                        $objecttype = array_shift($fieldspecs[$fieldname]);
                        if ($fieldtype == 'deferred') {
                            $field_resolvers[$object][$fieldname] = self::deferred_field_resolver($objecttype, $fieldname);
                        } elseif (in_array($fieldtype, ['deferitem', 'deferlist', 'defermany'])) {
                            $defername = array_shift($fieldspecs[$fieldname]);
                            $field_resolvers[$object][$fieldname] = self::deferred_field_resolver($defername, $fieldname, $object);
                        } elseif ($fieldtype == 'typelist') {
                            $field_resolvers[$object][$fieldname] = static function ($values, $args, $context, ResolveInfo $info) use ($fieldname) {
                                if (is_string($values[$fieldname]) && !empty($values[$fieldname])) {
                                    $result = @unserialize($values[$fieldname]);
                                    if ($result !== false) {
                                        return $result;
                                    }
                                }
                                return $values[$fieldname];
                            };
                        } elseif ($fieldtype == 'basetype' || (empty($fieldtype) && $fieldname == "properties")) {
                            $field_resolvers[$object][$fieldname] = static function ($values, $args, $context, ResolveInfo $info) use ($fieldname) {
                                return $values[$fieldname];
                            };
                        } else {
                            xarGraphQL::$paths[] = ["object field $object.$fieldname value " . print_r($values[$fieldname], true), $fieldspecs[$fieldname]];
                            throw new Exception('Invalid fieldtype ' . $fieldtype . ' for field ' . $fieldname . ' in input ' . $object);
                        }
                    }
                    return call_user_func($field_resolvers[$object][$fieldname], $values, $args, $context, $info);
                }
            }
            if (is_object($values)) {
                if ($fieldname == 'keys') {
                    if (property_exists($values, 'descriptor')) {
                        return array_keys($values->descriptor->getArgs());
                    }
                    return $values->getPublicProperties();
                }
                if (property_exists($values, 'properties') && in_array($fieldname, $values->properties)) {
                    // @checkme bypass getValue() and get the raw values from the properties to allow deferred handling
                    //return $values->properties[$fieldname]->getValue();
                    return $values->properties[$fieldname]->value;
                }
                if (property_exists($values, $fieldname)) {
                    return $values->{$fieldname};
                }
            }
            //return $values;
        };
        return $resolver;
    }

    /**
     * Get a default field resolver for all type fields
     */
    public static function default_field_resolver()
    {
        $resolver = function ($values, $args, $context, ResolveInfo $info) {
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
            // @checkme try finding field resolver for any other types and fields - will we need this?
            $field_resolver = self::find_field_resolver($typename, $fieldname);
            return call_user_func($field_resolver, $values, $args, $context, $info);
        };
        return $resolver;
    }

    public static function keys_field_resolver($typename, $fieldname)
    {
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

    public static function serial_field_resolver($typename, $fieldname)
    {
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

    public static function alias_field_resolver($typename, $fieldname, $fieldalias)
    {
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

    public static function keyval_field_resolver($typename, $fieldname, $fieldalias)
    {
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($fieldname, $fieldalias) {
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

    public static function basetype_field_resolver($typename, $fieldname)
    {
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
     */
    public static function find_field_resolver($typename = '*', $fieldname = '*')
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

        // use the same field resolver for all fields of this type, e.g. _page
        if (!empty($field_resolvers[$typename]['*'])) {
            return $field_resolvers[$typename]['*'];
        }

        // use known field resolver
        //$fieldname = strtolower($fieldname);
        if (isset($field_resolvers[$typename][$fieldname])) {
            return $field_resolvers[$typename][$fieldname];
        }

        // use object query resolver for query type
        if ($typename == 'query') {
            $field_resolver = self::object_query_resolver($typename);
            $field_resolvers[$typename]['*'] = $field_resolver;
            return $field_resolver;
        }

        // use object mutation resolver for mutation type
        if ($typename == 'mutation') {
            $field_resolver = self::object_mutation_resolver($typename);
            $field_resolvers[$typename]['*'] = $field_resolver;
            return $field_resolver;
        }

        // use standard default field resolver for _page types: order, offset, ..., [list items]
        $page_ext = '_page';
        if (substr($typename, -strlen($page_ext)) === $page_ext) {
            $field_resolver = $field_resolvers['*']['*'];
            $field_resolvers[$typename]['*'] = $field_resolver;
            return $field_resolver;
        }

        // @todo check for existing class with field resolver(s)?
        if (empty($type_checked[$typename]) && array_key_exists($typename, xarGraphQL::$type_mapper)) {
            $type_checked[$typename] = true;
            $clazz = xarGraphQL::get_type_class($typename);
            if (!is_subclass_of($clazz, ObjectType::class)) {
                $field_resolver = $field_resolvers['*']['*'];
                $field_resolvers[$typename]['*'] = $field_resolver;
                xarGraphQL::$paths[] = ["use default field resolver for type $typename = " . $clazz];
                return $field_resolver;
            //} elseif (method_exists($clazz, '_xar_get_type_config')) {
            } else {
                //$type_config = $clazz::_xar_get_type_config($typename);
                $type_def = self::object_type_definition($typename);
                if ($type_def) {
                    if ($type_def->resolveFieldFn) {
                        $field_resolver = $type_def->resolveFieldFn;
                        $field_resolvers[$typename]['*'] = $field_resolver;
                        xarGraphQL::$paths[] = ["use type field resolver for type $typename = " . (string) $type_def];
                        return $field_resolver;
                    }
                    try {
                        foreach ($type_def->getFields() as $field_def) {
                            if ($field_def->resolveFn) {
                                $field_resolvers[$typename][$field_def->name] = $field_def->resolveFn;
                                xarGraphQL::$paths[] = ["set field resolver for type $typename . " . (string) $type_def . " field " . $field_def->name];
                            }
                        }
                        if (isset($field_resolvers[$typename][$fieldname])) {
                            return $field_resolvers[$typename][$fieldname];
                        }
                    } catch (Exception $e) {
                        xarGraphQL::$paths[] = ["Unknown fields for type $typename = " . (string) $type_def . ": " . $e-getMessage()];
                    }
                }
            }
        }

        // @checkme handle keys field
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
            xarGraphQL::$paths[] = ["Unknown object $object - use default field resolver for type $typename"];
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
        if ($fieldtype == 'fieldtype') {
            $fieldtype = array_shift($fieldspecs[$fieldname]);
            $fieldtype = array_shift($fieldspecs[$fieldname]);
        }
        $objecttype = array_shift($fieldspecs[$fieldname]);

        if ($fieldtype == 'deferred') {
            $field_resolver = self::deferred_field_resolver($objecttype, $fieldname);
        } elseif (in_array($fieldtype, ['deferitem', 'deferlist', 'defermany'])) {
            $defername = array_shift($fieldspecs[$fieldname]);
            $field_resolver = self::deferred_field_resolver($defername, $fieldname, $object);
        } elseif ($fieldtype == 'typelist') {
            $field_resolver = self::serial_field_resolver($typename, $fieldname);
        } elseif ($fieldtype == 'basetype' || (empty($fieldtype) && $fieldname == "properties")) {
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
        $fields = [
            self::get_page_query($page, $type, $object),
            //self::get_list_query($list, $type, $object),
            self::get_item_query($item, $type, $object),
        ];
        return $fields;
    }

    /**
     * Get paginated list query field for this object type - see also relay connection for cursor-based
     * @deprecated Moved to queries trait
     */
    public static function get_page_query($page, $type, $object)
    {
        return static::_xar_get_page_query($page, $type, $object);
    }

    /**
     * Get the paginated list query resolver for the object type
     * @deprecated Moved to queries trait
     */
    public static function page_query_resolver($type, $object = null)
    {
        return static::_xar_page_query_resolver($type, $object);
    }

    /**
     * Get list query field for this object type
     * @deprecated Moved to queries trait
     */
    public static function get_list_query($list, $type, $object)
    {
        return static::_xar_get_list_query($list, $type, $object);
    }

    /**
     * Get the list query resolver for the object type
     * @deprecated Moved to queries trait
     */
    public static function list_query_resolver($type, $object = null)
    {
        return static::_xar_list_query_resolver($type, $object);
    }

    /**
     * Get item query field for this object type
     * @deprecated Moved to queries trait
     */
    public static function get_item_query($item, $type, $object)
    {
        return static::_xar_get_item_query($item, $type, $object);
    }

    /**
     * Get the item query resolver for the object type
     * @deprecated Moved to queries trait
     */
    public static function item_query_resolver($type, $object = null)
    {
        return static::_xar_item_query_resolver($type, $object);
    }

    /**
     * Add to the query resolver for the object type (page, list, item) - when using BuildSchema
     * @deprecated Moved to queries trait
     */
    public static function object_query_resolver($name = 'Query')
    {
        return static::_xar_query_field_resolver($name);
    }

    /**
     * Get the root mutation fields for this object for the GraphQL Mutation type (create..., update..., delete...)
     * @todo Move to mutations trait
     */
    public static function get_mutation_fields($name, $type = null, $object = null)
    {
        // name=Property, type=property, object=properties
        [$name, $type, $object] = xarGraphQLInflector::sanitize($name, $type, $object);
        $fields = [
            //self::get_create_mutation('create' . $name, $type, $object),
            //self::get_update_mutation('update' . $name, $type, $object),
            //self::get_delete_mutation('delete' . $name, $type, $object),
        ];
        return $fields;
    }

    /**
     * Get create mutation field for this object type
     * @deprecated Moved to mutations trait
     */
    public static function get_create_mutation($name, $type, $object)
    {
        return static::_xar_get_create_mutation($name, $type, $object);
    }

    /**
     * Get the create mutation resolver for the object type
     * @deprecated Moved to mutations trait
     */
    public static function create_mutation_resolver($type, $object = null)
    {
        return static::_xar_create_mutation_resolver($type, $object);
    }

    /**
     * Get update mutation field for this object type
     * @deprecated Moved to mutations trait
     */
    public static function get_update_mutation($name, $type, $object)
    {
        return static::_xar_get_update_mutation($name, $type, $object);
    }

    /**
     * Get the update mutation resolver for the object type
     * @deprecated Moved to mutations trait
     */
    public static function update_mutation_resolver($type, $object = null)
    {
        return static::_xar_update_mutation_resolver($type, $object);
    }

    /**
     * Get delete mutation field for this object type
     * @deprecated Moved to mutations trait
     */
    public static function get_delete_mutation($name, $type, $object)
    {
        return static::_xar_get_delete_mutation($name, $type, $object);
    }

    /**
     * Get the delete mutation resolver for the object type
     * @deprecated Moved to mutations trait
     */
    public static function delete_mutation_resolver($type, $object = null)
    {
        return static::_xar_delete_mutation_resolver($type, $object);
    }

    /**
     * Add to the mutation resolver for the object type (create, update, delete) - when using BuildSchema
     * @deprecated Moved to mutations trait
     */
    public static function object_mutation_resolver($name = 'Mutation')
    {
        return static::_xar_mutation_field_resolver($name);
    }

    /**
     * Add to the type resolver for the object type - when using BuildSchema
     */
    public static function object_type_resolver($name)
    {
        static $field_resolver = [];
        //xarGraphQL::$paths[] = "type resolver $name";
        return self::object_field_resolver($name);
    }

    /**
     * Get the type definition for the object type - when using BuildSchema - NOT USED
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
