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
        'getToken'     => 'tokentype',
        'deleteToken'  => 'tokentype',
        'createSample' => 'sampletype',
        'updateSample' => 'sampletype',
        'deleteSample' => 'sampletype',
    ];
    //public static $extra_types = [];

    public function __construct()
    {
        $config = static::_xar_get_type_config();
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config()
    {
        return [
            'name' => 'Mutation',
            'fields' => function () { return static::get_mutation_fields(); },
        ];
    }

    /**
     * Get all root mutation fields for the GraphQL Mutation type from the mutation_mapper above
     */
    public static function get_mutation_fields()
    {
        $fields = [];
        foreach (static::$mutation_mapper as $name => $type) {
            $add_field = static::add_mutation_field($name, $type);
            if (!empty($add_field)) {
                array_push($fields, $add_field);
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
        return $clazz::_xar_get_mutation_field($name);
    }
}
