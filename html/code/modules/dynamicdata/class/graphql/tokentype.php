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
use Xaraya\Authentication\AuthToken;

/**
 * Token GraphQL ObjectType to get an access token
 */
class xarGraphQLTokenType extends ObjectType implements xarGraphQLMutationCreateInterface, xarGraphQLMutationDeleteInterface
{
    /** @var array<mixed> */
    public static $_xar_mutations = ['getToken', 'deleteToken'];

    public function __construct()
    {
        $config = static::_xar_get_type_config('Token');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        return [
            'name' => $typename,
            'description' => 'API access token',
            'fields' => [
                'access_token' => ['type' => Type::string()],
                'expiration' => ['type' => Type::string()],
                'role_id' => ['type' => Type::int()],
            ],
        ];
    }

    /**
     * Summary of _xar_get_mutation_fields
     * @return array<mixed>
     */
    public static function _xar_get_mutation_fields()
    {
        $fields = [];
        foreach (static::$_xar_mutations as $kind => $name) {
            $fields[] = static::_xar_get_mutation_field($name, $kind);
        }
        return $fields;
    }

    /**
     * @checkme getting an access token is typically done as a mutation, not a query
     * @param mixed $name
     * @param mixed $kind
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function _xar_get_mutation_field($name, $kind = 'token')
    {
        switch ($name) {
            case 'getToken':
                return static::_xar_get_create_mutation($name);
            case 'deleteToken':
                return static::_xar_get_delete_mutation($name);
            default:
                throw new Exception("Unknown '$kind' mutation '$name'");
        }
    }

    /**
     * Get create mutation field for this object type
     */
    public static function _xar_get_create_mutation($name, $typename = '', $object = null): array
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
            'resolve' => static::_xar_create_mutation_resolver($name),
            /**
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
                $token = AuthToken::createToken($userInfo);
                $expiration = date('c', time() + AuthToken::$tokenExpires);
                return ['access_token' => $token, 'expiration' => $expiration, 'role_id' => $userId];
            },
             */
        ];
    }

    /**
     * Get the create mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_create_mutation_resolver($typename, $object = null): callable
    {
        //$resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
        $resolver = function ($rootValue, $args, $context) {
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
            // @todo use $context
            //xarSession::init();
            xarMod::init();
            xarUser::init();
            // @checkme unset xarSession role_id if needed, otherwise xarUser::logIn will hit xarUser::isLoggedIn first!?
            // @checkme or call authsystem directly if we don't want/need to support any other authentication modules
            $userId = xarMod::apiFunc('authsystem', 'user', 'authenticate_user', $args, $context);
            if (empty($userId) || $userId == xarUser::AUTH_FAILED) {
                throw new Exception('Invalid username or password');
            }
            $userInfo = ['userId' => $userId, 'access' => $args['access'], 'created' => time()];
            $token = AuthToken::createToken($userInfo);
            $expiration = date('c', time() + AuthToken::$tokenExpires);
            return ['access_token' => $token, 'expiration' => $expiration, 'role_id' => $userId];
        };
        return $resolver;
    }

    /**
     * Get delete mutation field for this object type
     */
    public static function _xar_get_delete_mutation($name, $typename = '', $object = null): array
    {
        return [
            'name' => $name,
            'description' => 'Delete API access token',
            'type' => Type::boolean(),
            'args' => [
                'confirm' => ['type' => Type::boolean(), 'defaultValue' => false],
            ],
            'resolve' => static::_xar_delete_mutation_resolver($name),
            /**
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
                $token = AuthToken::getAuthToken($context);
                if (empty($token)) {
                    return true;
                }
                AuthToken::deleteToken($token);
                return true;
            },
             */
        ];
    }

    /**
     * Get the delete mutation resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_delete_mutation_resolver($typename, $object = null): callable
    {
        //$resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($typename, $object) {
        $resolver = function ($rootValue, $args, $context) {
            // disable caching for mutations
            xarGraphQL::$enableCache = false;
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = "deleteToken";
            }
            if (empty($args['confirm'])) {
                return false;
            }
            // see dummytype whoami and graphql checkUser
            $token = AuthToken::getAuthToken($context);
            if (empty($token)) {
                return true;
            }
            AuthToken::deleteToken($token);
            return true;
        };
        return $resolver;
    }
}
