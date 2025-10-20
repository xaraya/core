<?php

/**
 * Handle module installer functions
 *
 * Usage:
 * ```
 * # installer.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\InstallerInterface;
 * use Xaraya\Modules\InstallerTrait;
 * use sys;
 *
 * sys::import('xaraya.modules.installertrait');
 *
 * class Installer implements InstallerInterface
 * {
 *     /** @use InstallerTrait<Module> *\/
 *     use InstallerTrait;
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use xarMod;
use xarModVars;
use sys;

sys::import('xaraya.modules.servicestrait');

/**
 * Module class supports installer (api) methods - available via InstallerTrait
 */
interface InstallerInterface extends ApiModuleServicesInterface
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
 * @template TModule of ModuleInterface|null
 */
trait InstallerTrait
{
    /** @use ModuleServicesTrait<TModule> */
    use ModuleServicesTrait;

    /** @var array<string> */
    protected $objects;                    // set in configure() - override this method
    /** @var array<string, mixed> */
    protected $variables;                  // set in configure() - override this method
    /** @var string */
    protected $oldversion;                 // set in configure() - override this method

    /**
     * Configure this module - override this method
     *
     * @return void
     */
    public function configure()
    {
        //$this->setModType('installer');
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
                // fall through to next upgrade
            case '2.8.1':
                // fall through to next upgrade
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
        $module = $this->getModName();
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
        $module = $this->getModName();
        return xarMod::apiFunc('modules', 'admin', 'standarddeinstall', ['module' => $module]);
    }
}
