<?php

sys::import('modules.dynamicdata.class.hookobservers.itemnew');
use Xaraya\DataObject\HookObservers\ItemNew;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_admin_newhook(array $args = [], $context = null)
{
    return ItemNew::run($args, $context);
}
