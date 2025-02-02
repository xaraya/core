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
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL ObjectType and query fields for "sample" dynamicdata object type
 */
class SampleObjectType extends BaseObjectType
{
    public static string $_xar_name   = 'Sample';
    public static string $_xar_type   = 'sample';
    public static string $_xar_object = 'sample';
    public static bool $_xar_security = false;
    /** @var array<mixed> */
    public static $_xar_queries = [
        'page' => 'samples_page',
        'list' => 'samples',
        'item' => 'sample',
    ];
    /** @var array<mixed> */
    public static $_xar_mutations = [
        'create' => 'createSample',
        'update' => 'updateSample',
        'delete' => 'deleteSample',
    ];

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    /**
    public function __construct()
    {
        $config = $this->get_type_config(static::$_xar_name);
        // you need to pass the type config to the parent here, if you want to override the constructor
        parent::__construct($config);
    }
     */

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    /**
    public function get_type_config($typename, $object = null)
    {
        $object ??= GraphQLInflector::pluralize($typename);
        return [
            'name' => ucwords($typename, '_'),
            'fields' => function () use ($object) {
                return $this->get_object_fields($object);
            },
            'resolveField' => $this->object_field_resolver($typename, $object),
        ];
    }
     */

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
            'age' => Type::int(),
            'location' => Type::string(),
            // @checkme use deferred field or property resolver here with default load resolver = DataObjectLoader
            'partner' => [
                'type' => GraphQLTypes::getType('sample'),
                //'resolve' => self::deferred_field_resolver('sample', 'partner'),
                'resolve' => self::deferred_property_resolver('sample', 'partner', $object),
            ],
            'parents' => [
                'type' => GraphQLTypes::getTypeList('sample'),
                //'resolve' => self::deferred_field_resolver('sample', 'parents'),
                'resolve' => self::deferred_property_resolver('sample', 'parents', $object),
            ],
            'children' => [
                'type' => GraphQLTypes::getTypeList('sample'),
                //'resolve' => self::deferred_field_resolver('sample', 'children'),
                'resolve' => self::deferred_property_resolver('sample', 'children', $object),
            ],
        ];
        return $fields;
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function get_input_fields($object, &$newType): array
    {
        // return static::get_object_fields($object);
        $fields = [
            'id' => Type::id(),  // allow null for create here
            'name' => Type::string(),
            'age' => Type::int(),
            'location' => Type::string(),
            //'partner' => GraphQLTypes::getInputType('sample'),
            //'parents' => GraphQLTypes::getInputTypeList('sample'),
            //'children' => GraphQLTypes::getInputTypeList('sample'),
            'partner' => $newType,
            'parents' => Type::listOf($newType),
            'children' => Type::listOf($newType),
        ];
        return $fields;
    }

    /**
     * Get the object field resolver for the object type
     *
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public function object_field_resolver($type, $object = null): ?callable
    {
        return null;
    }

    /**
     * Load values for a deferred field - looking up the user names for example
     *
     * This method *should* be overridden for each specific object type - unless we rely on the DataObjectLoader
     *
     * See Solving N+1 Problem - https://webonyx.github.io/graphql-php/data-fetching/
     */
    public static function load_deferred($type): ?callable
    {
        // support equivalent of overridden load_deferred in inheritance (e.g. usertype)
        // Note: by default we rely on the DataObjectLoader for fields or the DeferredLoader for properties here
        return null;
    }
}
