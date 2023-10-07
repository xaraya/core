<?php
/**
 * Base Route class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.routers.routes.interfaces');

class xarRoute extends xarObject implements iRoute
{
    protected string $delimiter = "/";
    /** @var xarRequest */
    protected $request;
    /** @var xarDispatcher */
    protected $dispatcher;
    /** @var ?string */
    protected $route = null;
    /** @var array<string, mixed> */
    protected $parts = array();
    /** @var array<string, mixed> */
    protected $defaults = array();
    protected bool $keysSet     = false;
    /** @var ?string */
    protected $matchedPath = null;

    protected string $moduleKey = 'module';
    protected string $typeKey   = 'type';
    protected string $funcKey   = 'func';

    public function __construct(array $defaults = array(), ?xarDispatcher $dispatcher = null)
    {
        $this->defaults += $defaults;
        //if (isset($request)) $this->request = $request;
        if (isset($dispatcher)) {
            $this->dispatcher = $dispatcher;
        }
    }

    /**
     * Set request keys based on values in request object
     *
     * @return void
     */
    protected function setRequestKeys()
    {
        // @todo these are actually never updated
        if (null !== $this->request) {
            if ($this->request->moduleKey) {
                $this->moduleKey   = $this->request->moduleKey;
            }
            if ($this->request->typeKey) {
                $this->typeKey       = $this->request->typeKey;
            }
            if ($this->request->funcKey) {
                $this->funcKey       = $this->request->funcKey;
            }
        }

        $this->defaults += array(
            $this->moduleKey   => xarController::$module,
            $this->typeKey     => xarController::$type,
            $this->funcKey     => xarController::$func,
        );

        $this->keysSet = true;
    }

    public function match(xarRequest $request, bool $partial = false)
    {
        $path = $request->getURL();
        if ($partial) {
            if (isset($this->route) && substr($path, 0, strlen($this->route)) === $this->route) {
                // @fixme does anyone know what this was supposed to do?
                $this->setMatchedPath($this->route);
                return $this->defaults;
            }
        } else {
            if (trim($path, $this->delimiter) == $this->route) {
                return $this->defaults;
            }
        }

        return false;
    }

    /**
     * Summary of setMatchedPath - @todo not used anywhere
     * @param string $matchedPath
     * @return void
     */
    public function setMatchedPath($matchedPath)
    {
        $this->matchedPath = $matchedPath;
    }

    /**
     * Summary of getParts
     * @return array<string, mixed>
     */
    public function getParts()
    {
        return $this->parts;
    }
}
