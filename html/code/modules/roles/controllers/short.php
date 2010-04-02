<?php
/**
 * Roles Action Controller class
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
 * /roles/
 * /roles/123
 * /roles/account
 * /roles/account/[module]
 *
 * /roles/list
 * /roles/list/viewall
 * /roles/list/X
 * /roles/list/viewall/X
 *
 * /roles/password
 * /roles/settings
 * /roles/settings/form (deprecated)
**/

sys::import('xaraya.mapper.controllers.short');

class RolesShortController extends ShortActionController
{
    function decode(Array $data=array())
    {
        $token1 = $this->firstToken();
        switch ($token1) {
            case 'admin':
                return parent::decode($data);
            break;

            case 'account':
                $data['func'] = 'account';
                
                $token2 = $this->nextToken();
                if ($token2 == 'profile')  $data['tab'] = 'profile';
                elseif ($token2 == 'edit')  $data['tab'] = 'basic';
                elseif ($token2)  $data['loadmodule'] = $token2;
            break;

            case 'list':
                $data['func'] = 'view';

                $token2 = $this->nextToken();
                if ($token2 == 'viewall' || !$token2)  $data['phase'] = 'viewall';
                else $data['letter'] = $token2;

                $token3 = $this->nextToken();
                if ($token3)  $data['letter'] = $token3;
            break;

            case 'password':
                $data['func'] = 'lostpassword';
            break;

            case 'settings':
                $data['func'] = 'account';
                $data['tab'] = 'basic';
            break;

            default:
                $data['func'] = 'account';
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
            case 'view':
                $path[] = 'list';
                if (!empty($phase) && $phase == 'viewall') {
                    unset($args['phase']);
                    $path[] = 'viewall';
                }
                if (!empty($letter)) {
                    unset($args['letter']);
                    $path[] = $letter;
                }
                break;

            case 'lostpassword':
                $path[] = 'password';
                break;

             case 'account':
                $path[] = 'account';
                if (!empty($params['tab'])){
                    switch ($params['tab']) {
                        case 'basic': {$path[] = 'edit';break; }
                    }
                }
                break;

              case 'usermenu':
                $path[] = 'settings';
                if (!empty($phase) && ($phase == 'formbasic' || $phase == 'form')) {
                    // Note : this URL format is no longer in use
                    unset($args['phase']);
                    $path[] = 'form';
                }
                break;

              case 'display':
                // check for required parameters
                if (isset($id) && is_numeric($id)) {
                    unset($args['id']);
                    $path[] = $id;
                }
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