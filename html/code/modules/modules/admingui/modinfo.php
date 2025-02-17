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
use xarConfigVars;
use xarController;
use xarHooks;
use xarMod;
use xarSecurity;
use xarTwigTpl;
use xarVar;
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
        if (!xarSecurity::check('ViewModules')) {
            return;
        }

        $data = [];

        if (!xarVar::fetch('id', 'notempty', $id)) {
            return;
        }

        // obtain maximum information about module
        $modinfo = xarMod::getInfo($id);

        // data vars for template
        $data['modid']              = xarVar::prepForDisplay($id);
        $data['modname']            = xarVar::prepForDisplay($modinfo['name']);
        $data['moddescr']           = xarVar::prepForDisplay($modinfo['description']);
        $data['moddispname']        = xarVar::prepForDisplay($modinfo['displayname']);
        $data['moddispdesc']        = xarVar::prepForDisplay($modinfo['displaydescription']);
        $data['modlisturl']         = xarController::URL('modules', 'admin', 'list');

        $aliasesMap = xarConfigVars::get(null, 'System.ModuleAliases');
        $aliases = [];
        foreach ($aliasesMap as $key => $value) {
            if ($value == $data['modname']) {
                $aliases[] = $key;
            }
        }
        $data['aliases']            = !empty($aliases) ? implode(', ', $aliases) : xarML('None');
        $data['moddir']             = sys::code() . 'modules/' . xarVar::prepForDisplay($modinfo['directory']);
        $data['modclass']           = xarVar::prepForDisplay($modinfo['class']);
        $data['modcat']             = xarVar::prepForDisplay($modinfo['category']);
        $data['modver']             = xarVar::prepForDisplay($modinfo['version']);
        $data['modauthor']          = xarVar::prepForDisplay($modinfo['author']);
        $data['modcontact']         = xarVar::prepForDisplay($modinfo['contact']);
        if (!empty($modinfo['dependencyinfo'])) {

            $dependencies = [];
            foreach ($modinfo['dependencyinfo'] as $key => $value) {
                if ($key != 0) {
                    $data['link'] = xarController::URL('modules', 'admin', 'modinfo', ['id' => $key]);
                    $dependencies[] = '<a href="' . $data["link"] . '">' . $value['name'] . '</a>';
                } else {
                    $dependencies[] = $value['name'];
                }
                $data['moddependencies'] = implode(', ', $dependencies);
            }
        } else {
            $data['moddependencies']             = xarML('None');
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
