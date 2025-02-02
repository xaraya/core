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

/**
 * For documentation purposes only - available via DataObjectTrait
 */
interface DataObjectInterface
{
    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_type_config($typename, $object = null): array;
    /**
     * This method *should* be overridden for each specific object type
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_object_fields($object): array;
    /**
     * Get the object field resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
     * @param mixed $object
     * @return ?callable
     */
    public function object_field_resolver($typename, $object = null): ?callable;
    /**
     * Make a generic Object Type with pagination for a dynamic object type by name = "Sample_Page" for samples etc.
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return ObjectType
     */
    public static function get_page_type($name, $type = null, $object = null): ObjectType;
}

/**
 * Trait to handle default object types for dataobjects
 */
trait DataObjectTrait
{
    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param mixed $typename
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
            // use specific field resolver for the object type if overridden in the class
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
     * @param mixed $typename
     * @param mixed $object
     * @return ?callable
     */
    public function object_field_resolver($typename, $object = null): ?callable
    {
        return null;
    }

    /**
     * Make a generic Object Type with pagination for a dynamic object type by name = "Sample_Page" for samples etc.
     * @param mixed $name
     * @param mixed $type
     * @param mixed $object
     * @return ObjectType
     */
    public static function get_page_type($name, $type = null, $object = null): ObjectType
    {
        return BuildType::make_page_type($name, $type, $object);
    }
}
