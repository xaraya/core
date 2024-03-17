<?php

sys::import('modules.dynamicdata.class.hookobservers.itemupdate');
use Xaraya\DataObject\HookObservers\ItemUpdate;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_adminapi_updatehook(array $args = [], $context = null)
{
    return ItemUpdate::run($args, $context);
}
