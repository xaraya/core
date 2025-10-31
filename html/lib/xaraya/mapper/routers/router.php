<?php

/**
 * Base Router class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

class xarRouter extends xarObject
{
    /** @var array<string, xarRoute> */
    protected $routes       = [];
    protected string $currentRoute = 'default';

    public function addRoute(string $name, xarRoute $route): bool
    {
        $this->routes[$name] = $route;
        return true;
    }

    /**
     * Summary of addDefaultRoutes
     * @return static
     */
    public function addDefaultRoutes()
    {
        if (empty($this->routes['default'])) {
            $dispatcher = xar::ctl()->getDispatcher();

            sys::import('xaraya.mapper.routers.routes.default');
            $route = new DefaultRoute([], $dispatcher);
            $this->routes['default'] = $route;

            sys::import('xaraya.mapper.routers.routes.short');
            $route = new ShortRoute([], $dispatcher);
            $this->routes['short'] = $route;

            /* Add more routes here
            */
        }

        return $this;
    }

    /**
     * Summary of route
     * @param xarRequest $request
     * @return bool
     */
    public function route(xarRequest $request)
    {
        $this->addDefaultRoutes();
        foreach (array_reverse($this->routes) as $name => $route) {
            if ($route->match($request)) {
                $publicproperties = array_keys($request->getPublicProperties());
                foreach ($route->getParts() as $key => $value) {
                    if (in_array($key, $publicproperties)) {
                        $request->$key = $value;
                    }
                }
                $request->setRoute($name);
                $this->currentRoute = $name;
                xar::log()->notice('The route is set: ' . $name);
                return true;
            }
        }
        return false;
    }

    /**
     * Summary of route
     * @checkme $request->setRoute is expecting a string name, not a xarRoute
     * @param ?string $name
     * @return string
     */
    public function getRoute($name = null)
    {
        if (null == $name) {
            return $this->currentRoute;
        }
        //return $this->routes[$name];
        return $name;
    }
}
