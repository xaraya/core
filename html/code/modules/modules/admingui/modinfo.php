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
use xarHooks;
use xarTwigTpl;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin modinfo function
 * @extends MethodClass<AdminGui>
 */
class ModinfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View complete module information/details
     * function passes the data to the template
     * opens in new window when browser is javascript enabled
     * @author Xaraya Development Team
     * @access public
     * @return array|void data for the template display
     * @todo some facelift
     * @see AdminGui::modinfo()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('ViewModules')) {
            return;
        }

        $data = [];

        $this->var()->get('id', $id, 'notempty');

        // obtain maximum information about module
        $modinfo = $this->mod()->getInfo($id);

        // data vars for template
        $data['modid']              = \xarVarPrep::forDisplay($id);
        $data['modname']            = \xarVarPrep::forDisplay($modinfo['name']);
        $data['moddescr']           = \xarVarPrep::forDisplay($modinfo['description']);
        $data['moddispname']        = \xarVarPrep::forDisplay($modinfo['displayname']);
        $data['moddispdesc']        = \xarVarPrep::forDisplay($modinfo['displaydescription']);
        $data['modlisturl']         = $this->ctl()->getModuleURL('modules', 'admin', 'list');

        $aliasesMap = $this->config()->getVar('System.ModuleAliases');
        $aliases = [];
        foreach ($aliasesMap as $key => $value) {
            if ($value == $data['modname']) {
                $aliases[] = $key;
            }
        }
        $data['aliases']            = !empty($aliases) ? implode(', ', $aliases) : $this->ml('None');
        $data['moddir']             = sys::code() . 'modules/' . \xarVarPrep::forDisplay($modinfo['directory']);
        $data['modclass']           = \xarVarPrep::forDisplay($modinfo['class']);
        $data['modcat']             = \xarVarPrep::forDisplay($modinfo['category']);
        $data['modver']             = \xarVarPrep::forDisplay($modinfo['version']);
        $data['modauthor']          = \xarVarPrep::forDisplay($modinfo['author']);
        $data['modcontact']         = \xarVarPrep::forDisplay($modinfo['contact']);
        if (!empty($modinfo['dependencyinfo'])) {

            $dependencies = [];
            foreach ($modinfo['dependencyinfo'] as $key => $value) {
                if ($key != 0) {
                    $data['link'] = $this->ctl()->getModuleURL('modules', 'admin', 'modinfo', ['id' => $key]);
                    $dependencies[] = '<a href="' . $data["link"] . '">' . $value['name'] . '</a>';
                } else {
                    $dependencies[] = $value['name'];
                }
                $data['moddependencies'] = implode(', ', $dependencies);
            }
        } else {
            $data['moddependencies']             = $this->ml('None');
        }

        $data['namespace'] = $modinfo['namespace'] ?? '';
        $data['twigtemplates'] = $modinfo['twigtemplates'] ?? false;
        $data['twigextension'] = $modinfo['twigextension'] ?? '.html.twig';
        $data['twigenabled'] = false;
        if (!empty($data['twigtemplates'])) {
            sys::import('xaraya.bridge.templates.twigtpl');
            if (xarTwigTpl::hasTwigEnvironment()) {
                $templatesDir = xarTwigTpl::getTwigTemplatesDir();
                if (is_dir($templatesDir)) {
                    $data['twigenabled'] = true;
                }
            }
        }
        $modname = $modinfo['name'];
        $hookobservers = xarHooks::getObserverModules($modname);
        if (!empty($hookobservers[$modname]) && !empty($hookobservers[$modname]['scopes'])) {
            $data['hookobservers'] = $hookobservers[$modname]['scopes'];
        }

        return $data;
    }
}
