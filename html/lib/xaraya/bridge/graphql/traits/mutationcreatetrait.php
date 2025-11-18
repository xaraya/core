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

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Xaraya\Context\Context;
use DataObjectFactory;
use Exception;

/**
 * For documentation purposes only - available via MutationCreateTrait
 */
interface MutationCreateInterface
{
    /**
     * Get create mutation field for this object type
     * @param mixed $name
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function get_create_mutation($name, $typename, $object): array;
    /**
     * Get the create mutation resolver for the object type
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function create_mutation_resolver($typename, $object = null): callable;
}

/**
 * Trait to handle default create mutation for dataobjects
 */
trait MutationCreateTrait
{
    /**
     * Get create mutation field for this object type
     * @param mixed $name
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function get_create_mutation($name, $typename, $object): array
    {
        return [
            'name' => $name,
            'description' => 'Create DD ' . $object . ' item',
            'type' => GraphQLTypes::getType($typename),
            'args' => [
                'input' => GraphQLTypes::getInputType($typename),
            ],
            //'extensions' => [
            //    'access' => 'create',
            //],
            'resolve' => static::create_mutation_resolver($typename, $object),
        ];
    }

    /**
     * Get the create mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function create_mutation_resolver($typename, $object = null): callable
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            /** @var Context $context */
            // disable caching for mutations
            $context->handler->enableCache(false);
            $context->tracePath(__CLASS__ . '::create_mutation_resolver: ' . $typename, $info->path);
            $fields = $info->getFieldSelection(1);
            if (empty($args['input'])) {
                throw new Exception('Unknown input for type ' . $typename);
            }
            if (!empty($args['input']['id'])) {
                //$params = array('name' => $object, 'itemid' => $args['input']['id']);
                unset($args['input']['id']);
            }
            $userId = $context->handler->checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object];
            // set context if available in resolver
            $objectitem = DataObjectFactory::getObject($params, $context);
            if (!$objectitem->checkAccess('create', 0, $userId)) {
                throw new Exception('Invalid user access');
            }
            $itemid = $objectitem->createItem($args['input']);
            if (!empty($params['itemid']) && $itemid != $params['itemid']) {
                throw new Exception('Unknown item for type ' . $typename);
            }
            $values = $objectitem->getFieldValues();
            return $values;
        };
        return $resolver;
    }
}

class MutationCreate implements MutationCreateInterface
{
    use MutationCreateTrait;
}
