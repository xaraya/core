<?php
/** 
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
    extract($args);
    
    if (!xarVarValidate('int:1', $bid, true)) {$bid = 0;}
    if (!xarVarValidate('str:1', $name, true)) {$name = '';}
    
    if (empty($bid) && empty($name)) {
        // No identifier provided.
        throw new EmptyParameterException('name or bid');
    }

    // The getall function does the main work.
    if (!empty($bid)) {
      // CHECKME: & removed below
        $instances = xarModAPIfunc('blocks', 'user', 'getall', array('bid' => $bid));
    } else {
        // CHECKME: & removed below 
        $instances = xarModAPIfunc('blocks', 'user', 'getall', array('name' => $name));
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
