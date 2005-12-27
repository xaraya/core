<?php
/**
 * Short description of purpose of file
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Commerce Module
 * @author Marc Lutolf
*/

/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 *
*/
function products_user_main()
{
   // Security Check
//    if(!xarSecurityCheck('ViewCommerce')) return;

    xarSessionSetVar('products_statusmsg', xarML('Products Main Menu',
                    'products'));

    if (xarModGetVar('adminpanels', 'overview') == 0 && !isset($branch)) {
        return array();
    } else {
        if(!xarVarFetch('branch', 'str', $branch,   "start", XARVAR_NOT_REQUIRED)) {return;}

        switch(strtolower($branch)) {
            case 'start':
                xarResponseRedirect(xarModURL('products', 'user', 'start'));
                break;
        }
   }
}
?>