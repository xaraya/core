<?php
/**
 * Authsystem Action Controller class
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 *
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
            
            case 'auth':
                $data['func'] = 'login';
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
        if ($request->getType() == 'admin') return parent::encode($request);

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
            case 'login':
                $path[] = 'auth';
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
        
        // Encode the processed params
        $request->setFunction($this->getFunction($path));
        
        // Send the unprocessed params back
        $request->setFunctionArgs($params);
        return parent::encode($request);
    }    
}
?>