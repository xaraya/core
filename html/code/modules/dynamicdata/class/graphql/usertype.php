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
use GraphQL\Deferred;

/**
 * GraphQL ObjectType and query fields for "roles_users" dynamicdata object type
 */
class xarGraphQLUserType extends xarGraphQLBaseType
{
    public static $_xar_name   = 'User';
    public static $_xar_type   = 'user';
    public static $_xar_object = 'roles_users';
    public static $_xar_list   = '';
    public static $_xar_item   = '';
    //protected static $_xar_todo = [];
    //protected static $_xar_cache = [];

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object)
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
            //'keys' => Type::listOf(Type::string()),
            //'keys' => [
            //    'type' => Type::listOf(Type::string()),
            //    'resolve' => function ($user, $args, $context, ResolveInfo $info) {
            //        return array_keys($user);
            //    }
            //],
            // other fields that might come in handy somewhere
            //'uname' => Type::string(),
            //'email' => Type::string(),
            //'regdate' => [
            //    'type' => Type::string(),
            //    'resolve' => function ($user, $args, $context, ResolveInfo $info) {
            //        return date(DATE_ATOM, $user['regdate']);
            //    }
            //],
            //'state' => Type::string(),
        ];
        return $fields;
    }

    /**
     * Get the list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_list_query_resolver($type, $object = null)
    {
        //$clazz = xarGraphQL::get_type_class("buildtype");
        //return $clazz::list_query_resolver($type, $object);
        throw new Exception('List queries are disabled in graphql/usertype.php');
    }

    /**
     * Get the item query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_item_query_resolver($type, $object = null)
    {
        //$clazz = xarGraphQL::get_type_class("buildtype");
        //return $clazz::item_query_resolver($type, $object);
        throw new Exception('Item queries are disabled in graphql/usertype.php');
    }

    /**
     * Load values for a deferred field - looking up the user names in this case
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     */
    public static function _xar_load_deferred()
    {
        //print_r("Loading " . implode(",", static::$_xar_todo));
        if (empty(static::$_xar_todo)) {
            return;
        }
        //$idlist = implode(",", static::$_xar_todo);
        //foreach (static::$_xar_todo as $id) {
        //    static::$_xar_cache["$id"] = array('id' => $id, 'name' => "user_" . $id);
        //}
        // @checkme create an extra object with 'username' property, add to extratypes and try extras_page{extras{...}}
        $object = 'roles_users';
        $fieldlist = array('id', 'name');;
        $itemids = array();
        foreach (static::$_xar_todo as $id) {
            $itemids[] = intval($id);
        }
        //$params = array('name' => $object);
        $params = array('name' => $object, 'fieldlist' => $fieldlist);
        //$params = array('name' => $object, 'fieldlist' => $fieldlist, 'itemids' => $itemids);
        $objectlist = DataObjectMaster::getObjectList($params);
        $params = array('itemids' => $itemids);
        //print_r("Params " . var_export($params, true));
        // @todo check why it doesn't select/return only $itemids anymore!?
        static::$_xar_cache = $objectlist->getItems($params);
        //print_r("Found " . var_export(static::$_xar_cache, true));
        static::$_xar_todo = [];
    }
}
