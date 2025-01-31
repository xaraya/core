<?php

/**
 * Classes for handling GraphQL queries on Dynamic Data Objects (POC)
 *
 * Note: this assumes you install graphql-php with composer
 * and use composer autoload in the entrypoint, see e.g. gql.php
 *
 * $ composer require webonyx/graphql-php
 * $ head html/gql.php
 * <?php
 * ...
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * ...
 *
 * @package core\bridge
 * @subpackage graphql
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

use GraphQL\Type\Definition\Type;

/**
 * See xardocs/graphql.txt for class structure
 * @uses \sys::autoload()
 */
class xarGraphQLTypes
{
    /** @var array<string, mixed> */
    protected static $typeCache = [];
    /** @var array<string, string> */
    protected static $typeMapper = [
        'query'    => 'querytype',
        'sample'   => 'sampletype',
        'object'   => 'objecttype',
        'property' => 'propertytype',
        'access'   => 'accesstype',
        'keyval'   => 'keyvaltype',
        'multival' => 'multivaltype',
        'user'     => 'usertype',
        'token'    => 'tokentype',
        'serial'   => 'serialtype',
        'mixed'    => 'mixedtype',
        'mutation' => 'mutationtype',
        //'node'     => 'nodetype',
        //'ddnode'   => 'ddnodetype',
        'module_api' => 'moduleapitype',
    ];
    /** @var array<string, string> */
    protected static $baseTypes = [
        'id'      => 'id',
        'string'  => 'string',
        'integer' => 'int',
        'boolean' => 'boolean',
        'number'  => 'float',
    ];
    /** @var array<string> */
    protected static $extraTypes = [];

    /**
     * Get GraphQL Type by name
     * @param string $name
     * @return mixed|void
     */
    public static function getType($name)
    {
        $name = strtolower($name);
        if (isset(self::$typeCache[$name])) {
            return self::$typeCache[$name];
        }
        // Schema doesn't accept lazy loading of query type or scalar type (besides typeLoader)
        if (in_array($name, ['query', 'mutation', 'mixed', 'serial'])) {
            return self::loadLazyType($name);
        }
        if (in_array($name, ['subscription'])) {
            return;
        }
        //if (!self::hasType($name)) {
        //    throw new Exception("Unknown graphql type: " . $name);
        //}
        // See https://github.com/webonyx/graphql-php/pull/557
        return static function () use ($name) {
            return self::loadLazyType($name);
        };
    }

    /**
     * Summary of has_type
     * @param string $name
     * @return bool
     */
    public static function hasType($name)
    {
        $name = strtolower($name);
        if (in_array($name, self::$extraTypes) || array_key_exists($name, self::$typeMapper)) {
            return true;
        }
        // @checkme for dynamically created types like the module api input types per function
        if (isset(self::$typeCache[$name])) {
            return true;
        }
        return false;
    }

    /**
     * @checkme for dynamically created types like the module api input types per function
     * Summary of setType
     * @param string $name
     * @param mixed $type
     * @return void
     */
    public static function setType($name, $type)
    {
        $name = strtolower($name);
        self::$typeCache[$name] = $type;
    }

    /**
     * Summary of getTypeList
     * 'type' => Type::listOf(xarGraphQLTypes::getType(static::$_xar_type)), doesn't accept lazy loading
     * @param string $name
     * @return Closure
     */
    public static function getTypeList($name)
    {
        $name = strtolower($name);
        // See https://github.com/webonyx/graphql-php/pull/557
        return static function () use ($name) {
            // return Type::listOf(self::getType($name));
            return Type::listOf(self::loadLazyType($name));
        };
    }

    /**
     * Summary of get_input_type_list
     * 'type' => Type::listOf(xarGraphQLTypes::getInputType(static::$_xar_type)), doesn't accept lazy loading
     * @param string $name
     * @return Closure
     */
    public static function getInputTypeList($name)
    {
        $name = strtolower($name);
        $input = $name . '_input';
        // See https://github.com/webonyx/graphql-php/pull/557
        return static function () use ($name) {
            // return Type::listOf(self::loadLazyType($input));
            return Type::listOf(self::getInputType($name));
        };
    }

    /**
     * Summary of loadLazyType
     * @param string $name
     * @throws \Exception
     * @return mixed
     */
    public static function loadLazyType($name)
    {
        if (isset(self::$typeCache[$name])) {
            return self::$typeCache[$name];
        }
        // @checkme use openapi data types and/or graphql base types + see buildtype get_field_basetypes()
        if (array_key_exists($name, self::$baseTypes)) {
            return Type::{self::$baseTypes[$name]}();
        }
        //xarGraphQL::tracePath(['load_lazy_type', $name]);
        $page_ext = '_page';
        if (str_ends_with($name, $page_ext)) {
            return self::getPageType(substr($name, 0, strlen($name) - strlen($page_ext)));
        }
        $input_ext = '_input';
        if (str_ends_with($name, $input_ext)) {
            return self::getInputType(substr($name, 0, strlen($name) - strlen($input_ext)));
        }
        // make Object Type from BuildType for extra dynamicdata object types
        if (in_array($name, self::$extraTypes) || in_array(ucfirst($name), self::$extraTypes)) {
            $type = xarGraphQLBuildType::make_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $name);
            }
            self::$typeCache[$name] = $type;
            return $type;
        }
        if (!array_key_exists($name, self::$typeMapper)) {
            throw new Exception("Unknown graphql type: " . $name);
        }
        $clazz = self::getTypeClass(self::$typeMapper[$name]);
        $type = new $clazz();
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $name);
        }
        self::$typeCache[$name] = $type;
        return $type;
    }

    /**
     * Get GraphQL Type by name with pagination
     * @param string $name
     * @throws \Exception
     * @return mixed
     */
    public static function getPageType($name)
    {
        $name = strtolower($name);
        $page = $name . '_page';
        if (isset(self::$typeCache[$page])) {
            return self::$typeCache[$page];
        }
        // make Object Type from BuildType for extra dynamicdata object types
        if (in_array($name, self::$extraTypes) || in_array(ucfirst($name), self::$extraTypes)) {
            $type = xarGraphQLBuildType::make_page_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $page);
            }
            self::$typeCache[$page] = $type;
            return $type;
        }
        if (!array_key_exists($name, self::$typeMapper)) {
            throw new Exception("Unknown graphql type: " . $page);
        }
        $clazz = self::getTypeClass(self::$typeMapper[$name]);
        // get page type from existing type class
        $type = $clazz::_xar_get_page_type($page);
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $page);
        }
        self::$typeCache[$page] = $type;
        return $type;
    }

    /**
     * Get GraphQL Input Type by name
     * @param string $name
     * @throws \Exception
     * @return mixed
     */
    public static function getInputType($name)
    {
        $name = strtolower($name);
        $input = $name . '_input';
        if (isset(self::$typeCache[$input])) {
            return self::$typeCache[$input];
        }
        // make Object Type from BuildType for extra dynamicdata object types
        if (in_array($name, self::$extraTypes) || in_array(ucfirst($name), self::$extraTypes)) {
            $type = xarGraphQLBuildType::make_input_type($name);
            if (!$type) {
                throw new Exception("Unknown graphql type: " . $input);
            }
            self::$typeCache[$input] = $type;
            return $type;
        }
        if (!array_key_exists($name, self::$typeMapper)) {
            throw new Exception("Unknown graphql type: " . $input);
        }
        $clazz = self::getTypeClass(self::$typeMapper[$name]);
        // get input type from existing type class
        $type = $clazz::_xar_get_input_type($input);
        if (!$type) {
            throw new Exception("Unknown graphql type: " . $input);
        }
        self::$typeCache[$input] = $type;
        return $type;
    }

    /**
     * Get class where the GraphQL Type is defined
     * @param string $type
     * @return string
     */
    public static function getTypeClass($type)
    {
        static $classMapper = [
            'querytype' => xarGraphQLQueryType::class,
            'dummytype' => xarGraphQLDummyType::class,
            'buildtype' => xarGraphQLBuildType::class,
            'basetype' => xarGraphQLBaseType::class,
            'sampletype' => xarGraphQLSampleType::class,
            'objecttype' => xarGraphQLObjectType::class,
            'propertytype' => xarGraphQLPropertyType::class,
            'accesstype' => xarGraphQLAccessType::class,
            'keyvaltype' => xarGraphQLKeyValType::class,
            'multivaltype' => xarGraphQLMultiValType::class,
            'usertype' => xarGraphQLUserType::class,
            'tokentype' => xarGraphQLTokenType::class,
            'serialtype' => xarGraphQLSerialType::class,
            'mixedtype' => xarGraphQLMixedType::class,
            'mutationtype' => xarGraphQLMutationType::class,
            //'nodetype' => xarGraphQLNodeType::class,
            //'ddnodetype' => xarGraphQLDDNodeType::class,
            'moduleapitype' => xarGraphQLModuleApiType::class,
        ];
        if (!array_key_exists($type, $classMapper) && array_key_exists($type, self::$typeMapper)) {
            $type = self::$typeMapper[$type];
        }
        // from deferred_field_resolver()
        if (!array_key_exists($type, $classMapper) && in_array($type, self::$extraTypes)) {
            $type = 'basetype';
        }
        // from deferred_field_resolver() for unknown type e.g. category
        if (!array_key_exists($type, $classMapper)) {
            $type = 'basetype';
        }
        return $classMapper[$type];
    }

    /**
     * Type config decorator for Query and Object types when using BuildSchema
     * @param array<string, mixed> $typeConfig
     * @param mixed $typeDefinitionNode
     * @param mixed $allNodesMap
     * @return mixed
     */
    public static function type_config_decorator($typeConfig, $typeDefinitionNode, $allNodesMap)
    {
        $name = $typeConfig['name'];
        // https://github.com/diasfs/graphql-php-resolvers/blob/master/src/FieldResolver.php
        // $typeConfig['resolveField'] = function($value, $args, $ctx, $info) use ($resolver) {
        //     return static::ResolveField($value, $args, $ctx, $info, $resolver);
        // };
        // @checkme forget about trying to override individual field resolve functions here - use fieldspecs later
        if (self::hasType($name)) {
            $type = strtolower($name);
            //$clazz = self::getTypeClass($type);
            //if ($clazz !== "xarGraphQLBaseType" && method_exists($clazz, "_xar_get_type_config")) {
            //    xarGraphQL::tracePath("type config $name defined in $clazz");
            //    $classConfig = $clazz::_xar_get_type_config($name);
            //    //return $classConfig;
            //}
        }
        // @todo skip this and override default field resolver in executeQuery, or use one in basetype?
        if ($name == 'Query') {
            xarGraphQL::tracePath("query config $name");
            //$fields = $typeConfig['fields']();
            //xarGraphQL::tracePath("query config fields " . implode(',', array_keys($fields)));
            //$typeConfig['fields'] = static function () use ($name) {
            //    $typeDef = xarGraphQLBuildType::object_type_definition($name);
            //    //return $typeDef->getFields();
            //    return $typeDef;
            //};
            // @checkme not possible to override page/list/item resolvers in child class by type here
            $typeConfig['resolveField'] = xarGraphQLBuildType::_xar_query_field_resolver($name);
        } elseif ($name == 'Mutation') {
            xarGraphQL::tracePath("mutation config $name");
            // @checkme not possible to override create/update/delete resolvers in child class by type here
            $typeConfig['resolveField'] = xarGraphQLBuildType::_xar_mutation_field_resolver($name);
        } else {
            xarGraphQL::tracePath("type config $name");
            //$typeConfig['fields'] = static function () use ($name) {
            //    $typeDef = xarGraphQLBuildType::object_type_definition($name);
            //    return $typeDef->getFields();
            //};
            $typeConfig['resolveField'] = xarGraphQLBuildType::object_type_resolver($name);
        }
        return $typeConfig;
    }

    /**
     * Summary of getTypeMapper
     * @return array<string, string>
     */
    public static function getTypeMapper()
    {
        return self::$typeMapper;
    }

    /**
     * Summary of getExtraTypes
     * @return array<string>
     */
    public static function getExtraTypes()
    {
        return self::$extraTypes;
    }

    /**
     * Summary of setExtraTypes
     * @param array<string> $extraTypes
     * @return void
     */
    public static function setExtraTypes($extraTypes)
    {
        self::$extraTypes = $extraTypes;
    }

    /**
     * Summary of addExtraType
     * @param string $type
     * @return void
     */
    public static function addExtraType($type)
    {
        if (in_array($type, self::$extraTypes)) {
            return;
        }
        self::$extraTypes[] = $type;
    }

    /**
     * Summary of findExtraTypes
     * @param ?array<string> $objectNames
     * @return array<string>
     */
    public static function findExtraTypes($objectNames = null)
    {
        $extraTypes = [];
        if (!empty($objectNames)) {
            foreach ($objectNames as $name) {
                if (str_contains($name, '.')) {
                    continue;
                }
                $type = xarGraphQLInflector::singularize($name);
                if (self::hasType($type)) {
                    continue;
                }
                $extraTypes[] = $type;
            }
        }
        return $extraTypes;
    }
}
