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
 * For documentation purposes only - available via xarGraphQLQueryListTrait
 */
interface xarGraphQLQueryListInterface
{
    /**
     * Get list query field for this object type
     * @param mixed $listname
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_list_query($listname, $typename, $object): array;
    /**
     * Get the list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @return callable
     */
    public static function _xar_list_query_resolver($typename, $object = null): callable;
}

/**
 * Trait to handle default list query for dataobjects
 */
trait xarGraphQLQueryListTrait
{
    /**
     * Get list query field for this object type
     * @param mixed $listname
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_list_query($listname, $typename, $object): array
    {
        return [
            'name' => $listname,
            'description' => 'List DD ' . $object . ' items',
            //'type' => Type::listOf(xarGraphQL::get_type($typename)),
            'type' => xarGraphQL::get_type_list($typename),
            'args' => [
                'order' => Type::string(),
                //'offset' => [
                //    'type' => Type::int(),
                //    'defaultValue' => 0,
                //],
                //'limit' => [
                //    'type' => Type::int(),
                //    'defaultValue' => 20,
                //],
                'filter' => Type::listOf(Type::string()),
            ],
            //'extensions' => [
            //    'access' => 'view',
            //],
            'resolve' => static::_xar_list_query_resolver($typename, $object),
        ];
    }

    /**
     * Get the list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function _xar_list_query_resolver($typename, $object = null): callable
    {
        // when using type config decorator and object_query_resolver
        $object ??= xarGraphQLInflector::pluralize($typename);
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // @checkme don't try to resolve anything further if the result is already cached?
            if (xarGraphQL::has_cached_data($typename . '_list', $rootValue, $args, $context, $info)) {
                return;
            }
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["list query " . $typename, $args]);
            }
            $fields = $info->getFieldSelection(1);
            if (array_key_exists($typename, xarGraphQL::$type_fields)) {
                $fieldlist = xarGraphQL::$type_fields[$typename];
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
            // set context if available in resolver
            $loader->setContext($context);
            $loader->parseQueryArgs($args);
            $objectlist = $loader->getObjectList();
            if (xarGraphQL::hasSecurity($object) && !$objectlist->checkAccess('view', 0, $userId)) {
                throw new Exception('Invalid user access');
            }
            $params = $loader->addPagingParams();
            $items = $objectlist->getItems($params);
            //$items = $loader->query($args);
            xarGraphQL::$object_ref[$object] = & $objectlist;
            return $items;
        };
        return $resolver;
    }
}
