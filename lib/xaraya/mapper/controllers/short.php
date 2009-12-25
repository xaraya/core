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
    public $separator = '/';
    
    function decode(Array $data=array())
    {
        $data['module'] = xarController::$request->getModule();
        $token = $this->firstToken();
        if ($token == 'admin') {
            $data['type'] = $token;
            $token = $this->nextToken();
            // If no function was passed we get the default
            if (!$token) $token = xarController::$func;
        }
        $data['func'] = $token;
        return $data;
    }

    public function encode(xarRequest $request)
    {  
        $path = $this->getInitialPath($request);
        $functionstring = $request->getFunctionArgs();
        if (empty($functionstring))  $path .= $this->separator . $request->getFunction();
        $path .= $this->separator . implode($this->separator, $request->getFunctionArgs());
        $path= trim($path,$this->separator);
        return $this->separator . $path;
    }        

    public function getActionString(xarRequest $request)
    { 
        $initialpath = xarServer::getBaseURL() . $request->entryPoint;
        $actionstring = substr($request->getURL(), strlen($initialpath));
        $actionstring = substr($actionstring,1);
        $actionstring = substr($actionstring,strpos($actionstring,$this->separator)+1);
        return $actionstring;
    }

    public function getInitialPath(xarRequest $request)
    {  
        $path = $request->getModule();
        if ('user' != $request->getType()) $path .= $this->separator . $request->getType();
        return $path;
    }       
}
?>