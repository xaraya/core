<?php

/**
 * Trait to handle installer functions
 *
 * @package core\traits
 * @subpackage traits
 * @category Xaraya Web Applications Framework
 * @version 2.5.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Core\Traits;

use xarMod;
use xarModVars;

/**
 * For documentation purposes only - available via InstallerTrait
 */
interface InstallerInterface
{
    // ...
}

/**
 * Trait to handle installer functions
 */
trait InstallerTrait
{
    protected string $moduleName;          // set in constructor by xarMod::getModule()

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Initialise this module
     *
     * @access public
     * @return  boolean true on success or false on failure
     */
    public function init()
    {
        $module = $this->moduleName;
        $objects = [
            // add your DD objects here
        ];
        if (!xarMod::apiFunc('modules', 'admin', 'standardinstall', ['module' => $module, 'objects' => $objects])) {
            return false;
        }

        // Set up module variables
        xarModVars::set($module, 'hello', 'world');

        // Installation complete; check for upgrades
        return $this->upgrade('2.4.1');
    }

    /**
     * Activate this module
     *
     * @access public
     * @return boolean
     */
    public function activate()
    {
        return true;
    }

    /**
     * Deactivate this module
     *
     * @access public
     * @return boolean
     */
    public function deactivate()
    {
        return true;
    }

    /**
     * Upgrade this module from an old version
     *
     * @param string $oldversion
     * @return boolean true on success, false on failure
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '2.4.1':
                // fall through to next upgrade
            case '2.4.2':
                break;
            default:
                break;
        }
        return true;
    }

    /**
     * Delete this module
     *
     * @return boolean
     */
    public function delete()
    {
        $module = $this->moduleName;
        return xarMod::apiFunc('modules', 'admin', 'standarddeinstall', ['module' => $module]);
    }
}

/**
 * Summary of DefaultInstaller
 */
class DefaultInstaller implements InstallerInterface
{
    use InstallerTrait;
}
