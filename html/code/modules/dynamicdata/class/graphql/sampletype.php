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
    public static $_xar_list   = 'samples';
    public static $_xar_item   = 'sample';

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    /**
    public function __construct()
    {
        $config = [
            'name' => static::$_xar_name,
            'fields' => static::_xar_get_object_fields(static::$_xar_object),
        ];
        // you need to pass the type config to the parent here, if you want to override the constructor
        parent::__construct($config);
    }
     */

    /**
     * This method *should* be overridden for each specific object type
     */
    public static function _xar_get_object_fields($object)
    {
        $fields = [
            'id' => Type::nonNull(Type::id()),
            'name' => Type::string(),
            'age' => Type::int(),
            'location' => Type::string(),
        ];
        return $fields;
    }
}
