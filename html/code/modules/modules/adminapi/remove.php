<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use ixarMod;
use Exception;

/**
 * modules adminapi remove function
 * @extends MethodClass<AdminApi>
 */
class RemoveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove a module
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id of the module
     * string   $args['name'] module's name
     * @return bool|void true on success, false on failure
     * @see AdminApi::remove()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Security Check
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Remove variables and module

        // Get module information
        if (isset($name)) {
            $regid = $this->mod()->getRegID($name);
        }
        $modinfo = $this->mod()->getInfo($regid);

        //TODO: Add check if there is any dependents
        // Make the whole thing atomic

        // If the files have been removed, the module will now also be removed from the db
        if ($modinfo['state'] == ixarMod::STATE_MISSING_FROM_UNINITIALISED
            || $modinfo['state'] == ixarMod::STATE_MISSING_FROM_INACTIVE
            || $modinfo['state'] == ixarMod::STATE_MISSING_FROM_ACTIVE
            || $modinfo['state'] == ixarMod::STATE_MISSING_FROM_UPGRADED) {

            // All cleanup needs to happen before a module entry is removed
            $this->events()->notify('ModRemove', $modinfo['name'], $this->getContext());
            // this is now handled by the modules module ModRemove event observer
            //xar::mod($modinfo['name'])->flushVars();

            // Remove the module itself
            try {
                $dbconn = $this->db()->getConn();
                $tables = $this->db()->getTables();
                $dbconn->begin();
                $query = "DELETE FROM $tables[modules] WHERE regid = ?";
                $dbconn->Execute($query, [$modinfo['regid']]);
                $dbconn->commit();
            } catch (Exception $e) {
                $dbconn->rollback();
                throw $e;
            }
        } else {
            // Module deletion function
            $adminapi->executeinitfunction(['regid' => $regid, 'function' => 'delete']);

            // All cleanup needs to happen before a module entry is removed
            $this->events()->notify('ModRemove', $modinfo['name'], $this->getContext());
            // this is now handled by the modules module ModRemove event observer
            //xar::mod($modinfo['name'])->flushVars();

            // Update state of module
            $adminapi->setstate(['regid' => $regid,'state' => ixarMod::STATE_UNINITIALISED]);
        }

        // Delete any masks still around
        // this is now handled by the modules module ModRemove event observer
        // xarMasks::removemasks($modinfo['name']);
        // this is now handled by the modules module ModRemove event observer
        // $this->mod()->callHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

        //
        // Delete block details for this module.
        //
        // Get block types.
        // this is now handled by the modules module ModRemove event observer
        /*
        $blocktypes = $this->mod()->apiFunc('blocks', 'user', 'getallblocktypes',
                                    array('module' => $modinfo['name']));

        // Delete block types.
        if (is_array($blocktypes) && !empty($blocktypes)) {
            foreach($blocktypes as $blocktype) {
                $this->mod()->apiFunc('blocks', 'admin', 'delete_type', $blocktype);
            }
        }
        */
        // this is now handled by the modules module ModRemove event observer
        /*
        $defaultmod = $this->mod()->getVar('defaultmodule');
        if ($modinfo['name'] == $defaultmod) {
            $this->mod()->setVar('defaultmodule','base');
        }
        */

        return true;
    }
}
