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
use xarMod;
use xarUser;
use Closure;
use Exception;

/**
 * Module API GraphQL ObjectType for calling module api functions
 *
 * query:
 * query getAssocArray {
 *   get_hello(args: { name: "hi", more: { oops: "hmmm" } })
 * }
 * query getAssocArrayVar($arr: Mixed) {
 *   get_hello(args: $arr)
 * }
 * mutation postAssocArray {
 *   post_hello(input: {args: { name: "hi", more: { oops: "hmmm" } }})
 * }
 * mutation postAssocArrayVar($arr: Mixed) {
 *   post_hello(input: {args: $arr})
 * }
 *
 * variables:
 * {
 *   "arr": { "name": "hi", "more": { "oops": "hmmm" } }
 * }
 *
 * Or use specific input type when the arguments are defined as ['field' => 'type', ...] below
 *
 */
class ModuleApiType extends ObjectType implements InputObjectInterface
{
    // @todo analyze response and mediatype + create result type per function if needed
    /** @var array<mixed> */
    public static $_xar_queries = [];
    /** @var array<mixed> */
    public static $_xar_mutations = [];

    public function __construct()
    {
        $config = $this->get_type_config('Module_Api');
        GraphQLHandler::setTimer('new ' . $config['name']);
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     * @param string $typename
     * @param mixed $object
     * @return array<string, mixed>
     */
    public function get_type_config($typename, $object = null)
    {
        return [
            'name' => 'Module_Api',
            'fields' => [],
        ];
    }

    /**
     * Summary of get_query_fields
     * @return array<mixed>
     */
    public static function get_query_fields(): array
    {
        if (empty(static::$_xar_queries)) {
            static::$_xar_queries = GraphQLModules::getQueries();
        }
        $fields = [];
        foreach (static::$_xar_queries as $name => $func) {
            $fields[] = static::get_query_field($name, $func);
        }
        return $fields;
    }

    /**
     * Summary of get_query_field
     * @param mixed $name
     * @param mixed $func
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_query_field($name, $func = []): array
    {
        if (empty($func)) {
            throw new Exception("Unknown module_api query '$name'");
        }
        // @todo add loadModules(), analyze parameters and requestBody + get rid of module, type and func args
        //$argstype = static::get_param_fielddef($func['args']);
        // @todo add loadModules(), analyze response and mediatype + create result type per function if needed
        $resulttype = static::get_param_fielddef($func['result']);
        $fields = static::parse_input_args($name, $func);
        // @checkme add paging parameters if specified in getlist.php
        if (!empty($func['paging'])) {
            $fields = array_merge($fields, static::get_paging_args());
        }
        return [
            'name' => $name,
            'description' => 'Call ' . $func['module'] . ' ' . $func['type'] . 'api ' . $func['func'] . ' function via GraphQL for ' . $name,
            'type' => $resulttype['type'],
            //'args' => [
            //    'module' => ['type' => Type::string(), 'defaultValue' => $func['module']],
            //    'type' => ['type' => Type::string(), 'defaultValue' => $func['type']],
            //    'func' => ['type' => Type::string(), 'defaultValue' => $func['func']],
            //    'args' => $argstype,
            //],
            'args' => $fields,
            'resolve' => static::call_query_resolver($func),
        ];
    }

    /**
     * Summary of get_param_fielddef
     * @param mixed $param
     * @return array<string, mixed>
     */
    public static function get_param_fielddef($param)
    {
        if (empty($param)) {
            $typedef = ['type' => GraphQLTypes::getType('mixed')];
        } elseif (is_string($param)) {
            // @checkme use openapi data types and/or graphql base types + see buildtype get_field_basetypes()
            $name = $param;
            //if (array_key_exists(ucfirst($param), Type::getStandardTypes())) {
            if (in_array($name, ['id', 'string', 'boolean', 'integer', 'number'])) {
                //$typedef = ['type' => Type::{$name}()];
                $typedef = ['type' => GraphQLTypes::getType($name)];
            } elseif ($name == 'object') {
                $typedef = ['type' => GraphQLTypes::getType('mixed')];
            } else {
                $typedef = ['type' => GraphQLTypes::getType($name)];
            }
        } elseif (is_numeric(array_key_first($param)) && count($param) == 1) {
            $name = $param[0];
            if (in_array($name, ['id', 'string', 'boolean', 'integer', 'number'])) {
                //$typedef = ['type' => Type::listOf(Type::{$name}())];
                $typedef = ['type' => GraphQLTypes::getTypeList($name)];
            } elseif ($name == 'object') {
                $typedef = ['type' => GraphQLTypes::getTypeList('mixed')];
            } else {
                $typedef = ['type' => GraphQLTypes::getTypeList($name)];
            }
        } elseif (array_key_exists('type', $param) && $param['type'] == 'array') {
            $name = $param['items']['type'];
            if (in_array($name, ['id', 'string', 'boolean', 'integer', 'number'])) {
                //$typedef = ['type' => Type::listOf(Type::{$name}())];
                $typedef = ['type' => GraphQLTypes::getTypeList($name)];
            } elseif ($name == 'object') {
                $typedef = ['type' => GraphQLTypes::getTypeList('mixed')];
            } else {
                $typedef = ['type' => GraphQLTypes::getTypeList($name)];
            }
        } else {
            // @checkme create input type corresponding to $args later?
            $typedef = ['type' => GraphQLTypes::getType('mixed')];
        }
        return $typedef;
    }

    /**
     * Get the call query resolver for the module api function
     *
     * This method *may* be overridden for a specific module api function, but it doesn't have to be
     * @param mixed $func
     * @throws \Exception
     * @return \Closure
     */
    public static function call_query_resolver($func)
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($func) {
            $context->tracePath(__CLASS__ . '::call_query_resolver: ' . $func['module'] . ' ' . $func['type'] . ' ' . $func['func'], $info->path);
            $fields = $info->getFieldSelection(1);
            // @checkme we only get the relevant 'args' values via the input type here
            if (is_array($func['args']) && !is_numeric(array_key_first($func['args']))) {
                $args = ['args' => $args];
                // @todo get rid of module, type and func args later
            } elseif (empty($args['module']) || $args['module'] != $func['module']) {
                throw new Exception("Invalid module for $func[module] $func[type] $func[func] function");
            }
            $userId = GraphQLHandler::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            // @checkme handle default args if specified in getlist.php
            $args['args'] ??= [];
            // @checkme actual params overwrite default args
            if (!empty($func['default'])) {
                $args['args'] = array_merge($func['default'], $args['args']);
            }
            // @checkme pass along the $args['args'] part here
            return static::call_module_function($func['module'], $func['type'], $func['func'], $args['args'], $context, $userId, $fields);
        };
        return $resolver;
    }

    /**
     * Summary of get_mutation_fields
     * @return array<mixed>
     */
    public static function get_mutation_fields(): array
    {
        if (empty(static::$_xar_mutations)) {
            static::$_xar_mutations = GraphQLModules::getMutations();
        }
        $fields = [];
        foreach (static::$_xar_mutations as $name => $func) {
            $fields[] = static::get_mutation_field($name, $func);
        }
        return $fields;
    }

    /**
     * Summary of get_mutation_field
     * @param mixed $name
     * @param mixed $func
     * @throws \Exception
     * @return array<string, mixed>
     */
    public static function get_mutation_field($name, $func = []): array
    {
        if (empty($func)) {
            throw new Exception("Unknown module_api mutation '$name'");
        }
        // @todo add loadModules(), analyze parameters and requestBody + get rid of module, type and func args
        // @todo add loadModules(), analyze response and mediatype + create result type per function if needed
        $resulttype = static::get_param_fielddef($func['result']);
        return [
            'name' => $name,
            'description' => 'Call ' . $func['module'] . ' ' . $func['type'] . 'api ' . $func['func'] . ' function via GraphQL',
            'type' => $resulttype['type'],
            'args' => [
                //'module' => ['type' => Type::string(), 'defaultValue' => $func['module']],
                //'type' => ['type' => Type::string(), 'defaultValue' => $func['type']],
                //'func' => ['type' => Type::string(), 'defaultValue' => $func['func']],
                //'args' => ['type' => GraphQLTypes::getType('mixed')],
                //'input' => GraphQLTypes::getInputType($name),
                'input' => function () use ($name) {
                    return static::create_input_type($name);
                },
            ],
            'resolve' => static::call_mutation_resolver($func),
        ];
    }

    // @checkme for dynamically created types like the module api input types per function
    /**
     * Summary of create_input_type
     * @param mixed $name
     * @return InputObjectType|mixed
     */
    public static function create_input_type($name)
    {
        $typename = ucwords($name . '_input', '_');
        if (GraphQLTypes::hasType($typename)) {
            return GraphQLTypes::getType($typename);
        }
        $newType = static::get_input_type($name);
        GraphQLTypes::setType($typename, $newType);
        return $newType;
    }

    /**
     * Make a generic Input Object Type for create/update mutations - @checkme these are created for each function
     */
    public static function get_input_type($name, $object = null): InputObjectType
    {
        if (empty(static::$_xar_mutations)) {
            static::$_xar_mutations = GraphQLModules::getMutations();
        }
        $input = ucwords($name . '_input', '_');
        $func = static::$_xar_mutations[$name];
        $description = 'Input for ' . $func['module'] . ' ' . $func['type'] . 'api ' . $func['func'] . ' function';
        // https://webonyx.github.io/graphql-php/type-definitions/object-types/#recurring-and-circular-types
        // $fields = static::get_input_fields($object);
        $newType = new InputObjectType([
            'name' => $input,
            'description' => $description,
            //'fields' => function () use ($name, &$newType) {
            //    return static::get_input_fields($name, $newType);
            //},
            'fields' => function () use ($name, $func) {
                return static::parse_input_args($name, $func);
            },
            'parseValue' => static::input_value_parser($name, $object),
        ]);
        return $newType;
    }

    /**
     * This method *may* be overridden for a specific module api function, but it doesn't have to be
     */
    public static function get_input_fields($name, &$newType = null): array
    {
        if (empty(static::$_xar_mutations)) {
            static::$_xar_mutations = GraphQLModules::getMutations();
        }
        $func = static::$_xar_mutations[$name];
        return static::parse_input_args($name, $func);
    }

    /**
     * Summary of parse_input_args
     * @param mixed $name
     * @param mixed $func
     * @return array<string, mixed>
     */
    public static function parse_input_args($name, $func)
    {
        if (empty($func['args'])) {
            return [];
        }
        // @todo add loadModules(), analyze parameters and requestBody + get rid of module, type and func args
        if (is_array($func['args']) && !is_numeric(array_key_first($func['args']))) {
            $fields = [];
            foreach ($func['args'] as $key => $value) {
                $fields[$key] = static::get_param_fielddef($value);
            }
            return $fields;
        }
        $argstype = static::get_param_fielddef($func['args']);
        $fields = [
            'module' => ['type' => Type::string(), 'defaultValue' => $func['module']],
            'type' => ['type' => Type::string(), 'defaultValue' => $func['type']],
            'func' => ['type' => Type::string(), 'defaultValue' => $func['func']],
            'args' => $argstype,
        ];
        return $fields;
    }

    /**
     * Summary of get_paging_args
     * @return array<string, mixed>
     */
    public static function get_paging_args()
    {
        $fields = [
            'order' => Type::string(),
            'offset' => [
                'type' => Type::int(),
                'defaultValue' => 0,
            ],
            'limit' => [
                'type' => Type::int(),
                'defaultValue' => 20,
            ],
            'filter' => Type::listOf(Type::string()),
        ];
        return $fields;
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function input_value_parser($name, $object): ?callable
    {
        return null;
    }

    /**
     * Get the call mutation resolver for the module api function
     *
     * This method *may* be overridden for a specific module api function, but it doesn't have to be
     * @param mixed $func
     * @throws \Exception
     * @return \Closure
     */
    public static function call_mutation_resolver($func)
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($func) {
            $context->tracePath(__CLASS__ . '::call_mutation_resolver: ' . $func['module'] . ' ' . $func['type'] . ' ' . $func['func'], $info->path);
            $fields = $info->getFieldSelection(1);
            if (empty($args['input'])) {
                throw new Exception("Unknown input for $func[module] $func[type] $func[func] function");
            }
            // @checkme use only the $args['input'] part here
            $args = $args['input'];

            // @checkme we only get the relevant 'args' values via the input type here
            if (is_array($func['args']) && !is_numeric(array_key_first($func['args']))) {
                $args = ['args' => $args];
                // @todo get rid of module, type and func args later
            } elseif (empty($args['module']) || $args['module'] != $func['module']) {
                throw new Exception("Invalid module for $func[module] $func[type] $func[func] function");
            }
            $userId = GraphQLHandler::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            // @checkme handle default args if specified in getlist.php
            $args['args'] ??= [];
            // @checkme actual params overwrite default args
            if (!empty($func['default'])) {
                $args['args'] = array_merge($func['default'], $args['args']);
            }
            // @checkme pass along the $args['args'] part here
            return static::call_module_function($func['module'], $func['type'], $func['func'], $args['args'], $context, $userId, $fields);
        };
        return $resolver;
    }

    /**
     * Summary of call_module_function
     * @param mixed $module
     * @param mixed $type
     * @param mixed $func
     * @param mixed $args
     * @param mixed $context
     * @param mixed $userId
     * @param mixed $fields
     * @return mixed
     */
    public static function call_module_function($module, $type, $func, $args, $context, $userId, $fields)
    {
        //$role = xarRoles::getRole($userId);
        //$rolename = $role->getName();
        xarMod::init();
        xarUser::init();
        $context->tracePath(__CLASS__ . '::call_module_function: ' . "$module $type $func for user $userId", ['args' => $args, 'fields' => $fields]);
        return xarMod::apiFunc($module, $type, $func, $args, $context);
        //$values = ['func_args' => $args];
        //return $values;
    }
}
