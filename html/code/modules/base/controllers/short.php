<?php
/**
 * Base Action Controller class
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
 * /base/
 * /base/[pagenâme]
 *
**/

sys::import('xaraya.mapper.controllers.short');

class BaseShortController extends ShortActionController
{
    function decode(Array $data=array())
    {
        $token = $this->firstToken();
        switch ($token) {
            case 'admin':
                return parent::decode($data);
            break;

            case 'login':
                $data['func'] = 'main';
                $data['page'] = $token;
            break;

            default:
                $data['func'] = 'main';
            break;
        }
        return $data;
    }
    
    public function encode(xarRequest $request)
    {  
        $params = $request->getURLParams();
        $path = array();
        switch($request->getFunction()) {
            case 'main':
                // Note : if your main function calls some other function by default,
                // you should set the path to directly to that other function
                $path[] = '';
                break;
            default:
                $path[] = '';
                break;
        }
        
        // Send the processed args back
        $request->setFunctionArgs($path);
        // Remove the processed args (in this case all of them)
        $request->setURLParams();
        return parent::encode($request);
    }    
}
?>