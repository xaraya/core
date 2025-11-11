<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use ModuleNotFoundException;
use ixarMod;
use Xaraya\Modules\InstallerTool;

/**
 * modules admin install function
 * @extends MethodClass<AdminGui>
 */
class InstallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::install()
     */

    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        $installer = InstallerTool::getInstance();
        // Security and sanity checks
        // TODO: check under what conditions this is needed
        //    if (!$this->sec()->confirmAuthKey()) return;

        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }
        $this->var()->find(
            'return_url',
            $return_url,
            'pre:trim:str:1:',
            ''
        );

        // First check for a proper core version
        if (!$installer->checkCore($id)) {
            return $this->tpl()->module('modules', 'user', 'errors', ['layout' => 'invalid_core', 'modname' => $this->mod()->getName($id)]);
        }

        // Next check the modules dependencies
        // TODO: investigate try/catch clause here, it's not trivial
        try {
            $installer->verifydependency($id);

            //Checking if the user has already passed thru the GUI:
            $this->var()->find('command', $command, 'checkbox', false);
        } catch (ModuleNotFoundException $e) {
            $command = false;
        }

        $data['moduledependencies'] = $installer->getalldependencies($id);

        // Finally check the property dependencies
        $this->var()->find('ignore_properties', $ignore_properties, 'int:1:', 0);
        $propdependencies['satisfied'] = [];
        $propdependencies['unsatisfiable'] = [];
        if (isset($data['moduledependencies']['satisfied'])) {
            foreach ($data['moduledependencies']['satisfied'] as $key => $value) {
                $newdependencies = $installer->getpropdependencies($key);
                $propdependencies['satisfied'] += $newdependencies['satisfied'];
                $propdependencies['unsatisfiable'] += $newdependencies['unsatisfiable'];
            }
        }
        if (isset($data['moduledependencies']['satisfiable'])) {
            foreach ($data['moduledependencies']['satisfiable'] as $key => $value) {
                $newdependencies = $installer->getpropdependencies($key);
                $propdependencies['satisfied'] += $newdependencies['satisfied'];
                $propdependencies['unsatisfiable'] += $newdependencies['unsatisfiable'];
            }
        }
        $data['propdependencies'] = $propdependencies;

        //Only show the status screen if there are dependencies that cannot be satisfied
        if (!$command && (!empty($data['moduledependencies']['unsatisfiable']) || !empty($data['propdependencies']['unsatisfiable']))) {
            //Let's make a nice GUI to show the user the options
            $data['id'] = $id;
            //They come in 3 arrays: satisfied, satisfiable and unsatisfiable
            //First 2 have $modInfo under them for each module,
            //3rd has only 'regid' key with the ID of the module

            // get any dependency info on this module for a better message if something is missing
            $thisinfo = $this->mod()->getInfo($id);
            $data['displayname'] = $thisinfo['displayname'];
            if (!empty($thisinfo['dependencyinfo'])) {
                $data['dependencyinfo'] = $thisinfo['dependencyinfo'];
            } elseif (!empty($thisinfo['dependency'])) {
                $data['dependencyinfo'] = $thisinfo['dependency'];
            } else {
                $data['dependencyinfo'] = [];
            }

            $data['authid']       = $this->sec()->genAuthKey();
            $data['return_url'] = $return_url;
            return $data;
        }

        if (!$ignore_properties && !empty($propdependencies['unsatisfied'])) {
            return $data;
        }

        // See if we have lost any modules since last generation
        if (!$installer->checkformissing()) {
            return;
        }

        $this->session()->setVar('installing', true);

        $minfo = $this->mod()->getInfo($id);

        //Bail if we've lost our module
        if ($minfo['state'] != ixarMod::STATE_MISSING_FROM_INACTIVE) {
            //Installs with dependencies, first initialise the necessary dependencies
            //then the module itself
            $installer->installmodule($id);
        }
        // Note: if the module installed successfully, the above method will have already redirected,
        // and thus the following won't be executed
        $this->session()->delVar('installing');

        // set the target location (anchor) to go to within the page
        $target = $minfo['name'];

        if ($this->cache()->withOutput()) {
            $this->cache()->flushPages('modules');
            // a status update might mean a new menulink and new base homepage
            $this->cache()->flushPages('base');
            // a status update might mean a new menulink and new base homepage
            $this->cache()->flushBlocks('base');
        }

        if (empty($return_url)) {
            $return_url = $this->ctl()->getModuleURL('modules', 'admin', 'list', ['state' => 0], null) . '#' . $target;
        }

        $this->ctl()->redirect($return_url);
        return true;
    }
}
