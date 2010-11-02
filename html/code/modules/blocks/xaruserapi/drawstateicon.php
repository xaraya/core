<?php
/**
 * Draw state icon
 * @package modules
 * @subpackage blocks module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * @author Jim McDonald, Paul Rosania
*/
function blocks_userapi_drawStateIcon($args)
{
    if (xarUserIsLoggedIn() && !empty($args['bid'])) {
        if(xarMod::apiFunc('blocks', 'user', 'getState', $args) == true) {
            $output = '<a href="'.xarModURL('blocks', 'user', 'changestatus', array('bid' => $args['bid'])).'"><img src="' .  sys::code() . 'modules/blocks/xarimages/'.xarModVars::get('blocks', 'blocksuparrow').'" border="0" alt=""/></a>';
        } else {
            $output = '<a href="'.xarModURL('blocks', 'user', 'changestatus', array('bid' => $args['bid'])).'"><img src="' . sys::code() . 'modules/blocks/xarimages/'.xarModVars::get('blocks', 'blocksdownarrow').'" border="0" alt=""/></a>';
        }
        return $output;
    }
}

?>