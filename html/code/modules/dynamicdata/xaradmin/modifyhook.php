<?php

sys::import('modules.dynamicdata.class.hookobservers.itemmodify');
use Xaraya\DataObject\HookObservers\ItemModify;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_admin_modifyhook(array $args = [])
{
    return ItemModify::run($args);
}
