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
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;

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
class xarGraphQLModuleApiType extends ObjectType
{
    // @todo analyze response and mediatype + create result type per function if needed
    /**
    public static $_xar_functions = [
        'get_hello' => [
            'module' => 'dynamicdata', 'type' => 'rest', 'func' => 'get_hello', 'args' => 'mixed', 'result' => 'mixed'
        ],
        'post_hello' => [
            'module' => 'dynamicdata', 'type' => 'rest', 'func' => 'post_hello', 'args' => 'mixed', 'result' => 'mixed'
        ],
        'anotherapi' => [
            'module' => 'dynamicdata', 'type' => 'user', 'func' => 'getobjects', 'args' => ['moduleid' => 'string'], 'result' => ['object']
        ],
        'no_login' => [
            'module' => 'authsystem', 'type' => 'rest', 'func' => 'honeypot', 'args' => ['username' => 'string', 'password' => 'string'], 'result' => 'string'
        ],
    ];
     */
    public static $_xar_queries = [];
    public static $_xar_mutations = [];

    public function __construct()
    {
        $config = static::_xar_get_type_config('Module_Api');
        xarGraphQL::setTimer('new ' . $config['name']);
        parent::__construct($config);
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_get_type_config($typename, $object = null)
    {
        return [
            'name' => 'Module_Api',
            'fields' => [],
        ];
    }

    public static function _xar_load_config()
    {
        xarGraphQL::loadModules();
        foreach (xarGraphQL::$config['modules'] as $itemid => $info) {
            $module = $info['module'];
            foreach ($info['apilist'] as $api => $item) {
                if (isset($item['enabled']) && empty($item['enabled'])) {
                    continue;
                }
                $item['module'] = $module;
                $item['type'] ??= 'rest';
                $item['func'] = $api;
                $item['method'] ??= 'get';
                $item['args'] = 'mixed';
                // @todo parse optional response - and how to match with result type, e.g. DD getobjects -> ['object']
                $item['result'] = 'mixed';
                $name = $module . '_' . $item['path'];
                if ($item['method'] == 'post' || !empty($item['requestBody'])) {
                    if (!empty($item['requestBody']) && !empty($item['requestBody']['application/json'])) {
                        $item['args'] = static::_xar_parse_api_parameters($item['requestBody']['application/json']);
                    }
                    if (!array_key_exists($name, static::$_xar_mutations)) {
                        static::$_xar_mutations[$name] = $item;
                    }
                } elseif (!array_key_exists($name, static::$_xar_queries)) {
                    if (isset($item['parameters'])) {
                        $item['args'] = static::_xar_parse_api_parameters($item['parameters']);
                    }
                    static::$_xar_queries[$name] = $item;
                }
            }
        }
    }

    public static function _xar_parse_api_parameters($parameters)
    {
        $properties = [];
        // @checkme handle more complex parameters like arrays of itemids for getitemlinks
        foreach ($parameters as $key => $name) {
            // 'parameters' => ['itemtype', 'itemids'],  // optional parameter(s)
            // 'requestBody' => ['application/json' => ['name', 'score']],  // optional requestBody
            if (is_numeric($key)) {
                $properties[$name] = 'string';
            } elseif (is_array($name)) {
                // => ['itemtype' => ['type' => 'string'], 'itemids' => ['type' => 'array', 'items' => ['type' => 'string']]]
                if (array_key_exists("type", $name)) {
                    $properties[$key] = $name['type'];
                // => ['itemtype' => 'string', 'itemids' => ['integer']]
                } else {
                    // @checkme use style = form + explode = true here
                    $properties[$key] = [$name[0]];
                }
                // => ['itemtype' => 'string', 'itemids' => 'array']
            } elseif (in_array($name, ["string", "integer", "boolean"])) {
                $properties[$key] = $name;
            // => ['itemtype' => 'string', 'itemids' => 'array']
            } elseif ($name === "array") {
                // @checkme use style = form + explode = true here
                $properties[$key] = ['string'];
            //} elseif ($name === "object") {
            } else {
            }
        }
        return $properties;
    }

    public static function _xar_get_query_fields()
    {
        static::_xar_load_config();
        $fields = [];
        foreach (static::$_xar_queries as $name => $func) {
            $fields[] = static::_xar_get_query_field($name, $func);
        }
        return $fields;
    }

    public static function _xar_get_query_field($name, $func = [])
    {
        if (empty($func)) {
            throw new Exception("Unknown module_api query '$name'");
        }
        // @todo add loadModules(), analyze parameters and requestBody + get rid of module, type and func args
        //$argstype = static::_xar_get_param_fielddef($func['args']);
        // @todo add loadModules(), analyze response and mediatype + create result type per function if needed
        $resulttype = static::_xar_get_param_fielddef($func['result']);
        return [
            'name' => $name,
            'description' => 'Call ' . $func['module'] . ' ' . $func['type'] . 'api ' . $func['func'] . ' function via GraphQL',
            'type' => $resulttype['type'],
            //'args' => [
            //    'module' => ['type' => Type::string(), 'defaultValue' => $func['module']],
            //    'type' => ['type' => Type::string(), 'defaultValue' => $func['type']],
            //    'func' => ['type' => Type::string(), 'defaultValue' => $func['func']],
            //    'args' => $argstype,
            //],
            'args' => static::_xar_parse_input_args($name, $func),
            'resolve' => static::_xar_call_query_resolver($func),
        ];
    }

    public static function _xar_get_param_fielddef($param)
    {
        if (empty($param)) {
            $typedef = ['type' => xarGraphQL::get_type('mixed')];
        } elseif (is_string($param)) {
            // @checkme use openapi data types and/or graphql base types + see buildtype get_field_basetypes()
            //if (array_key_exists(ucfirst($param), Type::getStandardTypes())) {
            if (in_array($param, ['id', 'string', 'boolean', 'integer', 'number'])) {
                //$typedef = ['type' => Type::{$param}()];
                $typedef = ['type' => xarGraphQL::get_type($param)];
            } else {
                $typedef = ['type' => xarGraphQL::get_type($param)];
            }
        } elseif (is_numeric(array_key_first($param)) && count($param) == 1) {
            if (in_array($param[0], ['id', 'string', 'boolean', 'integer', 'number'])) {
                //$typedef = ['type' => Type::listOf(Type::{$param[0]}())];
                $typedef = ['type' => xarGraphQL::get_type_list($param[0])];
            } else {
                $typedef = ['type' => xarGraphQL::get_type_list($param[0])];
            }
        } else {
            // @checkme create input type corresponding to $args later?
            $typedef = ['type' => xarGraphQL::get_type('mixed')];
        }
        return $typedef;
    }

    /**
     * Get the call query resolver for the module api function
     *
     * This method *may* be overridden for a specific module api function, but it doesn't have to be
     */
    public static function _xar_call_query_resolver($func)
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($func) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["module_api call query"]);
            }
            $fields = $info->getFieldSelection(1);
            // @checkme we only get the relevant 'args' values via the input type here
            if (is_array($func['args']) && !is_numeric(array_key_first($func['args']))) {
                $args = ['args' => $args];
            // @todo get rid of module, type and func args later
            } elseif (empty($args['module']) || $args['module'] != $func['module']) {
                throw new Exception("Invalid module for $func[module] $func[type] $func[func] function");
            }
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $args['args'] ??= [];
            // @checkme pass along the $args['args'] part here
            return static::_xar_call_module_function($func['module'], $func['type'], $func['func'], $args['args'], $userId, $fields);
        };
        return $resolver;
    }

    public static function _xar_get_mutation_fields()
    {
        static::_xar_load_config();
        $fields = [];
        foreach (static::$_xar_mutations as $name => $func) {
            $fields[] = static::_xar_get_mutation_field($name, $func);
        }
        return $fields;
    }

    public static function _xar_get_mutation_field($name, $func = [])
    {
        if (empty($func)) {
            throw new Exception("Unknown module_api mutation '$name'");
        }
        // @todo add loadModules(), analyze parameters and requestBody + get rid of module, type and func args
        // @todo add loadModules(), analyze response and mediatype + create result type per function if needed
        $resulttype = static::_xar_get_param_fielddef($func['result']);
        return [
            'name' => $name,
            'description' => 'Call ' . $func['module'] . ' ' . $func['type'] . 'api ' . $func['func'] . ' function via GraphQL',
            'type' => $resulttype['type'],
            'args' => [
                //'module' => ['type' => Type::string(), 'defaultValue' => $func['module']],
                //'type' => ['type' => Type::string(), 'defaultValue' => $func['type']],
                //'func' => ['type' => Type::string(), 'defaultValue' => $func['func']],
                //'args' => ['type' => xarGraphQL::get_type('mixed')],
                //'input' => xarGraphQL::get_input_type($name),
                'input' => function () use ($name) {
                    return static::_xar_create_input_type($name);
                },
            ],
            'resolve' => static::_xar_call_mutation_resolver($func),
        ];
    }

    // @checkme for dynamically created types like the module api input types per function
    public static function _xar_create_input_type($name)
    {
        $typename = ucwords($name . '_input', '_');
        if (xarGraphQL::has_type($typename)) {
            return xarGraphQL::get_type($typename);
        }
        $newType = static::_xar_get_input_type($name);
        xarGraphQL::set_type($typename, $newType);
        return $newType;
    }

    /**
     * Make a generic Input Object Type for create/update mutations - @checkme these are created for each function
     */
    public static function _xar_get_input_type($name, $object = null)
    {
        $input = ucwords($name . '_input', '_');
        $func = static::$_xar_mutations[$name];
        $description = 'Input for ' . $func['module'] . ' ' . $func['type'] . 'api ' . $func['func'] . ' function';
        // https://webonyx.github.io/graphql-php/type-definitions/object-types/#recurring-and-circular-types
        // $fields = static::_xar_get_input_fields($object);
        $newType = new InputObjectType([
            'name' => $input,
            'description' => $description,
            //'fields' => function () use ($name, &$newType) {
            //    return static::_xar_get_input_fields($name, $newType);
            //},
            'fields' => function () use ($name, $func) {
                return static::_xar_parse_input_args($name, $func);
            },
            'parseValue' => static::_xar_input_value_parser($name, $object),
        ]);
        return $newType;
    }

    /**
     * This method *may* be overridden for a specific module api function, but it doesn't have to be
     */
    public static function _xar_get_input_fields($name, &$newType = null)
    {
        $func = static::$_xar_mutations[$name];
        return static::_xar_parse_input_args($name, $func);
    }

    public static function _xar_parse_input_args($name, $func)
    {
        if (empty($func['args'])) {
            return [];
        }
        // @todo add loadModules(), analyze parameters and requestBody + get rid of module, type and func args
        if (is_array($func['args']) && !is_numeric(array_key_first($func['args']))) {
            $fields = [];
            foreach ($func['args'] as $key => $value) {
                $fields[$key] = static::_xar_get_param_fielddef($value);
            }
            return $fields;
        }
        $argstype = static::_xar_get_param_fielddef($func['args']);
        $fields = [
            'module' => ['type' => Type::string(), 'defaultValue' => $func['module']],
            'type' => ['type' => Type::string(), 'defaultValue' => $func['type']],
            'func' => ['type' => Type::string(), 'defaultValue' => $func['func']],
            'args' => $argstype,
        ];
        return $fields;
    }

    /**
     * This method *may* be overridden for a specific object type, but it doesn't have to be
     */
    public static function _xar_input_value_parser($name, $object)
    {
    }

    /**
     * Get the call mutation resolver for the module api function
     *
     * This method *may* be overridden for a specific module api function, but it doesn't have to be
     */
    public static function _xar_call_mutation_resolver($func)
    {
        $resolver = function ($rootValue, $args, $context, ResolveInfo $info) use ($func) {
            if (xarGraphQL::$trace_path) {
                xarGraphQL::$paths[] = array_merge($info->path, ["module_api call mutation"]);
            }
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
            $userId = xarGraphQL::checkUser($context);
            if (empty($userId)) {
                throw new Exception('Invalid user');
            }
            $args['args'] ??= [];
            // @checkme pass along the $args['args'] part here
            return static::_xar_call_module_function($func['module'], $func['type'], $func['func'], $args['args'], $userId, $fields);
        };
        return $resolver;
    }

    public static function _xar_call_module_function($module, $type, $func, $args, $userId, $fields)
    {
        //$role = xarRoles::getRole($userId);
        //$rolename = $role->getName();
        xarMod::init();
        xarUser::init();
        xarGraphQL::$paths[] = ["Calling $module $type $func for user $userId", $args, $fields];
        return xarMod::apiFunc($module, $type, $func, $args);
        $values = ['func_args' => $args];
        return $values;
    }
}
