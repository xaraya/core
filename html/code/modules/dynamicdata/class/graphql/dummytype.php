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
 * Dummy GraphQL ObjectType for standard query fields (hello, echo, schema)
 */
class xarGraphQLDummyType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Dummy',
            'fields' => [],
        ];
        parent::__construct($config);
    }

    public static function _xar_get_query_field($name)
    {
        $fields = [
            'hello' => [
                'name' => 'hello',
                'type' => Type::string(),
                'resolve' => function () {
                    return 'Hello World!';
                }
            ],
            'echo' => [
                'name' => 'echo',
                'type' => Type::string(),
                'args' => [
                    'message' => ['type' => Type::string()],
                ],
                'resolve' => function ($rootValue, $args) {
                    if (empty($args['message'])) {
                        return $rootValue['prefix'] . 'nothing';
                    } else {
                        return $rootValue['prefix'] . $args['message'];
                    }
                }
            ],
            'schema' => [
                'name' => 'schema',
                'type' => Type::string(),
                'resolve' => function () {
                    return 'Here is the schema';
                }
            ],
            'whoami' => [
                'name' => 'whoami',
                'type' => xarGraphQL::get_type('user'),
                'resolve' => function () {
                    xarSession::init();
                    //xarUser::init();
                    if (!xarUser::isLoggedIn()) {
                        return array('id' => 0, 'name' => 'Anonymous');
                    }
                    //return array('id' => xarUser::getVar('id'), 'name' => xarUser::getVar('name'));
                    $role = xarRoles::current();
                    $fields = $role->getFieldValues();
                    return array('id' => $fields['id'], 'name' => $fields['name']);
                }
            ],
        ];
        if (!empty($fields[$name])) {
            return $fields[$name];
        }
    }
}
