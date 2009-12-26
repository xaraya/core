<?php
/**
 * Base Action Controller class
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage controllers
 * @author Marc Lutolf <mfl@netspan.ch>
**/

sys::import('xaraya.mapper.controllers.base');
sys::import('xaraya.mapper.controllers.interfaces');

class DefaultActionController extends BaseActionController implements iController
{    
    public $separator = '&';

    function decode(Array $data=array())
    {
        xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $module, NULL, XARVAR_NOT_REQUIRED);
        if (null != $module) {
            $data['module'] = $module;
            xarVarFetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $data['type'], xarController::$request->getType(), XARVAR_NOT_REQUIRED);
            xarVarFetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $data['func'], xarController::$request->getFunction(), XARVAR_NOT_REQUIRED);
        }
        xarVarFetch('object', 'regexp:/^[a-z][a-z_0-9]*$/', $object, NULL, XARVAR_NOT_REQUIRED);
        if (null != $object) {
            $data['object'] = $object;
            xarVarFetch('method', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $data['method'], xarController::$request->getMethod(), XARVAR_NOT_REQUIRED);
        }
        return $data;
    }
    
    public function encode(xarRequest $request)
    {
        $path[$request->getModuleKey()] = $request->getModule();
        $path[$request->getTypeKey()] = $request->getType();
        $path[$request->getFunctionKey()] = $request->getFunction();
//        $path = $path + $request->getURLParams();
        $path = xarURL::addParametersToPath($path, '', xarController::$delimiter, $this->separator);
        return $path;
    }

    public function getActionString(xarRequest $request)       
    { 
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $actionstring = substr($request->getURL(), strlen($initialpath));
        return $actionstring;
    }

    public function getInitialPath(xarRequest $request)
    {  
        return '';
    }           
}
?>