<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Services\ModulesInterface;
use Xaraya\Services\ServiceFactory;

/**
 * For documentation purposes only - available via ModuleBridgeTrait
 */
interface ModuleBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseModulePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildModulePath
     * @param string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string;

    /**
     * Summary of runModuleGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runModuleGuiRequest($vars, $query): ?string;

    /**
     * Summary of runModuleApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runModuleApiRequest($vars, $query): mixed;
}

/**
 * Handle Module requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
trait ModuleBridgeTrait
{
    protected ?ModulesInterface $xarMod = null;

    public function mod(): ModulesInterface
    {
        $this->xarMod ??= ServiceFactory::getModulesService($this);
        return $this->xarMod;
    }

    /**
     * Summary of parseModulePath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseModulePath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        $params = [];
        if (strlen($path) > strlen($prefix) && str_starts_with($path, $prefix . '/')) {
            $pieces = explode('/', substr($path, strlen($prefix) + 1));
            // {prefix}/{module} = user main
            $params['module'] = $pieces[0];
            if ($params['module'] == 'object') {
                // see DataObjectBridgeTrait with prefix /object
                $handler = new DataObjectRequest();
                return $handler->parseDataObjectPath($path, $query, $prefix . '/object');
            }
            if (count($pieces) == 2) {
                // {prefix}/{module}/{func} = user view, display, ...
                $params['type'] = 'user';
                $params['func'] = $pieces[1];
            } elseif (count($pieces) > 2) {
                // {prefix}/{module}/{type}/{func} = admin main, new, config, ...
                $params['type'] = $pieces[1];
                $params['func'] = $pieces[2];
            }
        }
        // add remaining query params to path params
        $params = array_merge($params, $query);
        return $params;
    }

    /**
     * Summary of buildModulePath
     * @param string $module
     * @param ?string $type
     * @param string|int|null $func
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     * @todo do we want to keep this static?
     */
    public function buildModulePath(string $module = 'base', ?string $type = null, string|int|null $func = null, array $extra = [], string $prefix = ''): string
    {
        if ($module == 'object') {
            $itemid = $extra['itemid'] ?? null;
            unset($extra['itemid']);
            // see DataObjectBridgeTrait with prefix /object
            $prefix .= '/object';
            $handler = new DataObjectRequest();
            return $handler->buildDataObjectPath($type, $func, $itemid, $extra, $prefix);
        }
        // see xarServer::getModuleURL()
        $uri = $prefix;
        // {prefix}/{module} = user main
        $uri .= '/' . $module;
        if (empty($type) || $type == 'user') {
            if (!empty($func) && $func != 'main') {
                // {prefix}/{module}/{func} = user view, display, ...
                $uri .= '/' . $func;
            }
        } else {
            $uri .= '/' . $type;
            if (empty($func)) {
                $func = 'main';
            }
            // {prefix}/{module}/{type}/{func} = admin main, new, config, ...
            $uri .= '/' . $func;
        }
        if (!empty($extra)) {
            $uri .= '?' . http_build_query($extra);
        }
        return $uri;
    }

    /**
     * Summary of runModuleGuiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return string|null
     */
    public function runModuleGuiRequest($vars, $query): ?string
    {
        return $this->mod()->guiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'main', $query);
    }

    /**
     * Summary of runModuleApiRequest
     * @param array<string, mixed> $vars
     * @param array<string, mixed> $query
     * @return mixed
     */
    public function runModuleApiRequest($vars, $query): mixed
    {
        return $this->mod()->apiFunc($vars['module'], $vars['type'] ?? 'user', $vars['func'] ?? 'getitemtypes', $query);
    }
}
