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
use GraphQL\Type\Definition\ResolveInfo;
use Xaraya\Context\Context;
use Exception;

/**
 * For documentation purposes only - available via MutationsTrait
 */
interface MutationsInterface extends MutationCreateInterface, MutationUpdateInterface, MutationDeleteInterface
{
    /**
     * Get the mutation fields listed in the $_xar_mutations property of the actual class
     * @return array<mixed>
     */
    public static function get_mutation_fields(): array;
    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * @param mixed $name
     * @param mixed $kind
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_mutation_field($name, $kind = ''): array;
    /**
     * Add to the mutation resolver for the object type (create, update, delete) - when using BuildSchema
     * @param mixed $typename
     * @throws \Exception
     * @return callable
     */
    public static function mutation_field_resolver($typename = 'mutation'): callable;
}

/**
 * Trait to handle default mutation fields for dataobjects (create, update, delete)
 */
trait MutationsTrait
{
    use MutationCreateTrait;
    use MutationUpdateTrait;
    use MutationDeleteTrait;

    public static string $_xar_type   = '';  // specify in the class using this trait
    public static string $_xar_object = '';  // specify in the class using this trait
    /** @var array<mixed> */
    public static $_xar_mutations = [];  // specify in the class using this trait

    /**
     * Get the mutation fields listed in the $_xar_mutations property of the actual class
     * @return array<mixed>
     */
    public static function get_mutation_fields(): array
    {
        $fields = [];
        foreach (static::$_xar_mutations as $kind => $name) {
            if (!empty($name)) {
                $fields[] = static::get_mutation_field($name, $kind);
            }
        }
        return $fields;
    }

    /**
     * This method will be inherited by all specific object types, so it's important to use "static"
     * instead of "self" here - see https://www.php.net/manual/en/language.oop5.late-static-bindings.php
     * @param mixed $name
     * @param mixed $kind
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_mutation_field($name, $kind = ''): array
    {
        if (empty($kind) || is_numeric($kind)) {
            $kind = strtolower(substr($name, 0, 6));
        }
        // allow overriding create/update/delete mutation resolvers for custom type classes using this trait
        return match ($kind) {
            'create' => static::get_create_mutation($name, static::$_xar_type, static::$_xar_object),
            'update' => static::get_update_mutation($name, static::$_xar_type, static::$_xar_object),
            'delete' => static::get_delete_mutation($name, static::$_xar_type, static::$_xar_object),
            default => throw new Exception("Unknown '$kind' mutation '$name'"),
        };
    }

    /**
     * Add to the mutation resolver for the object type (create, update, delete) - when using BuildSchema
     * @param mixed $typename
     * @throws \Exception
     * @return callable
     */
    public static function mutation_field_resolver($typename = 'mutation'): callable
    {
        // call the right mutation resolver based on the first part of the field name <action><Object>
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) {
            /** @var Context $context */
            // disable caching for mutations
            $context->handler->enableCache(false);
            $context->tracePath(__CLASS__ . '::mutation_field_resolver: mutation', $info->path);
            // @todo check if type class corresponding to fieldname has overridden *_mutation_resolver
            $name = $info->fieldName;
            $action = substr($name, 0, 6);
            $type = strtolower(substr($name, 6));
            if ($action === "create") {
                $create_resolver = static::create_mutation_resolver($type);
                return call_user_func($create_resolver, $rootValue, $args, $context, $info);
            }
            if ($action === "update") {
                $update_resolver = static::update_mutation_resolver($type);
                return call_user_func($update_resolver, $rootValue, $args, $context, $info);
            }
            if ($action === "delete") {
                $delete_resolver = static::delete_mutation_resolver($type);
                return call_user_func($delete_resolver, $rootValue, $args, $context, $info);
            }
            throw new Exception('Invalid action ' . $action . ' for mutation ' . $info->fieldName);
        };
        return $resolver;
    }
}

class Mutations implements MutationsInterface
{
    use MutationsTrait;
}
