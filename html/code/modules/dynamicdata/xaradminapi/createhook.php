<?php

sys::import('modules.dynamicdata.class.hookobservers.itemcreate');
use Xaraya\DataObject\HookObservers\ItemCreate;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_adminapi_createhook(array $args = [])
{
    return ItemCreate::run($args);
}
