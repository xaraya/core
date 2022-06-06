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

/**
 * Build GraphQL ObjectType, query fields and resolvers for generic dynamicdata object type
 */
//class xarGraphQLBuildType extends ObjectType
class xarGraphQLBuildType
{
    public static $property_id = [];
    public static $known_proptype_ids = [];

    /**
     * Make a generic Object Type for a dynamicdata object type by name = "Module" for modules etc.
     *
     * Use inline style to define Object Type here instead of inheritance
     * https://webonyx.github.io/graphql-php/type-system/object-types/
     */
    public static function make_type($name, $type = null, $object = null, $list = null, $item = null)
    {
        xarGraphQL::setTimer('make type ' . $name);
        // name=Property, type=property, object=properties, list=properties, item=property
        [$name, $type, $object, $list, $item] = self::sanitize($name, $type, $object, $list, $item);
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
    public static function make_page_type($name, $type = null, $object = null, $list = null, $item = null)
    {
        // xarGraphQL::setTimer('make page type ' . $name);
        // name=Property, type=property, object=properties, list=properties, item=property
        [$name, $type, $object, $list, $item] = self::sanitize($name, $type, $object, $list, $item);
        $page = $name . '_Page';
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
    public static function make_input_type($name, $type = null, $object = null, $list = null, $item = null)
    {
        // xarGraphQL::setTimer('make input type ' . $name);
        // name=Property, type=property, object=properties, list=properties, item=property
        [$name, $type, $object, $list, $item] = self::sanitize($name, $type, $object, $list, $item);
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
        return [$name, $type, $object, $list, $item];
    }

    /**
     * Basic pluralize for most common case(s):
     * object -> objects / property -> properties
     */
    public static function pluralize($type)
    {
        if (substr($type, -1) === "y") {
            $object = substr($type, 0, strlen($type) - 1) . "ies";
        } elseif (!in_array($type, ["sample", "api_people", "api_species", "deferchildren", "deferparentchild", "cdcollection"])) {
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
        if ($name === "api_species") {
            $type = $name;
        } elseif (substr($name, -3) === "ies") {
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

    public static function find_object_fieldspecs($object)
    {
        xarGraphQL::loadObjects();
        if (!empty(xarGraphQL::$objectFieldSpecs[$object])) {
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
                self::get_property_id('categories') => 'category',
            ];
        }
        $fieldspecs = array();
        // @todo add fields based on object descriptor?
        foreach ($objectref->getProperties() as $key => $property) {
            if (array_key_exists($property->type, self::$known_proptype_ids)) {
                // @todo should we pass along the object too?
                $typename = self::$known_proptype_ids[$property->type];
                $fieldspecs[$property->name] = array('deferred', $typename);
                continue;
            }
            if ($property->type == self::get_property_id('deferitem')) {
                $typename = self::find_property_typename($property);
                $fieldspecs[$property->name] = array('deferitem', $typename, $property->defername);
                continue;
            }
            if ($property->type == self::get_property_id('deferlist')) {
                $typename = self::find_property_typename($property);
                $fieldspecs[$property->name] = array('deferlist', $typename, $property->defername);
                continue;
            }
            if ($property->type == self::get_property_id('defermany')) {
                $typename = self::find_property_typename($property);
                // @checkme we need the itemid here!
                $fieldspecs[$property->name] = array('defermany', $typename, $property->defername);
                continue;
            }
            if ($property->type == self::get_property_id('configuration')) {
                $typename = "keyval";
                $fieldspecs[$property->name] = array('typelist', $typename);
                continue;
            }
            if (!array_key_exists($property->name, $fieldspecs)) {
                $typename = $property->basetype;
                $fieldspecs[$property->name] = array('basetype', $typename);
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
            $typename = self::singularize($property->objectname);
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

    public static function deferred_field_resolver($typename, $prop_name, $object = null)
    {
        // we only need the type class here, not the type instance
        if (!empty($object)) {
            $clazz = xarGraphQL::get_type_class('basetype');
        } else {
            $clazz = xarGraphQL::get_type_class($typename);
        }
        // @todo should we pass along the object instead of the type here?
        return $clazz::_xar_deferred_field_resolver($typename, $prop_name, $object);
    }

    /**
     * Get the field resolver for the object type fields
     */
    public static function object_field_resolver($type, $object = null)
    {
        // xarGraphQL::setTimer('object field resolver ' . $type);
        // when using type config decorator
        if (!isset($object)) {
            $type = self::singularize($type);
            [$name, $type, $object, $list, $item] = self::sanitize($type);
        }
        $resolver = function ($values, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["object field " . $type, gettype($values), $args]);
            }
            $name = $info->fieldName;
            if (is_array($values)) {
                if ($name == 'keys') {
                    return array_keys($values);
                }
                // @checkme are we sure we'll always have this available?
                // if (empty(xarGraphQL::$object_ref[$object])) {
                //     xarGraphQL::$object_ref[$object] = DataObjectMaster::getObjectList(['name' => $object]);
                // }
                // $property = (xarGraphQL::$object_ref[$object])->properties[$name];
                if (array_key_exists($name, $values)) {
                    // see propertytype
                    if ($name == 'configuration' && is_string($values[$name]) && !empty($values[$name])) {
                        $result = @unserialize($values[$name]);
                        $config = [];
                        foreach ($result as $key => $value) {
                            //if (is_array($value)) {
                            //    $value = json_encode($value);
                            //}
                            $config[] = ['key' => $key, 'value' => $value];
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
                    // @checkme bypass getValue() and get the raw values from the properties to allow deferred handling
                    //return $values->properties[$name]->getValue();
                    return $values->properties[$name]->value;
                }
                if (property_exists($values, $name)) {
                    return $values->{$name};
                }
            }
            //return $values;
        };
        return $resolver;
    }

    public static function dump_query_plan($plan)
    {
        if (!is_array($plan)) {
            return $plan;
        }
        $info = [];
        foreach ($plan as $key => $value) {
            if ($key === 'type' && !is_array($value)) {
                $info[$key] = (string) $value;
            } else {
                $info[$key] = self::dump_query_plan($value);
            }
        }
        return $info;
    }

    public static function check_query_plan($queryType, $rootValue, $args, $context, ResolveInfo $info)
    {
        xarGraphQL::setTimer('check');
        $queryPlan = $info->lookAhead();
        xarGraphQL::$query_plan = $queryPlan;
        xarGraphQL::$type_fields = [];
        foreach ($queryPlan->getReferencedTypes() as $type) {
            xarGraphQL::$type_fields[strtolower($type)] = array_values($queryPlan->subFields($type));
        }
        //xarGraphQL::$paths[] = xarGraphQL::$type_fields;
        $dumpPlan = self::dump_query_plan($queryPlan->queryPlan());
        $queryId = $queryType . '-' . md5(json_encode($dumpPlan));
        if (!empty($args) && is_array($args)) {
            ksort($args);
        }
        // @checkme cache query plan + (later) perhaps result based on args
        if (xarGraphQL::$cache_plan) {
            $cacheKey = xarGraphQL::getCacheKey($queryId);
            if (!empty($cacheKey)) {
                if (!xarGraphQL::isCached($cacheKey)) {
                    xarGraphQL::setCached($cacheKey, $dumpPlan);
                }
                if (xarGraphQL::$cache_data) {
                    // @checkme add current arguments to cacheKey to cache results
                    if (!empty($args)) {
                        $cacheKey .= '-' . md5(json_encode($args));
                    } else {
                        $cacheKey .= '-result';
                    }
                    xarGraphQL::setCacheKey($cacheKey);
                }
            }
        }
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = [
                'queryId' => $queryId,
                'queryType' => $queryType,
                'queryPlan' => $dumpPlan,
                'rootValue' => $rootValue,
                'args' => $args,
            ];
        }
        xarGraphQL::setTimer('plan');
    }

    /**
     * Get the root query fields for this object for the GraphQL Query type (list, item)
     */
    public static function get_query_fields($name, $type = null, $object = null, $list = null, $item = null)
    {
        // name=Property, type=property, object=properties, list=properties, item=property
        [$name, $type, $object, $list, $item] = self::sanitize($name, $type, $object, $list, $item);
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
            'description' => 'Page ' . $object . ' items',
            'type' => xarGraphQL::get_page_type($type),
            'args' => [
                'order' => Type::string(),
                'offset' => [
                    'type' => Type::int(),
                    'defaultValue' => 0,
                ],
                'limit' => [
                    'type' => Type::int(),
                    'defaultValue' => 20,
                ],
                'filter' => Type::listOf(Type::string()),
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
            [$name, $type, $object, $list, $item] = self::sanitize($type);
        }
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (empty(xarGraphQL::$query_plan)) {
                $queryType = $type . '_page';
                self::check_query_plan($queryType, $rootValue, $args, $context, $info);
                // @checkme don't try to resolve anything further if the result is already cached?
                if (xarGraphQL::$cache_data && xarGraphQL::hasCacheKey() && xarGraphQL::isCached(xarGraphQL::getCacheKey())) {
                    return;
                }
            }
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["page query " . $type, $args]);
            }
            // key white-list filter - https://www.php.net/manual/en/function.array-intersect-key.php
            $allowed = array_flip(['order', 'offset', 'limit', 'filter', 'count']);
            $fields = $info->getFieldSelection(1);
            $args = array_intersect_key($args, $allowed);
            $todo = array_keys(array_diff_key($fields, $allowed));
            if (array_key_exists('count', $fields)) {
                $args['count'] = true;
            }
            if (empty($todo)) {
                return $args;
            }
            // @checkme we assume that the first field other than the allowed ones is the list we need
            $list = $todo[0];
            if (array_key_exists($type, xarGraphQL::$type_fields)) {
                $fieldlist = xarGraphQL::$type_fields[$type];
            } elseif (!empty($list) && array_key_exists($list, $fields)) {
                $fieldlist = array_keys($fields[$list]);
            } else {
                $fieldlist = array_keys($fields);
            }
            // @checkme original query field definition config
            //$config = $info->fieldDefinition->config;
            //if (array_key_exists('extensions', $config) && !empty($config['extensions']['access'])) {
            //}
            $userId = 0;
            if (xarGraphQL::hasSecurity($object)) {
                $userId = xarGraphQL::checkUser($context);
                if (empty($userId)) {
                    throw new Exception('Invalid user');
                }
            }
            $loader = new DataObjectLoader($object, $fieldlist);
            $loader->parseQueryArgs($args);
            $objectlist = $loader->getObjectList();
            if (xarGraphQL::hasSecurity($object) && !$objectlist->checkAccess('view', 0, $userId)) {
                throw new Exception('Invalid user access');
            }
            $params = $loader->addPagingParams();
            $args[$list] = $objectlist->getItems($params);
            //$args[$list] = $loader->query($args);
            /**
            $deferred = [];
            foreach ($fieldlist as $key) {
                if (!empty($objectlist->properties[$key]) && method_exists($objectlist->properties[$key], 'getDeferredData')) {
                    array_push($deferred, $key);
                    // @todo set the fieldlist of the loaders to match what we need here!?
                }
            }
            $allowed = array_flip($fieldlist);
            foreach ($args[$list] as $itemid => $item) {
                // @todo filter out fieldlist in dynamic_data datastore
                //$item = array_intersect_key($item, $allowed);
                foreach ($deferred as $key) {
                    $data = $objectlist->properties[$key]->getDeferredData(['value' => $item[$key] ?? null, '_itemid' => $itemid]);
                    if ($data['value'] && in_array(get_class($objectlist->properties[$key]), ['DeferredListProperty', 'DeferredManyProperty']) && is_array($data['value'])) {
                        $args[$list][$itemid][$key] = array_values($data['value']);
                    } else {
                        $args[$list][$itemid][$key] = $data['value'];
                    }
                }
            }
             */
            if (!empty($args['count'])) {
                $args['count'] = $loader->count;
            }
            xarGraphQL::$object_ref[$object] =& $objectlist;
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
            'description' => 'List ' . $object . ' items',
            //'type' => Type::listOf(xarGraphQL::get_type($type)),
            'type' => xarGraphQL::get_type_list($type),
            /**
            'args' => [
                'order' => Type::string(),
                'offset' => [
                    'type' => Type::int(),
                    'defaultValue' => 0,
                ],
                'limit' => [
                    'type' => Type::int(),
                    'defaultValue' => 20,
                ],
                'filter' => Type::listOf(Type::string()),
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
            [$name, $type, $object, $list, $item] = self::sanitize($type);
        }
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (empty(xarGraphQL::$query_plan)) {
                $queryType = $type . '_list';
                self::check_query_plan($queryType, $rootValue, $args, $context, $info);
                // @checkme don't try to resolve anything further if the result is already cached?
                if (xarGraphQL::$cache_data && xarGraphQL::hasCacheKey() && xarGraphQL::isCached(xarGraphQL::getCacheKey())) {
                    return;
                }
            }
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["list query " . $type, $args]);
            }
            $fields = $info->getFieldSelection(1);
            //if (array_key_exists('id', $fields) && count($fields) < 2) {
            //    return array('id' => $values[$prop_name]);
            //}
            if (array_key_exists($type, xarGraphQL::$type_fields)) {
                $fieldlist = xarGraphQL::$type_fields[$type];
            } else {
                $fieldlist = array_keys($fields);
            }
            // @checkme original query field definition config
            //$config = $info->fieldDefinition->config;
            //if (array_key_exists('extensions', $config) && !empty($config['extensions']['access'])) {
            //}
            $userId = 0;
            if (xarGraphQL::hasSecurity($object)) {
                $userId = xarGraphQL::checkUser($context);
                if (empty($userId)) {
                    throw new Exception('Invalid user');
                }
            }
            $loader = new DataObjectLoader($object, $fieldlist);
            $loader->parseQueryArgs($args);
            $objectlist = $loader->getObjectList();
            if (xarGraphQL::hasSecurity($object) && !$objectlist->checkAccess('view', 0, $userId)) {
                throw new Exception('Invalid user access');
            }
            $params = $loader->addPagingParams();
            $items = $objectlist->getItems($params);
            //$items = $loader->query($args);
            xarGraphQL::$object_ref[$object] =& $objectlist;
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
            'description' => 'Get ' . $object . ' item',
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'id' => Type::nonNull(Type::id()),
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
            [$name, $type, $object, $list, $item] = self::sanitize($type);
        }
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (empty(xarGraphQL::$query_plan)) {
                $queryType = $type . '_item';
                self::check_query_plan($queryType, $rootValue, $args, $context, $info);
                // @checkme don't try to resolve anything further if the result is already cached?
                if (xarGraphQL::$cache_data && xarGraphQL::hasCacheKey() && xarGraphQL::isCached(xarGraphQL::getCacheKey())) {
                    return;
                }
            }
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["item query"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['id'])) {
                throw new Exception('Unknown ' . $type);
            }
            // @checkme original query field definition config
            //$config = $info->fieldDefinition->config;
            //if (array_key_exists('extensions', $config) && !empty($config['extensions']['access'])) {
            //}
            $userId = 0;
            if (xarGraphQL::hasSecurity($object)) {
                $userId = xarGraphQL::checkUser($context);
                if (empty($userId)) {
                    throw new Exception('Invalid user');
                }
            }
            $params = ['name' => $object, 'itemid' => $args['id']];
            $objectitem = DataObjectMaster::getObject($params);
            if (xarGraphQL::hasSecurity($object) && !$objectitem->checkAccess('display', $params['itemid'], $userId)) {
                throw new Exception('Invalid user access');
            }
            $itemid = $objectitem->getItem();
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown ' . $type);
            }
            try {
                // @checkme this throws exception for userlist property when xarUser::init() is not called first
                //$values = $objectitem->getFieldValues();
                // @checkme bypass getValue() and get the raw values from the properties to allow deferred handling
                $values = $objectitem->getFieldValues([], 1);
            } catch (Exception $e) {
                //print_r($e->getMessage());
                $values = ['id' => $args['id']];
            }
            // see objecttype
            if ($object == 'objects') {
                if (array_key_exists('properties', $fields)) {
                    $values['properties'] = $objectitem->getProperties();
                }
                if (array_key_exists('config', $fields) && !empty($objectitem->config)) {
                    //$values['config'] = @unserialize($objectitem->config);
                    $values['config'] = [$objectitem->config];
                }
            }
            xarGraphQL::$object_ref[$object] =& $objectitem;
            return $values;
        };
        return $resolver;
    }

    /**
     * Add to the query resolver for the object type (page, list, item) - when using BuildSchema
     */
    public static function object_query_resolver($name)
    {
        // call either list_query_resolver or item_query_resolver here depending on $args['id']
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["object query", $args]);
            }
            $name = strtolower($info->fieldName);
            $page_ext = '_page';
            if (substr($name, -strlen($page_ext)) === $page_ext) {
                $type = substr($name, 0, strlen($name) - strlen($page_ext));
                $type = self::singularize($type);
                $page_resolver = self::page_query_resolver($type);
                return call_user_func($page_resolver, $rootValue, $args, $context, $info);
            }
            $type = self::singularize($name);
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

    /**
     * Get the root mutation fields for this object for the GraphQL Mutation type (create..., update..., delete...)
     */
    public static function get_mutation_fields($name, $type = null, $object = null, $list = null, $item = null)
    {
        // name=Property, type=property, object=properties, list=properties, item=property
        [$name, $type, $object, $list, $item] = self::sanitize($name, $type, $object, $list, $item);
        $fields = [
            //self::get_create_mutation('create' . $name, $type, $object),
            //self::get_update_mutation('update' . $name, $type, $object),
            //self::get_delete_mutation('delete' . $name, $type, $object),
        ];
        return $fields;
    }

    /**
     * Get create mutation field for this object type
     */
    public static function get_create_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'description' => 'Create ' . $object . ' item',
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'input' => xarGraphQL::get_input_type($type),
            ],
            'resolve' => self::create_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the create mutation resolver for the object type
     */
    public static function create_mutation_resolver($type, $object = null)
    {
        // when using type config decorator and object_mutation_resolver
        //if (!isset($object)) {
        //    list($name, $type, $object, $list, $item) = self::sanitize($type);
        //}
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["create mutation"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['input'])) {
                throw new Exception('Unknown input ' . $type);
            }
            if (!empty($args['input']['id'])) {
                //$params = array('name' => $object, 'itemid' => $args['input']['id']);
                unset($args['input']['id']);
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object];
            $objectitem = DataObjectMaster::getObject($params);
            if (!$objectitem->checkAccess('create', 0, $userId)) {
                throw new Exception('Invalid user access');
            }
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
    public static function get_update_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'description' => 'Update ' . $object . ' item',
            'type' => xarGraphQL::get_type($type),
            'args' => [
                'input' => xarGraphQL::get_input_type($type),
            ],
            'resolve' => self::update_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the update mutation resolver for the object type
     */
    public static function update_mutation_resolver($type, $object = null)
    {
        // when using type config decorator and object_mutation_resolver
        //if (!isset($object)) {
        //    list($name, $type, $object, $list, $item) = self::sanitize($type);
        //}
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["update mutation"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['input']) || empty($args['input']['id'])) {
                throw new Exception('Unknown input ' . $type);
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object, 'itemid' => $args['input']['id']];
            $objectitem = DataObjectMaster::getObject($params);
            if (!$objectitem->checkAccess('update', $params['itemid'], $userId)) {
                throw new Exception('Invalid user access');
            }
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
    public static function get_delete_mutation($name, $type, $object)
    {
        return [
            'name' => $name,
            'description' => 'Delete ' . $object . ' item',
            'type' => Type::nonNull(Type::id()),
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            'resolve' => self::delete_mutation_resolver($type, $object),
        ];
    }

    /**
     * Get the delete mutation resolver for the object type
     */
    public static function delete_mutation_resolver($type, $object = null)
    {
        // when using type config decorator and object_mutation_resolver
        //if (!isset($object)) {
        //    list($name, $type, $object, $list, $item) = self::sanitize($type);
        //}
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["delete mutation"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['id'])) {
                throw new Exception('Unknown id ' . $type);
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object, 'itemid' => $args['id']];
            $objectitem = DataObjectMaster::getObject($params);
            if (!$objectitem->checkAccess('delete', $params['itemid'], $userId)) {
                throw new Exception('Invalid user access');
            }
            $itemid = $objectitem->deleteItem();
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown item ' . $type);
            }
            return $itemid;
        };
        return $resolver;
    }

    /**
     * Add to the mutation resolver for the object type (create, update, delete) - when using BuildSchema
     */
    public static function object_mutation_resolver($name)
    {
        // call the right mutation resolver based on the first part of the field name <action><Object>
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["object mutation", $args]);
            }
            $name = $info->fieldName;
            $action = substr($name, 0, 6);
            $type = strtolower(substr($name, 6));
            if ($action === "create") {
                $create_resolver = self::create_mutation_resolver($type);
                return call_user_func($create_resolver, $rootValue, $args, $context, $info);
            }
            if ($action === "update") {
                $update_resolver = self::update_mutation_resolver($type);
                return call_user_func($update_resolver, $rootValue, $args, $context, $info);
            }
            if ($action === "delete") {
                $delete_resolver = self::delete_mutation_resolver($type);
                return call_user_func($delete_resolver, $rootValue, $args, $context, $info);
            }
            throw new Exception('Invalid action ' . $action . ' for mutation ' . $info->fieldName);
        };
        return $resolver;
    }

    /**
     * Add to the type resolver for the object type - when using BuildSchema
     */
    public static function object_type_resolver($name)
    {
        static $field_resolver = [];
        xarGraphQL::$paths[] = "type resolver $name";
        return self::object_field_resolver($name);

        // call the right resolver based on the type
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($name, &$field_resolver) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["object type $name", $args]);
            }
            if (!isset($field_resolver[$name])) {
                $field_resolver[$name] = self::object_field_resolver($name);
            }
            $field = $info->fieldName;
            if (!empty($field_resolver[$name])) {
                return call_user_func($field_resolver[$name], $rootValue, $args, $context, $info);
            }
            // throw new Exception('Invalid type ' . $name . ' for type ' . $info->fieldName);
        };
        return $resolver;
    }

    /**
     * Get the type definition for the object type - when using BuildSchema
     */
    public static function object_type_definition($name)
    {
        $found = xarGraphQL::get_type($name);
        if (!empty($found)) {
            if (is_string($found)) {
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
