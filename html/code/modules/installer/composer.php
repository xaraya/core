<?php
/**
 * Composer script event handler
 *
 * "scripts": {
 *     "post-package-install": "xarInstallComposer::postPackageInstall",
 *     "post-package-update": "xarInstallComposer::postPackageUpdate",
 *     "post-package-uninstall": "xarInstallComposer::postPackageUninstall",
 *     "xar-install-modules": "xarInstallComposer::createModuleSymLinks",
 *     "xar-list-modules": "xarInstallComposer::showModules",
 *     "xar-start-server": [
 *         "Composer\\Config::disableProcessTimeout",
 *         "php -S 0.0.0.0:8080 -t html"
 *     ],
 *     "xar-uninstall-modules": "xarInstallComposer::removeModuleSymLinks"
 * },
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

/**
 * See https://getcomposer.org/doc/articles/scripts.md#defining-scripts
 */
class xarInstallComposer extends xarObject
{
    public const MATCHES = '/xaraya/';

    /**
     * Summary of eventHandler
     * @param Composer\Script\Event $event
     * @return void
     */
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

    /**
     * Summary of postPackageInstall
     * @param Composer\Installer\PackageEvent $event
     * @return void
     */
    public static function postPackageInstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        if (!preg_match(self::MATCHES, $package->getName())) {
            return;
        }
        // @todo create symlink from vendor/xaraya/<name> to html/code/modules/<name>
        echo "Installed: " . $package->getName() . " " . $package->getType() . "\n";
    }

    /**
     * Summary of postPackageUpdate
     * @param Composer\Installer\PackageEvent $event
     * @return void
     */
    public static function postPackageUpdate(PackageEvent $event)
    {
        $package = $event->getOperation()->getTargetPackage();
        if (!preg_match(self::MATCHES, $package->getName())) {
            return;
        }
        // @todo update symlink from vendor/xaraya/<name> to html/code/modules/<name>
        echo "Updated: " . $package->getName() . " " . $package->getType() . "\n";
    }

    /**
     * Summary of postPackageUninstall
     * @param Composer\Installer\PackageEvent $event
     * @return void
     */
    public static function postPackageUninstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        if (!preg_match(self::MATCHES, $package->getName())) {
            return;
        }
        // @todo remove symlink from vendor/xaraya/<name> to html/code/modules/<name>
        echo "Uninstalled: " . $package->getName() . " " . $package->getType() . "\n";
    }

    /**
     * Summary of createModuleSymLinks
     * @param Composer\Script\Event $event
     * @return void
     */
    public static function createModuleSymLinks(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $modulesDir = dirname(__DIR__);
        foreach (static::listModules() as $package) {
            [$prefix, $module] = explode('/', $package);
            if ($module == 'cachemanager') {
                $module = 'xarcachemanager';
            }
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

    /**
     * Summary of removeModuleSymLinks
     * @param Composer\Script\Event $event
     * @return void
     */
    public static function removeModuleSymLinks(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $modulesDir = dirname(__DIR__);
        foreach (static::listModules() as $package) {
            [$prefix, $module] = explode('/', $package);
            if ($module == 'cachemanager') {
                $module = 'xarcachemanager';
            }
            if (!is_link($modulesDir . '/' . $module)) {
                echo "Module $module is already unlinked\n";
                continue;
            }
            if (readlink($modulesDir . '/' . $module) !== $vendorDir . '/' . $package) {
                echo "Module $module is not linked to $vendorDir/$package\n";
                continue;
            }
            echo "Removing symbolic link for module $module\n";
            unlink($modulesDir . '/' . $module);
        }
    }

    /**
     * Summary of showModules
     * @return void
     */
    public static function showModules()
    {
        print_r(static::listModules());
    }

    /**
     * Summary of listModules
     * @param mixed $type
     * @param mixed $matches
     * @return mixed
     */
    public static function listModules($type = 'xaraya-module', $matches = self::MATCHES)
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

    /**
     * Summary of listPackages
     * @param mixed $matches
     * @return mixed
     */
    public static function listPackages($matches = self::MATCHES)
    {
        return static::listModules(null, $matches);
    }

    /**
     * Summary of createPropertySymLinks
     * @param Composer\Script\Event $event
     * @return void
     */
    public static function createPropertySymLinks(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $packageDir = $vendorDir . '/xaraya/properties';
        $propertiesDir = dirname(__DIR__, 2) . '/properties';
        foreach (static::listProperties($packageDir) as $property) {
            if (is_link($propertiesDir . '/' . $property)) {
                echo "Property $property is already linked\n";
                continue;
            }
            if (is_dir($propertiesDir . '/' . $property)) {
                echo "Property $property is already copied\n";
                continue;
            }
            echo "Creating symbolic link for property $property\n";
            symlink($packageDir . '/' . $property, $propertiesDir . '/' . $property);
        }
    }

    /**
     * Summary of removePropertySymLinks
     * @param Composer\Script\Event $event
     * @return void
     */
    public static function removePropertySymLinks(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $packageDir = $vendorDir . '/xaraya/properties';
        $propertiesDir = dirname(__DIR__, 2) . '/properties';
        foreach (static::listProperties($packageDir) as $property) {
            if (!is_link($propertiesDir . '/' . $property)) {
                echo "Property $property is already unlinked\n";
                continue;
            }
            if (readlink($propertiesDir . '/' . $property) !== $packageDir . '/' . $property) {
                echo "Property $property is not linked to $packageDir/$property\n";
                continue;
            }
            echo "Removing symbolic link for property $property\n";
            unlink($propertiesDir . '/' . $property);
        }
    }

    /**
     * Summary of showProperties
     * @param Composer\Script\Event $event
     * @return void
     */
    public static function showProperties(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $packageDir = $vendorDir . '/xaraya/properties';
        print_r(static::listProperties($packageDir));
    }

    /**
     * Summary of listProperties
     * @param string $packageDir
     * @return mixed
     */
    public static function listProperties($packageDir)
    {
        $properties = [];
        if (!is_dir($packageDir)) {
            return $properties;
        }
        $dir = new FilesystemIterator($packageDir);
        foreach ($dir as $file) {
            if ($file->isDir() && $file->getFilename() !== '.git') {
                $properties[] = $file->getFilename();
            }
        }
        return $properties;
    }
}
