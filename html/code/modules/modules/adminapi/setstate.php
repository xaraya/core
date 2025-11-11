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
use EmptyParameterException;
use Exception;
use ixarMod;

/**
 * modules adminapi setstate function
 * @extends MethodClass<AdminApi>
 */
class SetstateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Set the state of a module
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the module id<br/>
     * integer  $args['state'] the state
     * @return int|void state
     * @throws \EmptyParameterException
     * @todo Do the db changes in a transaction to completely fail or succeed?
     * @see AdminApi::setstate()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array

        extract($args);

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }
        if (!isset($state)) {
            throw new EmptyParameterException('state');
        }

        // Security Check
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Clear cache to make sure we get newest values
        if ($this->mem()->has('Mod.Infos', $regid)) {
            $this->mem()->del('Mod.Infos', $regid);
        }

        //Get module info
        $modInfo = $this->mod()->getInfo($regid);

        //Set up database object
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $oldState = $modInfo['state'];
        $state = (int) $state;

        if ($state == $oldState) {
            return true;
        }
        // Check valid state transition
        switch ($state) {
            case ixarMod::STATE_UNINITIALISED:
                // So, we're basically good all the time here?
                if (($oldState == ixarMod::STATE_MISSING_FROM_UNINITIALISED)
                    || ($oldState == ixarMod::STATE_ERROR_UNINITIALISED)) {
                    break;
                }

                if ($oldState != ixarMod::STATE_INACTIVE) {
                    // New Module
                    break;
                }
                break;
            case ixarMod::STATE_INACTIVE:
                if (($oldState != ixarMod::STATE_UNINITIALISED)
                    && ($oldState != ixarMod::STATE_ACTIVE)
                    && ($oldState != ixarMod::STATE_MISSING_FROM_INACTIVE)
                    && ($oldState != ixarMod::STATE_ERROR_INACTIVE)
                    && ($oldState != ixarMod::STATE_UPGRADED)) {
                    $this->session()->setVar('errormsg', $this->ml('Invalid module state transition'));
                    return false;
                }
                break;
            case ixarMod::STATE_ACTIVE:
                if (($oldState != ixarMod::STATE_INACTIVE)
                    && ($oldState != ixarMod::STATE_ERROR_ACTIVE)
                    && ($oldState != ixarMod::STATE_MISSING_FROM_ACTIVE)) {
                    $this->session()->setVar('errormsg', $this->ml('Invalid module state transition'));
                    throw new Exception("Setting from $oldState to $state for module $regid failed");
                }
                break;
            case ixarMod::STATE_UPGRADED:
                if (($oldState != ixarMod::STATE_INACTIVE)
                    && ($oldState != ixarMod::STATE_ACTIVE)
                    && ($oldState != ixarMod::STATE_ERROR_UPGRADED)
                    && ($oldState != ixarMod::STATE_MISSING_FROM_UPGRADED)) {
                    $this->session()->setVar('errormsg', $this->ml('Invalid module state transition'));
                    return false;
                }
                break;
        }

        $modulesTable = $xartable['modules'];
        $query = "UPDATE $modulesTable SET state = ? WHERE regid = ?";
        $bindvars = [$state,$regid];
        $dbconn->Execute($query, $bindvars);

        // We're update module state here we must update at least
        // the base info in the cache.
        $modInfo['state'] = $state;
        $this->mem()->set('Mod.Infos', $regid, $modInfo);
        $this->mem()->set('Mod.BaseInfos', $modInfo['name'], $modInfo);

        return $state;
    }
}
