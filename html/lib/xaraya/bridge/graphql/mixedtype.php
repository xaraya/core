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
use GraphQL\Type\Definition\ScalarType;

/**
 * GraphQL ScalarType for mixed type fields - used in keyval type instead of multival, or as generic args field
 */
class MixedType extends ScalarType
{
    public string $name = 'Mixed';
    public ?string $description = 'Mixed type';

    public function serialize($value)
    {
        GraphQLHandler::tracePath(["mixed scalar type"]);
        return $value;
    }

    /**
     * query getAssocArrayVar($arr: Mixed) {
     *   get_hello(args: $arr)
     * }
     *
     * "variables": {
     *   "arr": {
     *     "name": "hi",
     *     "more": {
     *       "oops": "hmmm"
     *     }
     *   }
     * }
     */
    public function parseValue($value)
    {
        GraphQLHandler::tracePath(["parse value", gettype($value), $value]);
        return $value;
    }

    /**
     * query getAssocArray {
     *   get_hello(args: {name: "hi", more: {oops: "hmmm"}})
     * }
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        GraphQLHandler::tracePath(["parse literal", $valueNode->kind, $variables]);
        return $this->parseValueNode($valueNode);
    }

    /**
     * Summary of parseValueNode
     * @param mixed $valueNode
     * @return mixed
     */
    public function parseValueNode($valueNode)
    {
        if ($valueNode->kind === "ObjectValue") {
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
            return $valueNode->value;
        }
    }
}
