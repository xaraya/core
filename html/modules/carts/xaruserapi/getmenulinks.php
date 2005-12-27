<?php
/**
 * File: $Id$
 *
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
function commerce_userapi_getmenulinks()
{
    $menuLinks[] = array('url'   => xarModURL('commerce','user','start'),
                         'title' => xarML('Enter the shop'),
                         'label' => xarML('Shop'));

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menuLinks;
}
?>