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

class xarGraphQLMutationType extends ObjectType
{
    public static $mutation_mapper = [
        'createSample' => 'sampletype',
        'updateSample' => 'sampletype',
        'deleteSample' => 'sampletype',
    ];
    //public static $extra_types = [];

    public function __construct()
    {
        $config = [
            'name' => 'Mutation',
            'fields' => static::get_mutation_fields(),
        ];
        parent::__construct($config);
    }

    /**
     * Get all root mutation fields for the GraphQL Mutation type from the mutation_mapper above
     */
    public static function get_mutation_fields()
    {
        $fields = array();
        foreach (static::$mutation_mapper as $name => $type) {
            $add_fields = static::add_mutation_field($name, $type);
            if (!empty($add_fields)) {
                $fields = array_merge($fields, $add_fields);
            }
        }
        // @todo get mutation fields from BuildType for extra dynamicdata object types
        if (!empty(xarGraphQL::$extra_types)) {
            $clazz = xarGraphQL::get_type_class("buildtype");
            foreach (xarGraphQL::$extra_types as $name) {
                $add_fields = $clazz::get_mutation_fields($name);
                if (!empty($add_fields)) {
                    $fields = array_merge($fields, $add_fields);
                }
            }
        }
        return $fields;
    }

    /**
     * Add a root mutation field as defined in the GraphQL Object Type class (createSample, updateSample, ...)
     */
    public static function add_mutation_field($name, $type)
    {
        $clazz = xarGraphQL::get_type_class($type);
        // @checkme for some reason, mutation doesn't accept full field definition without $name => like query
        // contrary to https://webonyx.github.io/graphql-php/type-system/object-types/#shorthand-field-definitions
        //return $clazz::_xar_get_mutation_field($name);
        return array($name => $clazz::_xar_get_mutation_field($name));
    }

}
