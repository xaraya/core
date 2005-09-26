<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/* Handle the icon tag state
 *
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_handleStateIconTag($args)
{
    return "echo xarModAPIFunc('blocks', 'user', 'drawStateIcon', array('bid' => \$bid)); ";
}

?>