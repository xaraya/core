<?php

/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * Class to handle variables linked to an item of a module.
 *
 * Bit different than the others, so lets just start and see
 * where we end up
 */

use Xaraya\Services\Modules\ItemVarsHelper;
use Xaraya\Services\xar;

interface IxarModItemVars
{
    public static function get($scope, $name, $itemid = null);
    public static function set($scope, $name, $value, $itemid = null);
    public static function delete($scope, $name, $itemid = null);
}

/**
 * @package core\variables
 * @subpackage variables
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @deprecated 2.8.4 use xar::mod()->*ItemVar() instead
 */
class xarModItemVars extends xarVars implements IxarModItemVars
{
    protected static ?ItemVarsHelper $itemvars = null;

    protected static function itemvars()
    {
        if (!isset(self::$itemvars)) {
            $itemvars = xar::getServicesClass()->service('modules.item');
            assert($itemvars instanceof ItemVarsHelper);
            self::$itemvars = $itemvars;
        }
        return self::$itemvars;
    }

    public static function get($scope, $name, $itemid = null)
    {
        return self::itemvars()->get($scope, $name, $itemid);
    }

    public static function set($scope, $name, $value, $itemid = null)
    {
        return self::itemvars()->set($scope, $name, $value, $itemid);
    }

    public static function delete($scope, $name, $itemid = null)
    {
        return self::itemvars()->delete($scope, $name, $itemid);
    }
}
