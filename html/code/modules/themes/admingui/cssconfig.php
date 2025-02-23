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
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
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
        $data['submitbutton'] = $this->var()->prep($this->ml('Submit'));
        $data['resetbutton'] = $this->var()->prep($this->ml('Reset to defaults'));
        $data['unmanagednote'] = $this->var()->prep($this->ml('No configurable options are available in unmanaged mode.'));

        switch ($component) {
            case "common":
                // get and verify modvars and files - all reporting inline in the form
                $data['csslinkoption'] = xarModVars::get('themes', 'csslinkoption');
                $cssfilepath = sys::code() . 'modules/themes/xarstyles/';
                $filemissing = $this->ml('none (missing)');
                $notlinked = $this->ml('none - use for template debugging only!!');
                if ($data['csslinkoption'] == '') {
                    xarModVars::set('themes', 'csslinkoption', 'static');
                    if (file_exists($cssfilepath . 'core.css')) {
                        $data['currentcssfile'] = $this->var()->prep($cssfilepath . 'core.css');
                    } else {
                        $data['currentcssfile'] = $this->var()->prep($filemissing);
                    }
                } elseif ($data['csslinkoption'] == 'static') {
                    if (file_exists($cssfilepath . '/core.css')) {
                        $data['currentcssfile'] = $this->var()->prep($cssfilepath . 'core.css');
                        $handle = fopen($cssfilepath . '/core.css', 'r');
                        $data['csssource'] = fread($handle, filesize($cssfilepath . '/core.css'));
                        fclose($handle);
                    } else {
                        $data['currentcssfile'] = $this->var()->prep($filemissing);
                    }
                } elseif ($data['csslinkoption'] == 'dynamic') {
                    if (file_exists($cssfilepath . 'corecss.php')) {
                        $data['currentcssfile'] = $this->var()->prep($cssfilepath . 'corecss.php');
                        $data['csssource'] = xarModVars::get('themes', 'corecss');
                    } else {
                        $data['currentcssfile'] = $this->var()->prep($filemissing);
                    }
                } else {
                    $data['currentcssfile'] = $this->var()->prep($notlinked);
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
