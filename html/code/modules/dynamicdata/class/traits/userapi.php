<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

use xarController;
use xarDB;
use xarMod;
use FunctionNotFoundException;
use sys;

sys::import('modules.dynamicdata.class.traits.itemlinks');

/**
 * For documentation purposes only - available via UserApiTrait
 */
interface UserApiInterface extends ItemLinksInterface
{
    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public static function getModuleObjects(): array;

    /**
     * Get a module's itemtypes
     *
     * @param int $moduleId
     * @param bool $native
     * @param bool $extensions
     * @return array<mixed>
     */
    public static function getModuleItemTypes($moduleId, $native = false, $extensions = true): array;
}

/**
 * Trait to handle generic user api functions for modules with their own DD objects
 *
 * Example:
 * ```
 * use Xaraya\DataObject\Traits\UserApiInterface;
 * use Xaraya\DataObject\Traits\UserApiTrait;
 * use sys;
 *
 * sys::import('modules.dynamicdata.class.traits.userapi');
 *
 * class MyClassApi implements UserApiInterface
 * {
 *     use UserApiTrait;
 *     protected static int $moduleId = 18252;
 *     protected static int $itemtype = 0;
 * }
 * ```
 */
trait UserApiTrait
{
    use ItemLinksTrait;

    /**
     * Utility function to retrieve the DD objects of this module (if any).
     * @return array<string, mixed>
     */
    public static function getModuleObjects(): array
    {
        return static::getItemLinkObjects();
    }

    /**
     * Get a module's itemtypes
     *
     * @param int $moduleId
     * @param bool $native
     * @param bool $extensions
     * @return array<mixed>
     */
    public static function getModuleItemTypes($moduleId, $native = false, $extensions = true): array
    {
        $module = xarMod::getName($moduleId);

        $types = [];
        if ($native) {
            // Try to get the itemtypes
            try {
                // @todo create an adaptor class for procedural getitemtypes in modules
                $types = xarMod::apiFunc($module, 'user', 'getitemtypes', []);
            } catch (FunctionNotFoundException $e) {
                // No worries
            }
        }
        // @todo combine with getItemTypes()
        if ($extensions) {
            // Get all the objects at once
            xarMod::loadDbInfo('dynamicdata', 'dynamicdata');
            $xartable =  xarDB::getTables();

            $dynamicobjects = $xartable['dynamic_objects'];

            $bindvars = [];
            $query = "SELECT id AS objectid,
                             name AS objectname,
                             label AS objectlabel,
                             module_id AS moduleid,
                             itemtype AS itemtype
                      FROM $dynamicobjects ";

            $query .= " WHERE module_id = ? ";
            $bindvars[] = (int) $moduleId;

            $dbconn = xarDB::getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);

            // put in itemtype as key for easier manipulation
            while ($result->next()) {
                $row = $result->fields;
                $types [$row['itemtype']] = [
                    'label' => $row['objectlabel'],
                    'title' => xarML('View #(1)', $row['objectlabel']),
                    'url' => xarController::URL('dynamicdata', 'user', 'view', ['itemtype' => $row['itemtype']]),
                ];
            }
        }
        return $types;
    }
}
