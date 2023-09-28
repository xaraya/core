<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */

/**
 * Set the state of a module
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        integer  $args['regid'] the module id<br/>
 *        integer  $args['state'] the state
 * @return integer|void state
 * @throws EmptyParameterException
 * @todo Do the db changes in a transaction to completely fail or succeed?
 */
function modules_adminapi_setstate(Array $args=array())
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');
    if (!isset($state)) throw new EmptyParameterException('state');

    // Security Check
    if(!xarSecurity::check('AdminModules')) return;

    // Clear cache to make sure we get newest values
    if (xarVar::isCached('Mod.Infos', $regid)) {
        xarVar::delCached('Mod.Infos', $regid);
    }

    //Get module info
    $modInfo = xarMod::getInfo($regid);

    //Set up database object
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    $oldState = $modInfo['state'];
    $state = (int)$state;

    if ($state == $oldState) return true;
    // Check valid state transition
    switch ($state) {
        case xarMod::STATE_UNINITIALISED:
            // So, we're basically good all the time here?
            if (($oldState == xarMod::STATE_MISSING_FROM_UNINITIALISED) ||
                ($oldState == xarMod::STATE_ERROR_UNINITIALISED)) break;

            if ($oldState != xarMod::STATE_INACTIVE) {
                // New Module
                break;
            }
            break;
        case xarMod::STATE_INACTIVE:
            if (($oldState != xarMod::STATE_UNINITIALISED) &&
                ($oldState != xarMod::STATE_ACTIVE) &&
                ($oldState != xarMod::STATE_MISSING_FROM_INACTIVE) &&
                ($oldState != xarMod::STATE_ERROR_INACTIVE) &&
                ($oldState != xarMod::STATE_UPGRADED)) {
                xarSession::setVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
        case xarMod::STATE_ACTIVE:
            if (($oldState != xarMod::STATE_INACTIVE) &&
                ($oldState != xarMod::STATE_ERROR_ACTIVE) &&
                ($oldState != xarMod::STATE_MISSING_FROM_ACTIVE)) {
                xarSession::setVar('errormsg', xarML('Invalid module state transition'));
                throw new Exception("Setting from $oldState to $state for module $regid failed");
            }
            break;
        case xarMod::STATE_UPGRADED:
            if (($oldState != xarMod::STATE_INACTIVE) &&
                ($oldState != xarMod::STATE_ACTIVE) &&
                ($oldState != xarMod::STATE_ERROR_UPGRADED) &&
                ($oldState != xarMod::STATE_MISSING_FROM_UPGRADED)) {
                xarSession::setVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
    }

    $modulesTable = $xartable['modules'];
    $query = "UPDATE $modulesTable SET state = ? WHERE regid = ?";
    $bindvars = array($state,$regid);
    $dbconn->Execute($query,$bindvars);

    // We're update module state here we must update at least
    // the base info in the cache.
    $modInfo['state']=$state;
    xarVar::setCached('Mod.Infos',$regid,$modInfo);
    xarVar::setCached('Mod.BaseInfos',$modInfo['name'],$modInfo);

    return $state;
}
