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

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;

/**
 * GraphQL ObjectType and query fields for "base" dynamicdata object type
 */
class BaseObjectType extends ObjectType implements QueriesInterface, MutationsInterface, DataObjectInterface, DeferredInterface, InputObjectInterface
{
    use QueriesTrait;
    use MutationsTrait;
    use DataObjectTrait;
    use DeferredTrait;
    use InputObjectTrait;

    public static string $_xar_name   = '';
    public static string $_xar_type   = '';
    public static string $_xar_object = '';
    public static bool $_xar_security = true;
    /** @var array<mixed> */
    public static $_xar_queries = [];
    /** @var array<mixed> */
    public static $_xar_mutations = [];

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param ?array<string, mixed> $config
     */
    public function __construct($config = null)
    {
        if (empty($config)) {
            $config = $this->get_type_config(static::$_xar_name, static::$_xar_object);
        }
        GraphQLHandler::setTimer('new ' . $config['name']);
        // you need to pass the type config to the parent here, if you want to override the constructor
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
        $object ??= GraphQLInflector::pluralize($typename);
        return [
            'name' => ucwords($typename, '_'),
            'description' => 'DD ' . $object . ' item',
            'fields' => function () use ($object) {
                return $this->get_object_fields($object);
            },
            'resolveField' => $this->object_field_resolver($typename, $object),
        ];
    }

    /**
     * This method *should* be overridden for each specific object type
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_object_fields($object): array
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
        ];
        return $fields;
    }

    /**
     * Get the object field resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return ?callable
     */
    public function object_field_resolver($typename, $object = null): ?callable
    {
        return null;
    }

    /**
     * This method *should* be overridden for each specific object type
     * @param mixed $object
     * @param InputObjectType $newType
     * @return array<string, mixed>
     */
    public static function get_input_fields($object, &$newType): array
    {
        // return static::get_object_fields($object);
        $fields = [
            'id' => Type::id(),  // allow null for create here
            'name' => Type::string(),
        ];
        return $fields;
    }
}
