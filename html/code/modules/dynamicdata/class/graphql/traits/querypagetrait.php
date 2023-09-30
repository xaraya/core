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
 * For documentation purposes only - available via xarGraphQLQueryPageTrait
 */
interface xarGraphQLQueryPageInterface
{
    /**
     * Get paginated list query field for this object type - see also relay connection for cursor-based
     * @param mixed $pagename
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_page_query($pagename, $typename, $object): array;
    /**
     * Get the paginated list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @return callable
     */
    public static function _xar_page_query_resolver($typename, $object = null): callable;
}

/**
 * Trait to handle default page query for dataobjects
 */
trait xarGraphQLQueryPageTrait
{
    /**
     * Get paginated list query field for this object type - see also relay connection for cursor-based
     * @param mixed $pagename
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_page_query($pagename, $typename, $object): array
    {
        return [
            'name' => $pagename,
            'description' => 'Page ' . $object . ' items',
            'type' => xarGraphQL::get_page_type($typename),
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
            'resolve' => static::_xar_page_query_resolver($typename, $object),
        ];
    }

    /**
     * Get the paginated list query resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function _xar_page_query_resolver($typename, $object = null): callable
    {
        // when using type config decorator and object_query_resolver
        $object ??= xarGraphQLInflector::pluralize($typename);
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // @checkme don't try to resolve anything further if the result is already cached?
            if (xarGraphQL::has_cached_data($typename . '_page', $rootValue, $args, $context, $info)) {
                return;
            }
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["page query " . $typename, $args]);
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
            if (array_key_exists($typename, xarGraphQL::$type_fields)) {
                $fieldlist = xarGraphQL::$type_fields[$typename];
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
            xarGraphQL::$object_ref[$object] = & $objectlist;
            return $args;
        };
        return $resolver;
    }
}
