<?php

/**
 * DynamicData handler class for routing & dispatching outside Xaraya
 *
 * @todo experiment using module classes and methods as handler
 */

namespace Xaraya\Modules\DynamicData;

use Xaraya\Routing\ModuleHandler;

/**
 * DynamicData handler class for routing & dispatching outside Xaraya
 *
 * Supported URLs :
 *
 * /dynamicdata/
 * /dynamicdata/admin/... (not used here)
 * /dynamicdata/{entity}/
 * /dynamicdata/{entity}/{itemid} (numeric)
 * /dynamicdata/{entity}/{itemid}/{title}
 * /dynamicdata/{entity}/{action} (non-numeric)
 * /dynamicdata/{entity}/{action}/{itemid}
 */
class DynamicDataHandler extends ModuleHandler
{
    public static string $moduleName = 'dynamicdata';
    /** @var class-string */
    public static string $handlerClass = UserGui::class;
}
