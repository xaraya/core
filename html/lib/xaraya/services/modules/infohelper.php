<?php

/**
 * Modules Service Helper for Module Information
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Services\ServiceClass;
use xarClassMap;
use xarMod;
use sys;
use Exception;

/**
 * Modules Service Helper for Module Information
 */
class InfoHelper extends ServiceClass
{
    public const SLICE = 'modules.info';

    public function getName(?int $regID = null): string
    {
        return xarMod::getName($regID);
    }

    public function getID(string $modName): ?int
    {
        return xarMod::getID($modName);
    }

    public function getRegID(string $modName): int
    {
        // avoid getting module id from xarMod::getRegID() here
        $fileInfo = $this->getFileInfo($modName);
        return (int) ($fileInfo['regid'] ?? 0);
    }

    public function getDisplayName(string $modName): string
    {
        return xarMod::getDisplayName($modName);
    }

    public function getDisplayDescription(string $modName): string
    {
        return xarMod::getDisplayDescription($modName);
    }

    /** @return array<string, mixed> */
    public function getFileInfo(string $modName): array
    {
        return xarMod::getFileInfo($modName) ?? [];
    }

    /** @return array<string, mixed> */
    public function getBaseInfo(string $modName): array
    {
        return xarMod::getBaseInfo($modName) ?? [];
    }

    /** @return array<string, mixed> */
    public function getInfo(int $modRegId): array
    {
        return xarMod::getInfo($modRegId);
    }

    /** @return array<string, mixed> */
    public function getTables(string $modName): array
    {
        $result = xarClassMap::findTables($modName);
        if (!empty($result) && class_exists($result['classname'])) {
            $tablesCall = new $result['classname']();
            // @todo pass along the DB prefix to $tablesCall
            return $tablesCall();
        }

        // Load the database definition if required
        try {
            include_once sys::code() . 'modules/' . $modName . '/xartables.php';
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // @todo pass along the DB prefix to $tablefunc
            return $tablefunc();
        }
        return [];
    }

    public function loadDbInfo(string $modName, ?string $modDir = null): mixed
    {
        $modDir ??= $modName;
        return xarMod::loadDbInfo($modName, $modDir);
    }

    public function isAvailable(string $modName): bool
    {
        return xarMod::isAvailable($modName) ? true : false;
    }
}
