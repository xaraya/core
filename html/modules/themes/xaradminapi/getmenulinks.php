<?php
/**
* File: $Id$
*
* Utility function to pass individual menu items to the main menu
*
* @package Xaraya eXtensible Management System
* @copyright (C) 2003 by the Xaraya Development Team.
* @license GPL <http://www.gnu.org/licenses/gpl.html>
* @link http://www.xaraya.com
*
* @subpackage Themes
* @author Marty Vance
*/
/**
* utility function pass individual menu items to the main menu
*
* @author the Example module development team
* @returns array
* @return array containing the menulinks for the main menu items.
*/
function themes_adminapi_getmenulinks()
{
    $menulinks = array();
    // Security Check
    if (!xarSecurityCheck('AdminTheme',0)) return;

    // Generate security key - redundant here TODO: remove
    // $data['authid'] = xarSecGenAuthKey();


    $menulinks[] = array(   'url'   => xarModURL('themes', 'admin', 'list'),
    'title' => xarML('View installed themes on the system'),
    'label' => xarML('View Themes'));

    // addition by sw@telemedia.ch (Simon Wunderlin)
    // as per http://bugs.xaraya.com/show_bug.cgi?id=1162
    // added and commited by <andyv>
    // TODO: add credits in changelist.. John?
    $menulinks[] = array(   'url'   => xarModURL('themes', 'admin', 'listtpltags'),
    'title' => xarML('View the registered template tags.'),
    'label' => xarML('Template Tags'));

    // css configurations, viewer and editor (AndyV - corecss scenario)
    // lets make these links only available when css class lib is loaded
    //if(class_exists("xarCSS")){
    //    $menulinks[] = array(   'url'   => xarModURL('themes', 'admin', 'cssconfig'),
    //   'title' => xarML('View and configure Xaraya Cascading Style Sheets'),
    //    'label' => xarML('Manage CSS'));
    //}

    $menulinks[] = array(   'url'   => xarModURL('themes', 'admin', 'modifyconfig'),
    'title' => xarML('Modify the configuration of the themes module'),
    'label' => xarML('Modify Config'));


    return $menulinks;
}

?>