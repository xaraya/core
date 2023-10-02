<?php

sys::import('modules.dynamicdata.class.hookobservers.modulemodifyconfig');
use Xaraya\DataObject\HookObservers\ModuleModifyconfig;

/**
 * @deprecated 2.4.1 replaced with hookobserver classes
 */
function dynamicdata_admin_modifyconfighook(array $args = [])
{
    return ModuleModifyconfig::run($args);
}
