<?php

function dynamicdata_userapi_getall($args)
{
    return xarModAPIFunc('dynamicdata','user','getitem',$args);
}

?>