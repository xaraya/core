<?php

function mail_admin_create($args)
{
    // User requested to create a new mailqueue
    // We have to do 2 things:
    // 1. Create a queue for storage if needed
    // 2. Create a 'record' in the Queue definition object
    // If the first fails for some reason, we do not do the second and return to the edit screen if possible
    return xarModFunc('dynamicdata','admin','create',$args);
}
?>