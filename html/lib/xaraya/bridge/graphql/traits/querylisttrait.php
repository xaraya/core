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
use GraphQL\Type\Definition\ResolveInfo;
use DataObjectFactory;
use Exception;
 
/**
 * For documentation purposes only - available via QueryListTrait
 */
interface QueryListInterface
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
trait QueryListTrait
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
            //'type' => Type::listOf(GraphQLTypes::getType($typename)),
            'type' => GraphQLTypes::getTypeList($typename),
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
        $object ??= GraphQLInflector::pluralize($typename);
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // @checkme don't try to resolve anything further if the result is already cached?
            if (GraphQLHandler::hasCachedData($typename . '_list', $rootValue, $args, $context, $info)) {
                return;
            }
            GraphQLHandler::tracePath(array_merge($info->path, ["list query " . $typename, $args]));
            $fields = $info->getFieldSelection(1);
            if (GraphQLHandler::hasQueryFields($typename)) {
                $fieldlist = GraphQLHandler::getQueryFields($typename);
            } else {
                $fieldlist = array_keys($fields);
            }
            // @checkme original query field definition config
            //$config = $info->fieldDefinition->config;
            //if (array_key_exists('extensions', $config) && !empty($config['extensions']['access'])) {
            //}
            $userId = 0;
            if (GraphQLHandler::hasSecurity($object)) {
                $userId = GraphQLHandler::checkUser($context);
                if (empty($userId)) {
                    throw new Exception('Invalid user');
                }
            }
            $loader = DataObjectFactory::getObjectLoader($object, $fieldlist);
            // set context if available in resolver
            $loader->setContext($context);
            $loader->parseQueryArgs($args);
            $objectlist = $loader->getObjectList();
            if (GraphQLHandler::hasSecurity($object) && !$objectlist->checkAccess('view', 0, $userId)) {
                throw new Exception('Invalid user access');
            }
            $params = $loader->addPagingParams();
            $items = $objectlist->getItems($params);
            //$items = $loader->query($args);
            GraphQLObjects::setObjectRef($object, $objectlist);
            return $items;
        };
        return $resolver;
    }
}
