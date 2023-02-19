<?php
/**
 * Review and configure Xaraya CSS
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
* Module admin function to review and configure Xaraya CSS
*
* @author AndyV_at_Xaraya_dot_Com
 * @return array|void data for the template display
*/
function themes_admin_cssconfig()
{
    // Security
    if (!xarSecurity::check('AdminThemes',0)) return;
    
    // Generate security key
    $data['authid'] = xarSec::genAuthKey();

    // where are we?
    if (!xarVar::fetch('component', 'str::', $component, '', xarVar::NOT_REQUIRED)) return;

    $data['component'] = $component;
    // is configurable enabled?
    if (!xarVar::fetch('configurable', 'checkbox', $configurable, false, xarVar::NOT_REQUIRED)) return;
    $data['configurable'] = $configurable;

    // labels and defaults
    $data['submitbutton'] = xarVar::prepForDisplay(xarML('Submit'));
    $data['resetbutton'] = xarVar::prepForDisplay(xarML('Reset to defaults'));
    $data['unmanagednote'] = xarVar::prepForDisplay(xarML('No configurable options are available in unmanaged mode.'));

    switch($component) {
        case "common":
            // get and verify modvars and files - all reporting inline in the form
            $data['csslinkoption'] = xarModVars::get('themes', 'csslinkoption');
            $cssfilepath = sys::code() . 'modules/themes/xarstyles/';
            $filemissing = xarML('none (missing)');
            $notlinked = xarML('none - use for template debugging only!!');
            if($data['csslinkoption'] == '') {
                xarModVars::set('themes', 'csslinkoption', 'static');
                if(file_exists($cssfilepath.'core.css')) {
                    $data['currentcssfile'] = xarVar::prepForDisplay($cssfilepath.'core.css');
                } else {
                    $data['currentcssfile'] = xarVar::prepForDisplay($filemissing);
                }
            } else if($data['csslinkoption'] == 'static') {
                if(file_exists($cssfilepath.'/core.css')) {
                    $data['currentcssfile'] = xarVar::prepForDisplay($cssfilepath.'core.css');
                    $handle = fopen($cssfilepath.'/core.css', 'r');
                    $data['csssource'] = fread($handle, filesize($cssfilepath.'/core.css'));
                    fclose($handle);
                } else {
                    $data['currentcssfile'] = xarVar::prepForDisplay($filemissing);
                }
            } else if($data['csslinkoption'] == 'dynamic') {
                if(file_exists($cssfilepath.'corecss.php')) {
                    $data['currentcssfile'] = xarVar::prepForDisplay($cssfilepath.'corecss.php');
                    $data['csssource'] = xarModVars::get('themes', 'corecss');
                } else {
                    $data['currentcssfile'] = xarVar::prepForDisplay($filemissing);
                }
            } else {
                $data['currentcssfile'] = xarVar::prepForDisplay($notlinked);
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
