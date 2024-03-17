<?php

sys::import('modules.dynamicdata.class.hookobservers.moduleremove');
use Xaraya\DataObject\HookObservers\ModuleRemove;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_adminapi_removehook(array $args = [], $context = null)
{
    return ModuleRemove::run($args, $context);
}
