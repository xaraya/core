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
 * GraphQL ObjectType and (no) query fields for "access" field = unserialized in "object(s)"
 */
class xarGraphQLAccessType extends ObjectType
{
    public function __construct()
    {
        $config = static::_xar_get_type_config('Access');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'Access property for DD objects item',
            'fields' => [
                'keys' => Type::listOf(Type::string()),
                //'access' => Type::string(),
                //'access' => Type::listOf(xarGraphQL::get_type("keyval")),
                //'access' => xarGraphQL::get_type_list("keyval"),
                'access' => xarGraphQL::get_type("mixed"),
                //'display_access' => Type::listOf(xarGraphQL::get_type("keyval")),
                //'filters' => Type::string(),
                'filters' => xarGraphQL::get_type('serial'),
            ],
            'resolveField' => function ($object, $args, $context, ResolveInfo $info) {
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = array_merge($info->path, ["access field"]);
                }
                if (empty($object)) {
                    return null;
                }
                //print_r($object);
                if ($info->fieldName == 'keys') {
                    return array_keys($object);
                }
                if ($info->fieldName == 'access' && !empty($object['access']) && is_string($object['access'])) {
                    $values = @unserialize($object[$info->fieldName]);
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
