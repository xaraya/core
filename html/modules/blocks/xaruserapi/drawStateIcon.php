<?php
/**
 * Draw state icon
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/*
 * @author Jim McDonald, Paul Rosania
*/
function blocks_userapi_drawStateIcon($args)
{
    if (xarUserIsLoggedIn() && !empty($args['bid'])) {
        if(xarModAPIFunc('blocks', 'user', 'getState', $args) == true) {
            $output = '<a href="'.xarModURL('blocks', 'user', 'changestatus', array('bid' => $args['bid'])).'"><img src="modules/blocks/xarimages/'.xarModGetVar('blocks', 'blocksuparrow').'" border="0" alt="" /></a>';
        } else {
            $output = '<a href="'.xarModURL('blocks', 'user', 'changestatus', array('bid' => $args['bid'])).'"><img src="modules/blocks/xarimages/'.xarModGetVar('blocks', 'blocksdownarrow').'" border="0" alt="" /></a>';
        }
        return $output;
    }
}

?>
