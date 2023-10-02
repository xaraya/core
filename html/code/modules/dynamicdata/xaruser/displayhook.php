<?php

sys::import('modules.dynamicdata.class.hookobservers.itemdisplay');
use Xaraya\DataObject\HookObservers\ItemDisplay;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_user_displayhook(array $args = [])
{
    return ItemDisplay::run($args);
}
