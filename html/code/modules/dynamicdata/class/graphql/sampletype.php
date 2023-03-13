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
 * GraphQL ObjectType and query fields for "sample" dynamicdata object type
 */
class xarGraphQLSampleType extends xarGraphQLBaseType
{
    public static $_xar_name   = 'Sample';
    public static $_xar_type   = 'sample';
    public static $_xar_object = 'sample';
    public static $_xar_security = false;
    public static $_xar_queries = [
        'page' => 'samples_page',
        'list' => 'samples',
        'item' => 'sample',
    ];
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
        $config = static::_xar_get_type_config(static::$_xar_name);
        // you need to pass the type config to the parent here, if you want to override the constructor
        parent::__construct($config);
    }
     */

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    /**
    public static function _xar_get_type_config($typename, $object = null)
    {
        $object ??= xarGraphQLInflector::pluralize($typename);
        return [
            'name' => ucwords($typename, '_'),
            'fields' => function () use ($object) {
                return static::_xar_get_object_fields($object);
            },
            'resolveField' => static::_xar_object_field_resolver($typename, $object),
        ];
    }
     */

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object): array
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
            'age' => Type::int(),
            'location' => Type::string(),
            // @checkme use deferred field or property resolver here with default load resolver = DataObjectLoader
            'partner' => [
                'type' => xarGraphQL::get_type('sample'),
                //'resolve' => self::_xar_deferred_field_resolver('sample', 'partner'),
                'resolve' => self::_xar_deferred_property_resolver('sample', 'partner', $object),
            ],
            'parents' => [
                'type' => xarGraphQL::get_type_list('sample'),
                //'resolve' => self::_xar_deferred_field_resolver('sample', 'parents'),
                'resolve' => self::_xar_deferred_property_resolver('sample', 'parents', $object),
            ],
            'children' => [
                'type' => xarGraphQL::get_type_list('sample'),
                //'resolve' => self::_xar_deferred_field_resolver('sample', 'children'),
                'resolve' => self::_xar_deferred_property_resolver('sample', 'children', $object),
            ],
        ];
        return $fields;
    }

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_input_fields($object, &$newType): array
    {
        // return static::_xar_get_object_fields($object);
        $fields = [
            'id' => Type::id(),  // allow null for create here
            'name' => Type::string(),
            'age' => Type::int(),
            'location' => Type::string(),
            //'partner' => xarGraphQL::get_input_type('sample'),
            //'parents' => xarGraphQL::get_input_type_list('sample'),
            //'children' => xarGraphQL::get_input_type_list('sample'),
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
    public static function _xar_object_field_resolver($type, $object = null): ?callable
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
    public static function _xar_load_deferred($type): ?callable
    {
        // support equivalent of overridden _xar_load_deferred in inheritance (e.g. usertype)
        // Note: by default we rely on the DataObjectLoader for fields or the DeferredLoader for properties here
        return null;
    }
}
