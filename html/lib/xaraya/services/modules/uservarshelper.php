<?php

/**
 * Modules Service Helper for Module User Variables
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

/**
 * Modules Service Helper for Module User Variables
 */
class UserVarsHelper extends ItemVarsHelper
{
    public const SLICE = 'modules.user';

    public function get(string $modName, string $varName, mixed $userId = null): mixed
    {
        // --- LEGACY METHOD BODY ---
        // If id not specified take the current user
        $user = $this->getParent()->user($userId);

        // Anonymous user always uses the module default setting
        if (!$user->isLoggedIn()) {
            return parent::get($modName, $varName, null);
        }
        $userId = $user->getCurrentId();

        return parent::get($modName, $varName, $userId);
        // --- END LEGACY METHOD BODY ---
        // return xarModUserVars::get($modName, $varName, $userId);
    }

    public function set(string $modName, string $varName, mixed $value, mixed $userId = null): bool
    {
        // --- LEGACY METHOD BODY ---
        // If no id specified assume current user
        $user = $this->getParent()->user($userId);

        // For anonymous users no preference can be set
        // MrB: should we raise an exception here?
        if (!$user->isLoggedIn()) {
            return false;
        }
        $userId = $user->getCurrentId();

        return parent::set($modName, $varName, $value, $userId);
        // --- END LEGACY METHOD BODY ---
        // return xarModUserVars::set($modName, $varName, $value, $userId);
    }

    public function delete(string $modName, string $varName, mixed $userId = null): bool
    {
        // --- LEGACY METHOD BODY ---
        // If id is not set assume current user
        $user = $this->getParent()->user($userId);

        // Deleting for anonymous user is useless return true
        // MrB: should we continue, can't harm either and we have
        //      a failsafe that records are deleted, bit dirty, but
        //      it would work.
        if (!$user->isLoggedIn()) {
            return true;
        }
        $userId = $user->getCurrentId();

        return parent::delete($modName, $varName, $userId);
        // --- END LEGACY METHOD BODY ---
        // return xarModUserVars::delete($modName, $varName, $userId);
    }
}
