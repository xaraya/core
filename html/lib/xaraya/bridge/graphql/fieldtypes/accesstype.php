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

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL ObjectType and (no) query fields for "access" field = unserialized in "object(s)"
 */
class AccessFieldType extends ObjectType
{
    public function __construct()
    {
        $config = $this->get_type_config('Access');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'Access property for DD objects item',
            'fields' => [
                'keys' => Type::listOf(Type::string()),
                //'access' => Type::string(),
                //'access' => Type::listOf(GraphQLTypes::getType("keyval")),
                //'access' => GraphQLTypes::getTypeList("keyval"),
                'access' => GraphQLTypes::getType("mixed"),
                //'display_access' => Type::listOf(GraphQLTypes::getType("keyval")),
                //'filters' => Type::string(),
                'filters' => GraphQLTypes::getType('serial'),
            ],
            'resolveField' => function ($object, $args, $context, ResolveInfo $info) {
                GraphQLHandler::tracePath(array_merge($info->path, ["access field"]));
                if (empty($object)) {
                    return null;
                }
                //print_r($object);
                if ($info->fieldName == 'keys') {
                    return array_keys($object);
                }
                if ($info->fieldName == 'access' && !empty($object['access']) && is_string($object['access'])) {
                    $values = @unserialize((string) $object[$info->fieldName]);
                    return $values;
                    /**
                    $access = array();
                    foreach ($values as $key => $value) {
                        //if (is_array($value)) {
                        //    $value = json_encode($value);
                        //}
                        $access[] = array('key' => $key, 'value' => $value);
                    }
                    return $access;
                     */
                }
                if ($info->fieldName == 'display_access' && !empty($object['display_access']) && is_array($object['display_access'])) {
                    $values = $object[$info->fieldName];
                    $access = [];
                    foreach ($values as $key => $value) {
                        //if (is_array($value)) {
                        //    $value = json_encode($value);
                        //}
                        $access[] = ['key' => $key, 'value' => $value];
                    }
                    return $access;
                }
                if (array_key_exists($info->fieldName, $object)) {
                    return $object[$info->fieldName];
                }
                //return $info->fieldName;
                return null;
            },
        ];
    }
}
