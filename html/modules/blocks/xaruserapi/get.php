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
    if (is_array($args)) {
        extract($args);
    }

    if (is_numeric($args)) {
        $bid = $args;
    }
    
    // Check parameters.
    if (!xarVarFetch('bid', 'int:1:', $bid)) {return;}

    // The getall function does the main work.
    $instances =& xarModAPIfunc('blocks', 'user', 'getall', array('bid'=>$bid));

    if (isset($instances[$bid])) {
        return $instances[$bid];
    } else {
        return;
    }
}

?>
