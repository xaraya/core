<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Loads xarinit or pninit and executes the given function
 *
 * @author Xaraya Development Team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the id of the module<br/>
 *        string   $args['function'] name of the function to be called
 * @return boolean true on success, false on failure in the called function
 * @throws BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_executeinitfunction(Array $args=array())
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Argument check
    if (!isset($args['regid'])) throw new EmptyParameterException('regid');

    // Get module information
    $modInfo = xarMod::getInfo($args['regid']);

    if (!isset($modInfo['osdirectory']) ||
        empty($modInfo['osdirectory']) ||
        !is_dir(sys::code() . 'modules/'. $modInfo['osdirectory'])) {

        $msg = 'Module (regid: #(1) - directory: #(2) does not exist.';
        $vars = array($args['regid'], $modInfo['osdirectory']);
        throw new ModuleNotFoundException($vars,$msg);
    }

    // Get module database info, they might be needed in the function to be called
    xarMod__loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

    $xarinitfile = '';
    if (file_exists(sys::code() . 'modules/'. $modInfo['osdirectory'] .'/xarinit.php')) {
        $xarinitfile = sys::code() . 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    }
    // If there is no xarinit file, there is apparently nothing to init.
    // TODO: we migh consider making it required.
    if (empty($xarinitfile)) return true;


    // if (!empty($xarinitfile)) {
    ob_start();
    $r = sys::import('modules.'.$modInfo['osdirectory'].'.xarinit');
    $error_msg = strip_tags(ob_get_contents());
    ob_end_clean();

    if (empty($r) || !$r) {
        $msg = xarML("Could not load file: [#(1)].\n\n Error Caught:\n #(2)", $xarinitfile, $error_msg);
        throw new Exception($msg);
    }

    $func = $modInfo['name'] . '_'.$args['function'];
    if (function_exists($func)) {
        if ($args['function'] == 'upgrade') {
            // pass the old version as argument to the upgrade function
            $result = $func($modInfo['version']);
        } else {
            $result = $func();
        }

        if ($result === false) {
            $msg = xarML('While changing state of the #(1) module, the function #(2) returned a false value when executed.', $modInfo['name'], $func);
            throw new Exception($msg);
        } elseif ($result != true) {
            $msg = xarML('An error ocurred while changing state of the #(1) module, executing function #(2)', $modInfo['name'], $func);
            throw new Exception($msg);
        }
    }
    return true;
}

?>
