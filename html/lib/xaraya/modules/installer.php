<?php

/**
 * Handle module installer functions
 *
 * Usage:
 * ```
 * # installer.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\InstallerClass;
 *
 * /**
 *  * Handle module installer functions
 *  * @extends InstallerClass<Module>
 *  *\/
 * class Installer extends InstallerClass
 * {
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

/**
 * Handle module installer functions
 * @template TModule of ModuleInterface|null
 */
class InstallerClass implements InstallerInterface
{
    /** @use InstallerTrait<TModule> */
    use InstallerTrait;

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
     * @return bool true on success, false on failure
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
}
