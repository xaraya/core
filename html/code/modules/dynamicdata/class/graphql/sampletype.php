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

/**
 * GraphQL ObjectType and query fields for "sample" dynamicdata object type
 */
class xarGraphQLSampleType extends ObjectType
{
    public static $_xar_name   = 'Sample';
    public static $_xar_type   = 'sample';
    public static $_xar_object = 'sample';
    public static $_xar_list   = 'samples';
    public static $_xar_item   = 'sample';

    public function __construct()
    {
        $config = [
            'name' => self::$_xar_name,
            'fields' => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::string(),
                'age' => Type::int(),
            ],
        ];
        parent::__construct($config);
    }

    public static function _xar_get_query_field($name)
    {
        $fields = [
            self::$_xar_list => [
                'type' => Type::listOf(xarGraphQL::get_type(self::$_xar_type)),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    //print_r($rootValue);
                    //$fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    $args = array('name' => self::$_xar_object);
                    $objectlist = DataObjectMaster::getObjectList($args);
                    $items = $objectlist->getItems();
                    return $items;
                }
            ],
            self::$_xar_item => [
                'type' => xarGraphQL::get_type(self::$_xar_type),
                'args' => [
                    'id' => Type::nonNull(Type::id())
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    //print_r($rootValue);
                    //$fields = $info->getFieldSelection(1);
                    //print_r($fields);
                    //$queryPlan = $info->lookAhead();
                    //print_r($queryPlan->queryPlan());
                    //print_r($queryPlan->subFields('Property'));
                    if (empty($args['id'])) {
                        throw new Exception('Unknown ' . self::$_xar_type);
                    }
                    $args = array('name' => self::$_xar_object, 'itemid' => $args['id']);
                    $object = DataObjectMaster::getObject($args);
                    $itemid = $object->getItem();
                    if ($itemid != $args['itemid']) {
                        throw new Exception('Unknown ' . self::$_xar_type);
                    }
                    $values = $object->getFieldValues();
                    return $values;
                }
            ],
        ];
        return array($name => $fields[$name]);
    }
}
