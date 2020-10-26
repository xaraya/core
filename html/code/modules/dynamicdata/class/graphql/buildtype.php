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
 * Build GraphQL ObjectType and query fields for generic dynamicdata object type
 */
//class xarGraphQLBuildType extends ObjectType
class xarGraphQLBuildType
{
    /**
    public static $_xar_name   = 'Sample';
    public static $_xar_type   = 'sample';
    public static $_xar_object = 'sample';
    public static $_xar_list   = 'samples';
    public static $_xar_item   = 'sample';

    public function __construct()
    {
        $config = [
            'name' => self::$_xar_name,
            'fields' => [
                'id' => Type::id(),
                'name' => Type::string(),
                'age' => Type::int(),
            ],
        ];
        parent::__construct($config);
    }
     */

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
        // @todo add fields based on object descriptor
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
            'keys' => Type::listOf(Type::string()),
        ];
        //$args = array('name' => $object, 'numitems' => 1);
        //$objectlist = DataObjectMaster::getObjectList($args);
        //print_r($objectlist->getItems());
        $args = array('name' => $object);
        $objectref = DataObjectMaster::getObject($args);
        $args = array('name' => $object);
        $basetypes = [
            'string' => Type::string(),
            'integer' => Type::int(),
            'decimal' => Type::float(),
            'dropdown' => Type::string(),
        ];
        foreach ($objectref->getProperties() as $key => $property) {
            //print("        '" . $property->name . "' => Type::" . $property->basetype . "(),\n");
            if (!array_key_exists($property->name, $fields)) {
                $fields[$property->name] = $basetypes[$property->basetype];
            }
        }
        $newType = new ObjectType([
            'name' => $name,
            'description' => $description,
            'fields' => $fields,
            'resolveField' => function ($values, $args, $context, ResolveInfo $info) {
                if (is_array($values)) {
                    if ($info->fieldName == 'keys') {
                        return array_keys($values);
                    }
                    if (array_key_exists($info->fieldName, $values)) {
                        return $values[$info->fieldName];
                    }
                }
                if (is_object($values)) {
                    if ($info->fieldName == 'keys') {
                        if (property_exists($values, 'descriptor')) {
                            return array_keys($values->descriptor->getArgs());
                        }
                        return $values->getPublicProperties();
                    }
                    if (property_exists($values, 'properties') && in_array($info->fieldName, $values->properties)) {
                        return $values->properties[$info->fieldName]->getValue();
                    }
                    if (property_exists($values, $info->fieldName)) {
                        return $values->{$info->fieldName};
                    }
                }
            }
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
        } else {
            $object = $type . "s";
        }
        return $object;
    }

    /**
     * Get the root query fields for this object for the GraphQL Query type (list, item)
     */
    public static function get_query_fields($name, $type = null, $object = null, $list = null, $item = null)
    {
        // name=Property, type=property, object=properties, list=properties, item=property
        list($name, $type, $object, $list, $item) = self::sanitize($name, $type, $object, $list, $item);
        $fields = [
            $list => [
                'type' => Type::listOf(xarGraphQL::get_type($type)),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
                    //print_r($rootValue);
                    //$fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    $args = array('name' => $object);
                    $objectlist = DataObjectMaster::getObjectList($args);
                    $items = $objectlist->getItems();
                    return $items;
                }
            ],
            $item => [
                'type' => xarGraphQL::get_type($type),
                'args' => [
                    'id' => Type::nonNull(Type::id())
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($type, $object) {
                    //print_r($rootValue);
                    //$fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    if (empty($args['id'])) {
                        throw new Exception('Unknown ' . $type);
                    }
                    $args = array('name' => $object, 'itemid' => $args['id']);
                    $objectitem = DataObjectMaster::getObject($args);
                    $itemid = $objectitem->getItem();
                    if ($itemid != $args['itemid']) {
                        throw new Exception('Unknown ' . $type);
                    }
                    $values = $objectitem->getFieldValues();
                    return $values;
                }
            ],
        ];
        return $fields;
    }
}
