<?php
/**
 * Base Action Controller class
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

sys::import('xaraya.mapper.controllers.interfaces');

class BaseActionController extends xarObject
{
    /** @var mixed $controller */
    private $controller;
    private xarRequest $request;
    private string $actionstring;
    public string $separator      = '/';
    public string $module;
    public string $modulealias;

    public function __construct(xarRequest $request = null)
    {
        $this->request = $request;
        $this->actionstring = $request->getActionString();
        $this->module = $this->request->getModule();
        $this->modulealias = $this->request->getModuleAlias();
    }

    public function run(xarRequest $request = null, xarResponse $response = null): void
    {
        // Get the part of the URL we will tokenize and decode
        $this->actionstring = $request->getActionString();
        // Add the results of decoding to the params we already got when the request was created
        $args = $this->decode() + $request->getFunctionArgs();
        // Allocate those params we can to module/type/function and store the rest as FunctionArgs in the request
        $this->chargeRequest($request, $args);
        // Add all the params we have to the GET array in case they needed to be called in a standard way. e.g. xarVar::fetch
        $_GET = $_GET + $args;
        // Now get the output
        if ($request->getModule() == 'object') {
            sys::import('xaraya.objects');
            $response->output = xarDDObject::guiMethod($request->getType(), $request->getFunction(), $request->getFunctionArgs());
        } else {
            $response->output = xarMod::guiFunc($request->getModule(), $request->getType(), $request->getFunction(), $request->getFunctionArgs());
        }
    }

    /**
     * Summary of decode
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function decode(array $data = [])
    {
        return $data;
    }

    /** @return mixed */
    public function getController()
    {
        return $this->controller;
    }
    /** @return xarRequest */
    public function getRequest()
    {
        return $this->request;
    }
    //function getOutput()       { return $response->output;}
    /** @return string|bool */
    public function firstToken()
    {
        return strtok($this->actionstring, $this->separator);
    }
    /** @return string|bool */
    public function nextToken()
    {
        return strtok($this->separator);
    }

    /**
     * Summary of chargeRequest
     * @param xarRequest $request
     * @param array<string, mixed> $params
     * @return void
     */
    public function chargeRequest(xarRequest $request, array $params = []): void
    {
        if (isset($params['module'])) {
            $request->setModule($params['module']);
            unset($params['module']);
        }
        if (isset($params['type'])) {
            $request->setType($params['type']);
            unset($params['type']);
        }
        if (isset($params['func'])) {
            $request->setFunction($params['func']);
            unset($params['func']);
        }
        if (isset($params['object'])) {
            $request->setType($params['object']);
            unset($params['object']);
        }
        if (isset($params['method'])) {
            $request->setFunction($params['method']);
            unset($params['method']);
        }
        $request->setFunctionArgs($params);
    }

}
