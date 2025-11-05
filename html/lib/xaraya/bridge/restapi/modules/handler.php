<?php

/**
 * @package core\bridge
 * @subpackage restapi
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Bridge\RestAPI;

use Xaraya\Services\xar;
use xarRoles;
use xarSecurity;
use sys;
use ForbiddenOperationException;
use Exception;

/**
 * Class to handle Module REST API calls
 */
class ModuleAPIHandler extends RestAPIHandler
{
    /** @var array<string, mixed> */
    public static $modules = [];

    /**
     * Summary of getModuleURL
     * @param ?string $module
     * @param ?string $api
     * @param array<string, mixed> $args
     * @return string
     */
    public function getModuleURL($module = null, $api = null, $args = [])
    {
        if (empty($module)) {
            return $this->getBaseURL('/modules');
        }
        if (empty($api)) {
            return $this->getBaseURL('/modules', $module);
        }
        return $this->getBaseURL('/modules', $module . '/' . $api);
    }

    /**
     * Summary of getModules
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function getModules($args = [])
    {
        $this->loadModules();
        $result = ['items' => [], 'count' => count(self::$modules)];
        foreach (self::$modules as $itemid => $item) {
            $item['apilist'] = array_keys($item['apilist']);
            $item['_links'] = ['self' => ['href' => $this->getModuleURL($item['module'])]];
            array_push($result['items'], $item);
        }
        return $result;
    }

    /**
     * Summary of getModuleApis
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function getModuleApis($args)
    {
        $module = $args['path']['module'];
        if (!$this->hasModule($module)) {
            return ['method' => 'getModuleApis', 'args' => $args, 'error' => 'Unknown module'];
        }
        $result = ['module' => $module, 'apilist' => [], 'count' => 0];
        $apilist = $this->getModuleApiList($module);
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            $item['name'] = $api;
            $item['path'] = $this->getModuleURL($module, $item['path']);
            $result['apilist'][] = $item;
        }
        $result['count'] = count($result['apilist']);
        return $result;
    }

    /**
     * Summary of getModuleCall
     * @param array<string, mixed> $args
     * @uses xar::mod()->init()
     * @uses xar::user()->init()
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public function getModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // support optional part(s) after path, either with {path}/{more:.+} or with {path:.+}
        // see workflow restapi: 'path' => 'tracker/{workflow}/{subjectId}/{trackerId}'
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'get', $more);
        if (empty($func)) {
            return ['method' => 'getModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        xar::mod()->init();
        xar::user()->init();
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser();
            // @checkme assume we have a security mask here
            if (is_string($func['security'])) {
                $role = xarRoles::getRole($userId);
                $rolename = $role->getName();
                $pass = xarSecurity::check($func['security'], 0, 'All', 'All', $func['module'], $rolename);
                // @todo verify access for user based on what?
            } else {
                $pass = true;
            }
            if (!$pass) {
                throw new ForbiddenOperationException();
            }
        }
        if (empty($func['caching'])) {
            self::enableCache(false);
        }
        $context = $this->getContext();
        // @checkme how to save this in case of caching?
        if (!empty($func['mediatype'])) {
            $context['mediatype'] = $func['mediatype'];
            // don't save request in the context for now, unless we really need it later...
            //if (!empty($context['request'])) {
            //    $context['request'] = ($context['request'])->withAttribute('mediaType', $func['mediatype']);
            //}
        }
        // @checkme pass all query args from handler here?
        $params = $args['query'] ?? [];
        if (!empty($func['args'])) {
            if (!empty($more)) {
                // @checkme path params overwrite query params - but what about default args?
                $params = array_merge($params, $func['args']);
            } else {
                // @checkme query params overwrite default args
                $params = array_merge($func['args'], $params);
            }
        }
        // context for core services is set in handler
        return xar::mod()->apiFunc($func['module'], $func['type'], $func['name'], $params);
    }

    /**
     * Summary of postModuleCall
     * @param array<string, mixed> $args
     * @uses xar::mod()->init()
     * @uses xar::user()->init()
     * @throws \ForbiddenOperationException
     * @return mixed
     */
    public function postModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // support optional part(s) after path, either with {path}/{more:.+} or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'post', $more);
        if (empty($func)) {
            return ['method' => 'postModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        // this contains any POSTed args from rst.php
        if (empty($args['input'])) {
            $args['input'] = [];
        }
        xar::mod()->init();
        xar::user()->init();
        if (!empty($func['security'])) {
            // verify that the cookie corresponds to an authorized user (with minimal core load) or exit - see whoami
            $userId = $this->checkUser();
            // @checkme assume we have a security mask here
            if (is_string($func['security'])) {
                $role = xarRoles::getRole($userId);
                $rolename = $role->getName();
                $pass = xarSecurity::check($func['security'], 0, 'All', 'All', $func['module'], $rolename);
                // @todo verify access for user based on what?
            } else {
                $pass = true;
            }
            if (!$pass) {
                throw new ForbiddenOperationException();
            }
        }
        $context = $this->getContext();
        if (!empty($func['mediatype'])) {
            $context['mediatype'] = $func['mediatype'];
            // don't save request in the context for now, unless we really need it later...
            //if (!empty($context['request'])) {
            //    $context['request'] = ($context['request'])->withAttribute('mediaType', $func['mediatype']);
            //}
        }
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        $params = $args['input'] ?? [];
        if (!empty($more) && !empty($func['args'])) {
            $params = array_merge($params, $func['args']);
        }
        // context for core services is set in handler
        return xar::mod()->apiFunc($func['module'], $func['type'], $func['name'], $params);
    }

    /**
     * Summary of putModuleCall
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function putModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // support optional part(s) after path, either with {path}/{more:.+} or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'put', $more);
        if (empty($func)) {
            return ['method' => 'putModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        throw new Exception('Unsupported method PUT for module api');
    }

    /**
     * Summary of deleteModuleCall
     * @param array<string, mixed> $args
     * @throws \Exception
     * @return mixed
     */
    public function deleteModuleCall($args)
    {
        $module = $args['path']['module'];
        $path = $args['path']['path'];
        // support optional part(s) after path, either with {path}/{more:.+} or with {path:.+}
        $more = $args['path']['more'] ?? '';
        $func = $this->getModuleApiFunc($module, $path, 'delete', $more);
        if (empty($func)) {
            return ['method' => 'deleteModuleCall', 'args' => $args, 'error' => 'Unknown module api'];
        }
        throw new Exception('Unsupported method DELETE for module api');
    }

    /**
     * Summary of hasModule
     * @param string $module
     * @return bool
     */
    public function hasModule($module)
    {
        $this->loadModules();
        if (empty(self::$config) || empty(self::$config['modules']) || empty(self::$config['modules'][$module])) {
            return false;
        }
        return true;
    }

    /**
     * Summary of getModuleApiList
     * @param string $module
     * @return array<string, mixed>
     */
    public function getModuleApiList($module)
    {
        if (!$this->hasModule($module)) {
            return [];
        }
        return self::$modules[$module]['apilist'];
    }

    /**
     * Summary of getModuleApiFunc
     * @param string $module
     * @param string $path
     * @param string $method
     * @param ?string $more
     * @throws \Exception
     * @return array<string, mixed>|null
     */
    public function getModuleApiFunc($module, $path, $method = 'get', $more = null)
    {
        if (!$this->hasModule($module)) {
            return null;
        }
        $apilist = $this->getModuleApiList($module);
        if (!empty($more)) {
            // @checkme sort by decreasing path length
            uasort($apilist, function ($a, $b) {
                $lena = strlen($a['path']);
                $lenb = strlen($b['path']);
                return $lenb <=> $lena;
            });
        }
        foreach ($apilist as $api => $item) {
            if (isset($item['enabled']) && empty($item['enabled'])) {
                continue;
            }
            if (empty($more) && $item['path'] == $path && $item['method'] == $method) {
                $item['module'] ??= $module;
                $item['type'] ??= 'rest';
                $item['name'] ??= $api;
                // @checkme allow default args to start with
                $item['args'] ??= [];
                $item['caching'] ??= ($method == 'get') ? true : false;
                return $item;
            }
            // support optional part(s) after path, either with {path}/{more:.+} or with {path:.+}
            // see workflow restapi: 'path' => 'tracker/{workflow}/{subjectId}/{trackerId}'
            if (!empty($more) && strncmp($item['path'], $path . '/', strlen($path) + 1) === 0 && $item['method'] == $method) {
                // @checkme assuming only more path parameter(s) in module paths for now... {type}/{key}/{code}
                $more_params = explode('/', substr($item['path'], strlen($path) + 1));
                $more_values = explode('/', $more);
                if (count($more_values) != count($more_params)) {
                    continue;
                }
                $item['module'] ??= $module;
                $item['type'] ??= 'rest';
                $item['name'] ??= $api;
                // @checkme allow default args to start with
                $item['args'] ??= [];
                $item['caching'] ??= ($method == 'get') ? true : false;
                $i = 0;
                foreach ($more_params as $path_param) {
                    if (empty($path_param)) {
                        continue;
                    }
                    if (!str_starts_with($path_param, '{') && !str_ends_with($path_param, '}')) {
                        // @checkme how do we keep track of fixed parts of the path here?
                        continue;
                    }
                    if (!str_starts_with($path_param, '{') || !str_ends_with($path_param, '}')) {
                        throw new Exception('Invalid path parameter in ' . $item['path']);
                    }
                    $path_param = substr($path_param, 1, -1);
                    // @checkme path params overwrite default args
                    $item['args'][$path_param] = $more_values[$i];
                    $i += 1;
                }
                return $item;
            }
        }
        return null;
    }

    /**
     * Summary of loadModules
     * @param array<string, mixed> $config
     * @return void
     */
    public function loadModules($config = [])
    {
        if (!empty(self::$modules)) {
            return;
        }
        self::$config['modules'] = self::loadModuleConfig($config);
        self::$modules = self::$config['modules'];
        $this->setTimer('modules');
    }

    /**
     * Summary of loadModuleConfig
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function loadModuleConfig($config = [])
    {
        $configFile = sys::varpath() . '/cache/api/restapi_modules.json';
        if (empty($config) && file_exists($configFile)) {
            $contents = file_get_contents($configFile);
            $config = json_decode($contents, true);
        }
        if (!empty($config['modules'])) {
            return $config['modules'];
        }
        return self::getDefaultModules();
    }

    /**
     * Summary of getDefaultModules
     * @uses xar::mod()->init()
     * @return array<string, mixed>
     */
    public static function getDefaultModules()
    {
        $modulelist = ['dynamicdata'];
        $default = [];
        xar::mod()->init();
        foreach ($modulelist as $module) {
            $default[$module] = [
                'module' => $module,
                'apilist' => xar::mod()->apiFunc($module, 'rest', 'getlist'),
            ];
        }
        return $default;
    }
}
