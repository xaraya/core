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
 * Trait to handle default delete mutation for dataobjects
 */
trait xarGraphQLMutationDeleteTrait
{
    /**
     * Get delete mutation field for this object type
     */
    public static function _xar_get_delete_mutation($name, $typename, $object)
    {
        return [
            'name' => $name,
            'description' => 'Delete DD ' . $object . ' item',
            'type' => Type::nonNull(Type::id()),
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            //'extensions' => [
            //    'access' => 'delete',
            //],
            'resolve' => static::_xar_delete_mutation_resolver($typename, $object),
        ];
    }

    /**
     * Get the delete mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_delete_mutation_resolver($typename, $object = null)
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
            // disable caching for mutations
            xarGraphQL::$enableCache = false;
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["delete mutation"]);
            }
            $fields = $info->getFieldSelection(1);
            if (empty($args['id'])) {
                throw new Exception('Unknown id for type ' . $typename);
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $params = ['name' => $object, 'itemid' => $args['id']];
            $objectitem = DataObjectMaster::getObject($params);
            if (!$objectitem->checkAccess('delete', $params['itemid'], $userId)) {
                throw new Exception('Invalid user access');
            }
            $itemid = $objectitem->deleteItem();
            if ($itemid != $params['itemid']) {
                throw new Exception('Unknown item for type ' . $typename);
            }
            return $itemid;
        };
        return $resolver;
    }
}
