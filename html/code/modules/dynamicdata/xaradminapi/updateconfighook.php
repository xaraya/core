<?php

sys::import('modules.dynamicdata.class.hookobservers.moduleupdateconfig');
use Xaraya\DataObject\HookObservers\ModuleUpdateconfig;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_adminapi_updateconfighook(array $args = [])
{
    return ModuleUpdateconfig::run($args);
}
