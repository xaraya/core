<?php

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