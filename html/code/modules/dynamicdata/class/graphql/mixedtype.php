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

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;

/**
 * GraphQL ScalarType for mixed type fields - used in keyval type instead of multival
 */
class xarGraphQLMixedType extends ScalarType
{
    public $name = 'Mixed';
    public $description = 'Mixed type';

    public function serialize($value)
    {
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = ["mixed scalar type"];
        }
        return $value;
    }

    public function parseValue($value)
    {
        return 'value:' . $value;
    }

    public function parseLiteral($valueNode, array $variables = null)
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Exception('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        return 'literal:' . $valueNode->value;
    }
}
