<?php
/**
 * Authsystem Action Controller class
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @author Marc Lutolf <mfl@netspan.ch>
**/

/**
 * Supported URLs :
 *
 * /authsystem/
 * /authsystem/login
 * /authsystem/logout
 * /authsystem/password
 *
**/

sys::import('xaraya.mapper.controllers.short');

class AuthsystemShortController extends ShortActionController
{
    function decode(Array $data=array())
    {
        $token = $this->firstToken();
        switch ($token) {
            case 'admin':
                return parent::decode($data);
            break;

            case 'login':
                $data['func'] = 'showloginform';
            break;

            case 'logout':
                $data['func'] = 'logout';
            break;

            case 'password':
                $data['func'] = 'password';
            break;

            default:
                $data['func'] = 'showloginform';
            break;
        }
        return $data;
    }
    
    public function encode(xarRequest $request)
    {  
        $params = $request->getFunctionArgs();
        $path = array();
        switch($request->getFunction()) {
            case 'main':
                // Note : if your main function calls some other function by default,
                // you should set the path to directly to that other function
                $path[] = '';
                break;
            case 'showloginform':
                $path[] = 'login';
                break;

            case 'logout':
                $path[] = 'logout';
                break;

            case 'password':
                $path[] = 'password';
                break;

            default:
                break;
        }
        
        // Send the processed args back
        $request->setFunctionArgs($path);
        // Remove the processed args (in this case all of them)
        $request->setFunctionArgs();
        return parent::encode($request);
    }    
}
?>