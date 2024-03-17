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
 * For documentation purposes only - available via xarGraphQLMutationUpdateTrait
 */
interface xarGraphQLMutationUpdateInterface
{
    /**
     * Get update mutation field for this object type
     * @param mixed $name
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_update_mutation($name, $typename, $object): array;
    /**
     * Get the update mutation resolver for the object type
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function _xar_update_mutation_resolver($typename, $object = null): callable;
}

/**
 * Trait to handle default update mutation for dataobjects
 */
trait xarGraphQLMutationUpdateTrait
{
    /**
     * Get update mutation field for this object type
     * @param mixed $name
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_update_mutation($name, $typename, $object): array
    {
        return [
            'name' => $name,
            'description' => 'Update DD ' . $object . ' item',
            'type' => xarGraphQL::get_type($typename),
            'args' => [
                'input' => xarGraphQL::get_input_type($typename),
            ],
            //'extensions' => [
            //    'access' => 'update',
            //],
            'resolve' => static::_xar_update_mutation_resolver($typename, $object),
        ];
    }

    /**
     * Get the update mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @throws \Exception
     * @return callable
     */
    public static function _xar_update_mutation_resolver($typename, $object = null): callable
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // disable caching for mutations
            xarGraphQL::$enableCache = false;
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["update mutation"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['input']) || empty($args['input']['id'])) {
                throw new Exception('Unknown input for type ' . $typename);
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object, 'itemid' => $args['input']['id']];
            // set context if available in resolver
            $objectitem = DataObjectFactory::getObject($params, $context);
            if (!$objectitem->checkAccess('update', $params['itemid'], $userId)) {
                throw new Exception('Invalid user access');
            }
            $itemid = $objectitem->updateItem($args['input']);
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown item for type ' . $typename);
            }
            $values = $objectitem->getFieldValues();
            return $values;
        };
        return $resolver;
    }
}
