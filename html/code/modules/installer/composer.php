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
use Composer\InstalledVersions;
use Composer\Installer\PackageEvent;

const MATCHES = '/xaraya/';

/**
 * See https://getcomposer.org/doc/articles/scripts.md#defining-scripts
 */
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

    public static function postPackageInstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        if (!preg_match(MATCHES, $package->getName())) {
            return;
        }
        // @todo create symlink from vendor/xaraya/<name> to html/code/modules/<name>
        echo "Installed: " . $package->getName() . " " . $package->getType() . "\n";
    }

    public static function postPackageUpdate(PackageEvent $event)
    {
        $package = $event->getOperation()->getTargetPackage();
        if (!preg_match(MATCHES, $package->getName())) {
            return;
        }
        // @todo update symlink from vendor/xaraya/<name> to html/code/modules/<name>
        echo "Updated: " . $package->getName() . " " . $package->getType() . "\n";
    }

    public static function postPackageUninstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        if (!preg_match(MATCHES, $package->getName())) {
            return;
        }
        // @todo remove symlink from vendor/xaraya/<name> to html/code/modules/<name>
        echo "Uninstalled: " . $package->getName() . " " . $package->getType() . "\n";
    }

    public static function createModuleSymLinks(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $modulesDir = dirname(__DIR__);
        foreach (static::listModules() as $package) {
            [$prefix, $module] = explode('/', $package);
            if (is_link($modulesDir . '/' . $module)) {
                echo "Module $module is already linked\n";
                continue;
            }
            if (is_dir($modulesDir . '/' . $module)) {
                echo "Module $module is already copied\n";
                continue;
            }
            echo "Creating symbolic link for module $module\n";
            symlink($vendorDir . '/' . $package, $modulesDir . '/' . $module);
        }
    }

    public static function showModules()
    {
        print_r(static::listModules());
    }

    public static function listModules($type = 'xaraya-module', $matches = MATCHES)
    {
        if (!empty($type)) {
            return array_unique(InstalledVersions::getInstalledPackagesByType($type));
        }
        $packages = InstalledVersions::getInstalledPackages();
        if (!empty($matches)) {
            return array_values(preg_grep($matches, $packages));
        }
        return $packages;
    }

    public static function listPackages($matches = MATCHES)
    {
        return static::listModules(null, $matches);
    }
}
