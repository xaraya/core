<?php

sys::import('modules.dynamicdata.class.hookobservers.itemdelete');
use Xaraya\DataObject\HookObservers\ItemDelete;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_adminapi_deletehook(array $args = [], $context = null)
{
    return ItemDelete::run($args, $context);
}
