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
        $config = [
            'name' => 'Access',
            'fields' => [
                'keys' => Type::listOf(Type::string()),
                //'access' => Type::string(),
                //'access' => Type::listOf(xarGraphQL::get_type("keyval")),
                //'display_access' => Type::listOf(xarGraphQL::get_type("keyval")),
                'filters' => Type::string(),
            ],
            'resolveField' => function ($object, $args, $context, ResolveInfo $info) {
                if (empty($object)) {
                    return null;
                }
                //print_r($object);
                if ($info->fieldName == 'keys') {
                    return array_keys($object);
                }
                if ($info->fieldName == 'access' && !empty($object['access']) && is_string($object['access'])) {
                    $values = @unserialize($object[$info->fieldName]);
                    $access = array();
                    foreach ($values as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $access[] = array('key' => $key, 'value' => $value);
                    }
                    return $access;
                }
                if ($info->fieldName == 'display_access' && !empty($object['display_access']) && is_array($object['display_access'])) {
                    $values = $object[$info->fieldName];
                    $access = array();
                    foreach ($values as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $access[] = array('key' => $key, 'value' => $value);
                    }
                    return $access;
                }
                if (array_key_exists($info->fieldName, $object)) {
                    return $object[$info->fieldName];
                }
                //return $info->fieldName;
                return null;
            }
        ];
        parent::__construct($config);
    }

    public static function _xar_get_query_field($name)
    {
        $fields = [
        ];
        return array($name => $fields[$name]);
    }
}
