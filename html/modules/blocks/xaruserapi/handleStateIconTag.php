<?php

function blocks_userapi_handleStateIconTag($args)
{
    return "xarModAPILoad('blocks'); echo xarModAPIFunc('blocks', 'user', 'drawStateIcon', array('bid' => \$bid)); ";
}

?>