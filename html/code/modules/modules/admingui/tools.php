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
use xarController;
use xarMod;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin tools function
 * @extends MethodClass<AdminGui>
 */
class ToolsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Tools to build and verify modules elements
     * @author Xaraya Development Team
     * @access public
     * @return array|void data for the template display
     * @todo some facelift
     * @see AdminGui::tools()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        $data = [];

        /*     $this->var()->check('id', $id, 'id'); */
        /*     // obtain maximum information about module */
        /*     $modinfo = $this->mod()->getInfo($id); */
        /*      */
        /*     // data vars for template */
        /*     $data['modid']              = $this->var()->prep($id); */
        /*     $data['modname']            = $this->var()->prep($modinfo['name']); */
        /*     $data['moddescr']           = $this->var()->prep($modinfo['description']); */
        /*     $data['moddispname']        = $this->var()->prep($modinfo['displayname']); */
        /*     $data['modlisturl']         = $this->ctl()->getModuleURL('modules', 'admin', 'list'); */
        /*     // check for proper icon, if not found display default */
        /*     // also displaying a generic icon now, if it was provided */
        /*     // additionally showing a short message if the icon is missing.. */
        /*     $modicon = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin.gif'; */
        /*     $modicongeneric = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin_generic.gif'; */
        /*     if(file_exists($modicon)){ */
        /*         $data['modiconurl']     = $this->var()->prep($modicon); */
        /*         $data['modiconmsg'] = $this->var()->prep($this->ml('as provided by the author')); */
        /*     }elseif(file_exists($modicongeneric)){ */
        /*         $data['modiconurl']     = $this->var()->prep($modicongeneric); */
        /*         $data['modiconmsg'] = $this->var()->prep($this->ml('Only generic icon has been provided')); */
        /*     }else{ */
        /*         $data['modiconurl']     = $this->var()->prep('modules/modules/xarimages/admin_generic.gif'); */
        /*         $data['modiconmsg'] = $this->var()->prep($this->ml('[Original icon is missing.. please ask this module developer to provide one in accordance with MDG]')); */
        /*     } */
        /*     $data['moddir']             = $this->var()->prep($modinfo['directory']); */
        /*     $data['modclass']           = $this->var()->prep($modinfo['class']); */
        /*     $data['modcat']             = $this->var()->prep($modinfo['category']); */
        /*     $data['modver']             = $this->var()->prep($modinfo['version']); */
        /*     $data['modauthor']          = preg_replace('/,/', '<br />', $this->var()->prep($modinfo['author'])); */
        /*     $data['modcontact']         = preg_replace('/,/', '<br />',$this->var()->prep($modinfo['contact'])); */
        /*     if(!empty($modinfo['dependency'])){ */
        /*         $dependency             = $this->ml('Working on it...'); */
        /*     } else { */
        /*         $dependency             = $this->ml('None'); */
        /*     } */
        /*     $data['moddependency']      = $this->var()->prep($dependency); */

        // done
        return $data;
    }
}
