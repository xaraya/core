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
 * Token GraphQL ObjectType to get an access token
 */
class xarGraphQLTokenType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Token',
            'description' => 'API access token',
            'fields' => [
                'access_token' => ['type' => Type::string()],
                'expiration' => ['type' => Type::string()],
            ],
        ];
        parent::__construct($config);
    }

    // @checkme getting an access token is typically done as a mutation, not a query
    public static function _xar_get_mutation_field($name)
    {
        return [
            'name' => 'getToken',
            'description' => 'Get API access token',
            'type' => xarGraphQL::get_type('token'),
            'args' => [
                'uname' => ['type' => Type::string()],
                'pass' => ['type' => Type::string()],
                'access' => ['type' => Type::string(), 'defaultValue' => 'display'],
            ],
            'resolve' => function ($rootValue, $args) {
                if (empty($args['uname']) || empty($args['pass'])) {
                    throw new Exception('Invalid username or password');
                }
                if (empty($args['access']) || !in_array($args['access'], ['display', 'update', 'create', 'delete', 'admin'])) {
                    throw new Exception('Invalid access');
                }
                //xarSession::init();
                xarMod::init();
                xarUser::init();
                // @checkme unset xarSession role_id if needed, otherwise xarUser::logIn will hit xarUser::isLoggedIn first!?
                // @checkme or call authsystem directly if we don't want/need to support any other authentication modules
                $userId = xarMod::apiFunc('authsystem', 'user', 'authenticate_user', $args);
                if (empty($userId) || $userId == xarUser::AUTH_FAILED) {
                    throw new Exception('Invalid username or password');
                }
                $userInfo = ['userId' => $userId, 'access' => $args['access'], 'created' => time()];
                $token = xarGraphQL::createToken($userInfo);
                $expiration = date('c', time() + xarGraphQL::$tokenExpires);
                return ['access_token' => $token, 'expiration' => $expiration];
            },
        ];
    }
}
