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
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Xaraya\Context\Context;
use xarRoles;
use Exception;

/**
 * Dummy GraphQL ObjectType for standard query fields (hello, echo, schema)
 */
class DummyType extends ObjectType
{
    /** @var array<mixed> */
    public static $_xar_queries = ['hello', 'echo', 'schema', 'whoami', 'context'];

    public function __construct()
    {
        $config = $this->get_type_config('Dummy');
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_type_config($typename, $object = null): array
    {
        return [
            'name' => 'Dummy',
            'fields' => [],
        ];
    }

    /**
     * Summary of get_query_fields
     * @return array<string, mixed>
     */
    public static function get_query_fields(): array
    {
        return [
            'hello' => [
                'name' => 'hello',
                'description' => 'Hello World!',
                'type' => Type::string(),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve hello');
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
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve echo');
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
                'type' => GraphQLTypes::getType('mixed'),
                'args' => [
                    [
                        'name' => 'args',
                        'type' => GraphQLTypes::getType('mixed'),  // or 'serial'
                        'defaultValue' => 'assoc array, string, list, ...',
                    ],
                ],
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve parse');
                    return $args;
                },
            ],
             */
            'schema' => [
                'name' => 'schema',
                'description' => 'Get GraphQL Schema Definition',
                'type' => Type::string(),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve schema');
                    return 'Here is the schema';
                },
            ],
            'whoami' => [
                'name' => 'whoami',
                'description' => 'Display current user',
                'type' => GraphQLTypes::getType('user'),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    /** @var Context $context */
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve whoami');
                    $userId = $context->handler->checkUser($context);
                    if (empty($userId)) {
                        return;
                    }
                    $xar = $context->handler->getServicesClass();
                    $xar->mod()->init();
                    $xar->user()->init();
                    $role = $xar->user()->getRole('id', (int) $userId);
                    $fields = $role->getFieldValues();
                    return ['id' => $fields['id'], 'name' => $fields['name']];
                },
            ],
            'context' => [
                'name' => 'context',
                'description' => 'Show current context',
                'type' => GraphQLTypes::getType('mixed'),
                'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) {
                    /** @var Context $context */
                    $context->tracePath(__CLASS__ . '::get_query_fields: resolve context');
                    $userId = $context->handler->checkUser($context);
                    // return restricted version for non-site admin
                    if (empty($userId)) {
                        return ['userId' => $userId, 'error' => 'Restricted to site admin'];
                    }
                    $xar = $context->handler->getServicesClass();
                    if (!$xar->user($userId)->isSiteAdmin()) {
                        return ['userId' => $userId, 'error' => 'Restricted to site admin'];
                    }
                    return $context->getArrayCopy();
                },
            ],
        ];
    }

    /**
     * Summary of get_query_field
     * @param mixed $name
     * @param mixed $kind
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_query_field($name, $kind = 'dummy'): array
    {
        $fields = static::get_query_fields();
        if (!empty($fields[$name])) {
            return $fields[$name];
        }
        throw new Exception("Unknown '$kind' query '$name'");
    }
}
