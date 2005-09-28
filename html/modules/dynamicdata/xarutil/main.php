<?php
/**
 * File: $Id$
 *
 * Main menu for utility functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Main menu for utility functions
 */
function dynamicdata_util_main()
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    xarTplSetPageTemplateName('admin');

    return $data;
}

?>