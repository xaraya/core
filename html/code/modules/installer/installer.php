<?php

/**
 * Handle module installer functions
 *
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer;

use Xaraya\Modules\InstallerClass;

/**
 * Handle module installer functions - not used for installer module
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * Install Xaraya
     * @author Johnny Robeson
     * @return bool
     */
    public function init()
    {
        // Initialisation successful
        return true;
    }

    /**
     * Upgrade this module from an old version
     * @param string $oldversion
     * @return bool true on success, false on failure
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '2.0.0':
                break;
        }
        return true;
    }

    /**
     * Delete this module
     * @return bool
     */
    public function delete()
    {
        // this module cannot be removed
        return false;
    }
}
