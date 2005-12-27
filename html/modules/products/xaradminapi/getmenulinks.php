<?php
/**
 * Pass admin links to the admin menu
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage module name
 * @author Marcel van der Boom <marcel@xaraya.com>
*/


/**
 * Pass individual menu items to the admin menu
 *
 * @return array containing the menulinks for the admin menu items.
 */
function products_adminapi_getmenulinks()
{
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'categories'),
                              'title' => xarML('Manage products and their categories'),
                              'label' => xarML('Categories/Products'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'xsell_products'),
                              'title' => xarML('Manage product cross selling'),
                              'label' => xarML('XSell Products'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'new_attributes'),
                              'title' => xarML('Create product attributes'),
                              'label' => xarML('Attribute Manager'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'products_attributes'),
                              'title' => xarML('Add options to products'),
                              'label' => xarML('Product Options'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'manufacturers'),
                              'title' => xarML('Manage manufacturers'),
                              'label' => xarML('Manufacturers'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'reviews'),
                              'title' => xarML('Manage products reviews'),
                              'label' => xarML('Product Reviews'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'specials'),
                              'title' => xarML('Manage special promotions'),
                              'label' => xarML('Specials Pricing'));
    }
    if (xarSecurityCheck('EditProducts',0)) {
        $menulinks[] = Array('url'   => xarModURL('products',
                                                  'admin',
                                                  'products_expected'),
                              'title' => xarML('Manage products expected'),
                              'label' => xarML('Products Expected'));
    }
    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}
?>