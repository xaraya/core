<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin cssconfig function
 * @extends MethodClass<AdminGui>
 */
class CssconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Module admin function to review and configure Xaraya CSS
     * @author AndyV_at_Xaraya_dot_Com
     * @return array|void data for the template display
     * @see AdminGui::cssconfig()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminThemes', 0)) {
            return;
        }

        // Generate security key
        $data['authid'] = $this->sec()->genAuthKey();

        // where are we?
        $this->var()->find('component', $component, 'str::', '');

        $data['component'] = $component;
        // is configurable enabled?
        $this->var()->find('configurable', $configurable, 'checkbox', false);
        $data['configurable'] = $configurable;

        // labels and defaults
        $data['submitbutton'] = \xarVarPrep::forDisplay($this->ml('Submit'));
        $data['resetbutton'] = \xarVarPrep::forDisplay($this->ml('Reset to defaults'));
        $data['unmanagednote'] = \xarVarPrep::forDisplay($this->ml('No configurable options are available in unmanaged mode.'));

        switch ($component) {
            case "common":
                // get and verify modvars and files - all reporting inline in the form
                $data['csslinkoption'] = $this->mod()->getVar('csslinkoption');
                $cssfilepath = sys::code() . 'modules/themes/xarstyles/';
                $filemissing = $this->ml('none (missing)');
                $notlinked = $this->ml('none - use for template debugging only!!');
                if ($data['csslinkoption'] == '') {
                    $this->mod()->setVar('csslinkoption', 'static');
                    if (file_exists($cssfilepath . 'core.css')) {
                        $data['currentcssfile'] = \xarVarPrep::forDisplay($cssfilepath . 'core.css');
                    } else {
                        $data['currentcssfile'] = \xarVarPrep::forDisplay($filemissing);
                    }
                } elseif ($data['csslinkoption'] == 'static') {
                    if (file_exists($cssfilepath . '/core.css')) {
                        $data['currentcssfile'] = \xarVarPrep::forDisplay($cssfilepath . 'core.css');
                        $handle = fopen($cssfilepath . '/core.css', 'r');
                        $data['csssource'] = fread($handle, filesize($cssfilepath . '/core.css'));
                        fclose($handle);
                    } else {
                        $data['currentcssfile'] = \xarVarPrep::forDisplay($filemissing);
                    }
                } elseif ($data['csslinkoption'] == 'dynamic') {
                    if (file_exists($cssfilepath . 'corecss.php')) {
                        $data['currentcssfile'] = \xarVarPrep::forDisplay($cssfilepath . 'corecss.php');
                        $data['csssource'] = $this->mod()->getVar('corecss');
                    } else {
                        $data['currentcssfile'] = \xarVarPrep::forDisplay($filemissing);
                    }
                } else {
                    $data['currentcssfile'] = \xarVarPrep::forDisplay($notlinked);
                }


                break;
            case "modules":
                break;
            case "themes":
                break;
            default:
                break;
        }

        return $data;
    }
}
