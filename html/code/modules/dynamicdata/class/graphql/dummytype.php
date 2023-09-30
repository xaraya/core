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
    /** @var array<mixed> */
    public static $_xar_queries = ['hello', 'echo', 'schema', 'whoami'];

    public function __construct()
    {
        $config = static::_xar_get_type_config('Dummy');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public static function _xar_get_type_config($typename, $object = null): array
    {
        return [
            'name' => 'Dummy',
            'fields' => [],
        ];
    }

    /**
     * Summary of _xar_get_query_fields
     * @return array<string, mixed>
     */
    public static function _xar_get_query_fields(): array
    {
        return [
            'hello' => [
                'name' => 'hello',
                'description' => 'Hello World!',
                'type' => Type::string(),
                'resolve' => function () {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = "hello";
                    }
                    return 'Hello World!';
                },
            ],
            'echo' => [
                'name' => 'echo',
                'description' => 'Echo Message',
                'type' => Type::string(),
                'args' => [
                    'message' => ['type' => Type::string()],
                ],
                'resolve' => function ($rootValue, $args) {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = "echo";
                    }
                    if (empty($args['message'])) {
                        return $rootValue['prefix'] . 'nothing';
                    } else {
                        return $rootValue['prefix'] . $args['message'];
                    }
                },
            ],
            /**
            'parse' => [
                'name' => 'parse',
                'description' => 'Parse Arguments',
                'type' => xarGraphQL::get_type('mixed'),
                'args' => [
                    [
                        'name' => 'args',
                        'type' => xarGraphQL::get_type('mixed'),  // or 'serial'
                        'defaultValue' => 'assoc array, string, list, ...',
                    ],
                ],
                'resolve' => function ($rootValue, $args) {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = ["parse"];
                    }
                    return $args;
                },
            ],
             */
            'schema' => [
                'name' => 'schema',
                'description' => 'Get GraphQL Schema Definition',
                'type' => Type::string(),
                'resolve' => function () {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = "schema";
                    }
                    return 'Here is the schema';
                },
            ],
            'whoami' => [
                'name' => 'whoami',
                'description' => 'Display current user',
                'type' => xarGraphQL::get_type('user'),
                'resolve' => function ($rootValue, $args, $context) {
                    if (xarGraphQL::$trace_path) {
                        xarGraphQL::$paths[] = "whoami";
                    }
                    $userId = xarGraphQL::checkUser($context);
                    if (empty($userId)) {
                        return;
                    }
                    $role = xarRoles::getRole($userId);
                    $fields = $role->getFieldValues();
                    return ['id' => $fields['id'], 'name' => $fields['name']];
                },
            ],
        ];
    }

    /**
     * Summary of _xar_get_query_field
     * @param mixed $name
     * @param mixed $kind
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function _xar_get_query_field($name, $kind = 'dummy'): array
    {
        $fields = static::_xar_get_query_fields();
        if (!empty($fields[$name])) {
            return $fields[$name];
        }
        throw new Exception("Unknown '$kind' query '$name'");
    }
}
