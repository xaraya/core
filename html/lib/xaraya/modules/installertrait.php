<?php

/**
 * Handle module installer functions
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use xarMod;
use xarModVars;

/**
 * For documentation purposes only - available via InstallerTrait
 */
interface InstallerInterface
{
    /**
     * Configure this module - override this method
     *
     * @return void
     */
    public function configure();

    /**
     * Upgrade this module from an old version - override this method
     *
     * @param string $oldversion
     * @return boolean true on success, false on failure
     */
    public function upgrade($oldversion);
}

/**
 * Trait to handle installer functions
 */
trait InstallerTrait
{
    protected string $moduleName;          // set in constructor by xarMod::getModule()
    /** @var array<string> */
    protected $objects;                    // set in configure() - override this method
    /** @var array<string, mixed> */
    protected $variables;                  // set in configure() - override this method
    /** @var string */
    protected $oldversion;                 // set in configure() - override this method

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->configure();
    }

    /**
     * Configure this module - override this method
     *
     * @return void
     */
    public function configure()
    {
        $this->objects = [
            // add your DD objects here
            //'sample_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /**
     * Upgrade this module from an old version - override this method
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
     * Initialise this module
     *
     * @access public
     * @return  boolean true on success or false on failure
     */
    public function init()
    {
        $module = $this->moduleName;
        $objects = $this->objects ?? [];
        if (!xarMod::apiFunc('modules', 'admin', 'standardinstall', ['module' => $module, 'objects' => $objects])) {
            return false;
        }

        // Set up module variables
        $variables = $this->variables ?? [];
        foreach ($variables as $name => $value) {
            xarModVars::set($module, $name, $value);
        }

        // Installation complete; check for upgrades
        $oldversion = $this->oldversion ?? '2.4.1';
        return $this->upgrade($oldversion);
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
