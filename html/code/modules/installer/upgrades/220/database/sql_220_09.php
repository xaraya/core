<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
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
    }
    
    try {
        $tmp = xarConfigVars::get(null, 'Site.BL.CompressWhitespace');
    } catch (Exception $e) {
        xarConfigVars::set(null, 'Site.BL.CompressWhitespace', 1);
    }
    
    // Add default values for logging if logging is not turned on
    try {
        $logfile = xarSystemVars::get(sys::CONFIG, 'Log.Filename');
    } catch (Exception $e) {
        $variables = array('Log.Enabled' => 0, 'Log.Filename' => 'xarayalog.txt');
        xarMod::apiFunc('installer','admin','modifysystemvars', array('variables'=> $variables));
    }
    return $data;
}
?>