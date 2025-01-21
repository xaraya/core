<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
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
sys::import('xaraya.services.hasdatabasetrait');
use Xaraya\Services\HasDatabaseStaticTrait;

/**
 * Handle (traditional) DD user api functions via module class
 * Note: this does not replace the direct use of object methods
 *
 * @method mixed countitems(array $args = []) utility function to count the number of items held by this module
 * @method mixed decodeShorturl(array $args = []) extract function and arguments from short URLs for this module, and pass - them back to xarGetRequestInfo()
 * @method mixed dropdownlist(array $args = []) Get an array of DD items (itemid => fieldvalue) for use in dropdown lists - E.g. to specify the parent of an item for parent-child relationships, - add a dynamic data field of type Dropdown List with the configuration rule - xarMod::apiFunc('dynamicdata','user','dropdownlist',array('field' => 'name','module' => 'dynamicdata','itemtype' => 2))
 * @method mixed encodeShorturl(array $args = []) return the path for a short URL to xarController::URL for this module
 * @method mixed getcontext(array $args = []) get an array of context data for a module using dynamicdata
 * @method mixed getfield(array $args = []) get a specific item field
 * @method mixed getitem(array $args = []) get all data fields (dynamic or static) for an item - (identified by module + item type + item id or table + item id)
 * @method mixed getitemfields(array $args = []) utility function to pass item field definitions to whoever
 * @method mixed getitemfordisplay(array $args = []) return the properties for an item
 * @method mixed getitemlinks(array $args = []) utility function to pass individual item links to whoever
 * @method mixed getitems(array $args = []) get all dynamic data fields for a list of items - (identified by module + item type or table, and item ids or other search criteria)
 * @method mixed getitemsforview(array $args = []) return the properties and items
 * @method mixed getitemtypes(array $args = []) Utility function to retrieve the list of itemtypes of this module (if any).
 * @method mixed getmoduleitemtypes(array $args = []) utility function to retrieve the list of item types of a module (if any)
 * @method mixed getobject(array $args = []) get a dynamic object
 * @method mixed getobjectlist(array $args = []) get a dynamic object list
 * @method mixed getobjects(array $args = []) get the list of defined dynamic objects
 * @method mixed getprop(array $args = []) Get field properties for a specific module + item type
 * @method mixed getproperty(array $args = []) get a dynamic property
 * @method mixed getproptypes(array $args = []) Get the list of defined property types
 * @method mixed showdisplay(array $args = []) Display an item in a template
 * @method mixed showview(array $args = []) // TODO: move this to some common place in Xaraya (base module ?) - list some items in a template
 * @method mixed testCall(array $args = [])
 * @extends
 */
class UserApi implements UserApiInterface
{
    /** @use UserApiTrait<Module> */
    use UserApiTrait;
    use HasDatabaseStaticTrait;

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
     * @todo move this elsewhere?
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
            $xartable =  self::xarDB()->getTables();

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

            $dbconn = self::xarDB()->getConn();
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars, self::xarDB()->getFetchAssoc());

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
