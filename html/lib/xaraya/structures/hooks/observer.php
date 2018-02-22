<?php
/**
 * @package core\hooks
 * @subpackage hooks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

sys::import('xaraya.structures.events.observer');

abstract class HookObserver extends EventObserver implements ixarEventObserver
{
    public $module = "modules";
}
?>
