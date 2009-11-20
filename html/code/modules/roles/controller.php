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

sys::import('xaraya.controllers.action');

class RolesActionController extends ActionController
{

    function assemble()
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
                if ($token2 == 'viewall')  $data['phase'] = 'viewall';
                else $data['letter'] = $token2;

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
        $this->run($data);
        
    }
    
}
?>