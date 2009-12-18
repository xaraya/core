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

sys::import('xaraya.mapper.controllers.short');

class RolesShortController extends ShortActionController
{

    function decode()
    {
        $data = array();
        $token1 = $this->firstToken();
        switch ($token1) {
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
    
    function encode($request)
    {  
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
                if(!empty($moduleload)) {
                    // Note: this handles usermenu requests for hooked modules (including roles itself).
                    unset($args['moduleload']);
                    $path[] = $moduleload;
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

        return parent::encode($request);
    }
    
}
?>