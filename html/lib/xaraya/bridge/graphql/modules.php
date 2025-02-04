<?php

/**
 * Class for handling GraphQL queries on Module APIs
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

namespace Xaraya\Bridge\GraphQL\Types;

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use sys;
use Exception;

/**
 * See xardocs/graphql.txt for class structure
 * @uses \sys::autoload()
 */
class GraphQLModules
{
    // @todo analyze response and mediatype + create result type per function if needed
    /** @var array<string, mixed> */
    protected static $modules = [];
    /** @var array<string, mixed> */
    protected static $queries = [];
    /** @var array<string, mixed> */
    protected static $mutations = [];

    /**
     * Summary of getModules
     * @return array<mixed>
     */
    public static function getModules()
    {
        self::loadModules();
        return self::$modules;
    }

    /**
     * Summary of getQueries
     * @return array<mixed>
     */
    public static function getQueries()
    {
        self::loadModules();
        return self::$queries;
    }

    /**
     * Summary of getMutations
     * @return array<mixed>
     */
    public static function getMutations()
    {
        self::loadModules();
        return self::$mutations;
    }

    /**
     * Summary of loadModules
     * @param array<string, mixed> $config
     * @return void
     */
    public static function loadModules($config = [])
    {
        if (!empty(self::$modules)) {
            return;
        }
        self::$modules = self::loadModuleConfig($config);
        foreach (self::$modules as $itemid => $info) {
            self::parseModuleInfo($info);
        }
        GraphQLHandler::setTimer('modules');
    }

    /**
     * Summary of loadObjectConfig
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function loadModuleConfig($config = [])
    {
        $configFile = sys::varpath() . '/cache/api/graphql_modules.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        if (!empty($config['modules'])) {
            return $config['modules'];
        }
        return [];
    }

    /**
     * Summary of parseModuleInfo
     * @param array<string, mixed> $info
     * @return void
     */
    public static function parseModuleInfo($info)
    {
        $module = $info['module'];
        foreach ($info['apilist'] as $api => $item) {
            self::parseModuleApi($module, $api, $item);
        }
    }

    /**
     * Summary of parseModuleApi
     * @param string $module
     * @param string $api
     * @param array<string, mixed> $item
     * @throws \Exception
     * @return void
     */
    public static function parseModuleApi($module, $api, $item)
    {
        if (isset($item['enabled']) && empty($item['enabled'])) {
            return;
        }
        $item['module'] = $module;
        $item['type'] ??= 'rest';
        // @checkme 'name' is a tricky part for GraphQL type definitions - use 'func' here instead to be sure
        $item['func'] = $item['name'] ?? $api;
        $item['method'] ??= 'get';
        // @checkme handle default args if specified in getlist.php
        $item['default'] = $item['args'] ?? [];
        $item['args'] = 'mixed';
        // @checkme add paging parameters if specified in getlist.php
        $item['paging'] ??= false;
        // @todo parse optional response - and how to match with result type, e.g. DD getobjects -> ['object']
        $item['result'] = $item['response'] ?? 'mixed';
        if (str_contains($item['path'], '/')) {
            $name = $module . '_' . $api;
            // @checkme support optional part(s) after path, either with {path}[/{more}] or with {path:.+}
            if (str_contains($item['path'], '{')) {
                $found = preg_match_all('/\{([^}]+)\}/', $item['path'], $matches);
                if (empty($found)) {
                    throw new Exception('Invalid path parameter in path ' . $item['path'] . ' for rest api ' . $api . ' in module ' . $module);
                }
                // @checkme assuming we don't have more complex parameters already, we simply add them first
                $path_params = [];
                foreach ($matches[1] as $part) {
                    $path_params[] = $part;
                }
                $item['parameters'] ??= [];
                $item['parameters'] = array_merge($path_params, $item['parameters']);
            }
        } else {
            $name = $module . '_' . $item['path'];
        }
        if ($item['method'] == 'post' || !empty($item['requestBody'])) {
            if (!empty($item['requestBody']) && !empty($item['requestBody']['application/json'])) {
                $item['args'] = self::parseApiParameters($item['requestBody']['application/json']);
            }
            if (!array_key_exists($name, self::$mutations)) {
                self::$mutations[$name] = $item;
            }
        } elseif (!array_key_exists($name, self::$queries)) {
            if (isset($item['parameters'])) {
                $item['args'] = self::parseApiParameters($item['parameters']);
            }
            self::$queries[$name] = $item;
        }
    }

    /**
     * Summary of parseApiParameters
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public static function parseApiParameters($parameters)
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
}
