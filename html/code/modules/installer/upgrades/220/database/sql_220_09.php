<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_09()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding some configuration variables
    ");
    $data['reply'] = xarML("
        Success!
    ");

    try {
        $tmp = xarConfigVars::get(null, 'Site.BL.MemCacheTemplates');
    } catch (Exception $e) {
        xarConfigVars::set(null, 'Site.BL.MemCacheTemplates', false);
        xarConfigVars::set(null, 'Site.BL.CompressWhitespace', 1);
        xarConfigVars::set(null, 'Site.BL.Debug_User', xarModVars('roles','admin'));
    }
    if (!isset(xarSystemVars::get(sys::CONFIG, 'Log.Filename'))) {
        $variables = array('Log.Enabled' => 0, 'Log.Filename' => 'xarayalog.txt');
        xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
    }
    return $data;
}
?>