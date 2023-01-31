<?php
/**
 * Default Action Controller class
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

sys::import('xaraya.mapper.controllers.base');
sys::import('xaraya.mapper.controllers.interfaces');

class DefaultActionController extends BaseActionController implements iController
{    
    public $separator = '&';

    function decode(Array $data=array())
    {
        xarVar::fetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $module, NULL, xarVar::NOT_REQUIRED);
        if (null != $module) {
            xarVar::fetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $data['type'], xarController::$request->getType(), xarVar::NOT_REQUIRED);
            xarVar::fetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $data['func'], xarController::$request->getFunction(), xarVar::NOT_REQUIRED);
        }
        xarVar::fetch('object', 'regexp:/^[a-z][a-z_0-9]*$/', $object, NULL, xarVar::NOT_REQUIRED);
        if (null != $object) {
            $data['object'] = $object;
            xarVar::fetch('method', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $data['method'], xarController::$request->getMethod(), xarVar::NOT_REQUIRED);

            // No admin equivalent for objectURL for now
            if ((xarController::$request->getModule() == 'object') && xarController::$request->getType() == 'admin') {
                xarController::$request->setModule('dynamicdata');
                $data['func'] = 'view';
            }
        }
        return $data;
    }
    
    public function encode(xarRequest $request)
    {
        if ($request->getModule() =='object') {
            $pathargs['object'] = $request->getType();
            $pathargs['method'] = $request->getFunction();
        } else {
            $pathargs[$request->getModuleKey()] = $request->getModule();
            $pathargs[$request->getTypeKey()] = $request->getType();
            $pathargs[$request->getFunctionKey()] = $request->getFunction();
        }
        $pathargs = $pathargs + $request->getFunctionArgs();
        $path = xarURL::addParametersToPath($pathargs, '', xarController::$delimiter, $this->separator);
        return $path;
    }

    public function getActionString(xarRequest $request)       
    { 
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $actionstring = substr($request->getURL() ?? '', strlen($initialpath));
        return $actionstring;
    }

    public function getInitialPath(xarRequest $request)
    {  
        return '';
    }           
}

