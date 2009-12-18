<?php
/**
 * Short Action Controller class
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

class ShortActionController extends BaseActionController implements iController
{    
    public function decode(xarRequest $request)
    {
        $data = array();
        $token1 = $this->firstToken();
        $token2 = $this->nextToken();
        if ($token2) {
            $request->setType($token1);
            $request->setFunction($token2);
        } else {
            $request->setFunction($token1);
        }
        return true;
    }

    protected function getInitialPath(xarRequest $request)
    {  
        $path = $request->getModule();
        if ('user' != $request->getType()) $path .= xarController::$separator . $request->getType();
        return $path;
    }       
    
    public function encode(xarRequest $request)
    {  
        $path = $this->getInitialPath($request);
        $functionstring = $request->getActionString();
        if (empty($functionstring)) $path .= xarController::$separator . $request->getFunction();
        $path = xarURL::addParametersToPath($request->getURLParams(), $path, xarController::$delimiter, xarController::$separator);
        return xarController::$separator . $path;
    }        

    public function getActionString(xarRequest $request)
    { 
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $actionstring = substr($request->getURL(), strlen($initialpath));
        $actionstring = substr($actionstring,1);
        $actionstring = substr($actionstring,strpos($actionstring,xarController::$separator)+1);
        return $actionstring;
    }
}
?>