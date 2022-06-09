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
    public static $_xar_mutations = ['getToken', 'deleteToken'];

    public function __construct()
    {
        $config = static::_xar_get_type_config('Token');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'API access token',
            'fields' => [
                'access_token' => ['type' => Type::string()],
                'expiration' => ['type' => Type::string()],
            ],
        ];
    }

    public static function _xar_get_mutation_fields()
    {
        $fields = [];
        foreach (static::$_xar_mutations as $name) {
            $fields[] = static::_xar_get_mutation_field($name);
        }
        return $fields;
    }

    // @checkme getting an access token is typically done as a mutation, not a query
    public static function _xar_get_mutation_field($name)
    {
        switch ($name) {
            case 'getToken':
                return static::_xar_get_create_mutation($name);
                break;
            case 'deleteToken':
                return static::_xar_get_delete_mutation($name);
                break;
            default:
                throw new Exception('Unknown mutation ' . $name);
        }
    }

    /**
     * Get create mutation field for this object type
     */
    public static function _xar_get_create_mutation($name)
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
                // disable caching for mutations
                xarGraphQL::$enableCache = false;
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = "getToken";
                }
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

    /**
     * Get delete mutation field for this object type
     */
    public static function _xar_get_delete_mutation($name)
    {
        return [
            'name' => $name,
            'description' => 'Delete API access token',
            'type' => Type::boolean(),
            'args' => [
                'confirm' => ['type' => Type::boolean(), 'defaultValue' => false],
            ],
            'resolve' => function ($rootValue, $args, $context) {
                // disable caching for mutations
                xarGraphQL::$enableCache = false;
                if (xarGraphQL::$trace_path) {
                    xarGraphQL::$paths[] = "deleteToken";
                }
                if (empty($args['confirm'])) {
                    return false;
                }
                // see dummytype whoami and graphql checkUser
                $userId = xarGraphQL::checkToken($context['server']);
                if (empty($userId)) {
                    return true;
                }
                xarGraphQL::deleteToken($context['server']);
                return true;
            },
        ];
    }
}
