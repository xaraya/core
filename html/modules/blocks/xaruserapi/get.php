<?php
/** 
 * File: $Id$
 *
 * Retrieve a block instance raw data.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_get($args)
{
    if (is_array($args)) {extract($args);}
    if (is_numeric($args)) {$bid = $args;}
    if (is_string($args)) {$name = $args;}
    
    if (!xarVarValidate('int:1', $bid, true)) {$bid = 0;}
    if (!xarVarValidate('str:1', $name, true)) {$name = '';}
    
    if (empty($bid) && empty($name)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing bid or name');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // The getall function does the main work.
    if (!empty($bid)) {
        $instances =& xarModAPIfunc('blocks', 'user', 'getall', array('bid' => $bid));
    } else {
        $instances =& xarModAPIfunc('blocks', 'user', 'getall', array('name' => $name));
    }

    // If exactly one row was found then return it.
    if (count($instances) == 1) {
        $instance = array_pop($instances);
        return $instance;
    } else {
        return;
    }
}

?>
