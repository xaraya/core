<?php
/**
 * Admin interface for the commerce module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Commerce Module
 * @author Marc Lutolf
 *  -----------------------------------------------------------------------------------------
 *  based on:
 *  (c) 2003 XT-Commerce
 *  (c) 2003  nextcommerce (product_reviews_info.php,v 1.12 2003/08/17); www.nextcommerce.org
 *  (c) 2002-2003 osCommerce(product_reviews_info.php,v 1.47 2003/02/13); www.oscommerce.com
 *  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
*/

/**
 * the main administration function
 */
function products_admin_main()
{
   // Security Check
//    if(!xarSecurityCheck('EditProducts')) return;

    xarSessionSetVar('products_statusmsg', xarML('Products Main Menu',
                    'products'));

    if(!xarVarFetch('branch', 'str', $branch,   "start", XARVAR_NOT_REQUIRED)) {return;}

    if (xarModGetVar('adminpanels', 'overview') == 0) {
        return array();
    }
    else {
        switch(strtolower($branch)) {
            case 'start':
                xarResponseRedirect(xarModURL('products', 'admin', 'start'));
                break;
        }
   }
}
?>