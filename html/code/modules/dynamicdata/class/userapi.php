<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use Xaraya\DataObject\Traits\UserApiInterface;
use Xaraya\DataObject\Traits\UserApiTrait;
use xarController;
use xarDB;
use xarMLS;
use xarMod;
use FunctionNotFoundException;
use sys;

sys::import('modules.dynamicdata.class.traits.userapi');

/**
 * Handle (traditional) DD user api functions via module class
 * Note: this does not replace the direct use of object methods
 */
class UserApi implements UserApiInterface
{
    /** @use UserApiTrait<Module> */
    use UserApiTrait;

    /**
     * Summary of other
     * @param array<mixed> $args
     * @return mixed
     */
    public function other(array $args = [])
    {
        $args['handled'] = 'other';
        // call other methods from the Module() class via ->getModule() here
        $args['parent'] = $this->getModule()::class;
        return $args;
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
            } catch (FunctionNotFoundException) {
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
                    'title' => xarMLS::translate('View #(1)', $row['objectlabel']),
                    'url' => xarController::URL('dynamicdata', 'user', 'view', ['itemtype' => $row['itemtype']]),
                ];
            }
        }
        return $types;
    }
}
