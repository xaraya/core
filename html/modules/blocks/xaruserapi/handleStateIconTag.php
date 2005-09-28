<?php
/** 
 * File: $Id$
 *
 * Handle the icon tag state
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_handleStateIconTag($args)
{
    return "echo xarModAPIFunc('blocks', 'user', 'drawStateIcon', array('bid' => \$bid)); ";
}

?>