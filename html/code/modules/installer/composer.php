<?php
/**
 * Composer script event handler
 *
 * @package modules\installer\installer
 * @subpackage composer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */
use Composer\Script\Event;

class xarInstallComposer extends xarObject
{
    public static function eventHandler(Event $event)
    {
        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $extra = $package->getExtra();
        if (empty($extra) || empty($extra["xaraya"])) {
            return;
        }
        var_dump($extra);
        $arguments = $event->getArguments();
        var_dump($arguments);
    }
}
