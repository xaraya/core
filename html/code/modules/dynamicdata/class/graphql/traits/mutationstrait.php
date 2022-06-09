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
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Trait to handle default mutation fields for dataobjects (create, update, delete)
 */
trait xarGraphQLMutationsTrait
{
    use xarGraphQLMutationCreateTrait;
    use xarGraphQLMutationUpdateTrait;
    use xarGraphQLMutationDeleteTrait;

    public static $_xar_type   = '';  // specify in the class using this trait
    public static $_xar_object = '';  // specify in the class using this trait
    public static $_xar_mutations = [];  // specify in the class using this trait

    public static function _xar_get_mutation_fields()
    {
        $fields = [];
        foreach (static::$_xar_mutations as $name) {
            $fields[] = static::_xar_get_mutation_field($name);
        }
        return $fields;
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
     * Add to the mutation resolver for the object type (create, update, delete) - when using BuildSchema
     */
    public static function _xar_mutation_field_resolver($typename = 'mutation')
    {
        // call the right mutation resolver based on the first part of the field name <action><Object>
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            // disable caching for mutations
            xarGraphQL::$enableCache = false;
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["object mutation", $args]);
            }
            $name = $info->fieldName;
            $action = substr($name, 0, 6);
            $type = strtolower(substr($name, 6));
            if ($action === "create") {
                $create_resolver = static::_xar_create_mutation_resolver($type);
                return call_user_func($create_resolver, $rootValue, $args, $context, $info);
            }
            if ($action === "update") {
                $update_resolver = static::_xar_update_mutation_resolver($type);
                return call_user_func($update_resolver, $rootValue, $args, $context, $info);
            }
            if ($action === "delete") {
                $delete_resolver = static::_xar_delete_mutation_resolver($type);
                return call_user_func($delete_resolver, $rootValue, $args, $context, $info);
            }
            throw new Exception('Invalid action ' . $action . ' for mutation ' . $info->fieldName);
        };
        return $resolver;
    }
}
