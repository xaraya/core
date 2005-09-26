<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_admin_importpropertytypes ($args)
{
    
    $args['flush'] = 'false';
    $success = xarModAPIFunc('dynamicdata','admin','importpropertytypes', $args);
    
    return array();
}
?>