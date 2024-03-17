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

use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\StringValueNode;

/**
 * GraphQL ScalarType for serialized fields
 */
class xarGraphQLSerialType extends ScalarType
{
    public string $name = 'Serial';
    public ?string $description = 'Serialized value';

    public function serialize($value)
    {
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = ["serial scalar type"];
        }
        return $this->tryUnserialized($value);
    }

    /**
     * Summary of tryUnserialized
     * @param mixed $value
     * @return mixed
     */
    public function tryUnserialized($value)
    {
        if (empty($value) || !is_string($value)) {
            return $value;
        }
        $result = @unserialize($value);
        if ($result !== false) {
            return $result;
        }
        return $value;
    }

    public function parseValue($value)
    {
        return $this->tryUnserialized($value);
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if (xarGraphQL::$trace_path) {
            xarGraphQL::$paths[] = ["parse literal", $valueNode->kind, $variables];
        }
        // @checkme support only top-level serialized values here
        if ($valueNode instanceof StringValueNode) {
            return $this->tryUnserialized($valueNode->value);
        }
        return $this->parseValueNode($valueNode);
    }

    /**
     * Summary of parseValueNode
     * @param mixed $valueNode
     * @return mixed
     */
    public function parseValueNode($valueNode)
    {
        if ($valueNode instanceof ObjectValueNode) {
            $values = [];
            foreach ($valueNode->fields as $node) {
                $values[$node->name->value] = $this->parseValueNode($node->value);
            }
            return $values;
        } elseif ($valueNode->kind === "ListValue") {
            $values = [];
            foreach ($valueNode->values as $node) {
                $values[] = $this->parseValueNode($node);
            }
            return $values;
        } else {
            // @checkme support only top-level serialized values here
            //return $this->tryUnserialized($valueNode->value);
            return $valueNode->value;
        }
    }
}
