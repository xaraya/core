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
 * For documentation purposes only - available via xarGraphQLMutationCreateTrait
 */
interface xarGraphQLMutationCreateInterface
{
    public static function _xar_get_create_mutation($name, $typename, $object): array;
    public static function _xar_create_mutation_resolver($typename, $object = null): callable;
}

/**
 * Trait to handle default create mutation for dataobjects
 */
trait xarGraphQLMutationCreateTrait
{
    /**
     * Get create mutation field for this object type
     */
    public static function _xar_get_create_mutation($name, $typename, $object): array
    {
        return [
            'name' => $name,
            'description' => 'Create DD ' . $object . ' item',
            'type' => xarGraphQL::get_type($typename),
            'args' => [
                'input' => xarGraphQL::get_input_type($typename),
            ],
            //'extensions' => [
            //    'access' => 'create',
            //],
            'resolve' => static::_xar_create_mutation_resolver($typename, $object),
        ];
    }

    /**
     * Get the create mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_create_mutation_resolver($typename, $object = null): callable
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // disable caching for mutations
            xarGraphQL::$enableCache = false;
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["create mutation"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['input'])) {
                throw new Exception('Unknown input for type ' . $typename);
            }
            if (!empty($args['input']['id'])) {
                //$params = array('name' => $object, 'itemid' => $args['input']['id']);
                unset($args['input']['id']);
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object];
            $objectitem = DataObjectMaster::getObject($params);
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
