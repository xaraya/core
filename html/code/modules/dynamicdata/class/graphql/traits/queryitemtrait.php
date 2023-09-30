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
 * For documentation purposes only - available via xarGraphQLQueryItemTrait
 */
interface xarGraphQLQueryItemInterface
{
    /**
     * Get item query field for this object type
     * @param mixed $itemname
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_item_query($itemname, $typename, $object): array;
    /**
     * Get the item query resolver for the object type
     * @param mixed $typename
     * @param mixed $object
     * @return callable
     */
    public static function _xar_item_query_resolver($typename, $object = null): callable;
}

/**
 * Trait to handle default item query for dataobjects
 */
trait xarGraphQLQueryItemTrait
{
    /**
     * Get item query field for this object type
     * @param mixed $itemname
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_item_query($itemname, $typename, $object): array
    {
        return [
            'name' => $itemname,
            'description' => 'Get DD ' . $object . ' item',
            'type' => xarGraphQL::get_type($typename),
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            //'extensions' => [
            //    'access' => 'display',
            //],
            'resolve' => static::_xar_item_query_resolver($typename, $object),
        ];
    }

    /**
     * Get the item query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function _xar_item_query_resolver($typename, $object = null): callable
    {
        // when using type config decorator and object_query_resolver
        $object ??= xarGraphQLInflector::pluralize($typename);
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // @checkme don't try to resolve anything further if the result is already cached?
            if (xarGraphQL::has_cached_data($typename . '_item', $rootValue, $args, $context, $info)) {
                return;
            }
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["item query"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['id'])) {
                throw new Exception('Unknown id for type ' . $typename);
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
                throw new Exception('Unknown item for type ' . $typename);
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
                    //$values['properties'] = $objectitem->getProperties();
                    $params = ['objectid' => $itemid];
                    $values['properties'] = DataPropertyMaster::getProperties($params);
                }
                if (array_key_exists('config', $fields) && !empty($objectitem->config)) {
                    //$values['config'] = @unserialize($objectitem->config);
                    $values['config'] = [$objectitem->config];
                }
            }
            xarGraphQL::$object_ref[$object] = & $objectitem;
            return $values;
        };
        return $resolver;
    }
}
