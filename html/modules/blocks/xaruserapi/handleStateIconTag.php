<?php
/**
 * Handle the icon tag state
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
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