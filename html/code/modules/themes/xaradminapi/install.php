<?php
/**
 * Install a theme.
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Install a theme.
 *
 * @author Marty Vance
 * @param $maindId int ID of the module to look dependents for
 * @return boolean true on dependencies activated, false for not
 * @throws NO_PERMISSION
 */
function themes_adminapi_install($args)
{
    //    static $installed_ids = array();
    $regid = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminThemes', 1, 'All', 'All', 'themes')) return;

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');
    // See if we have lost any modules since last generation
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance('themes');  
    if (!$installer->checkformissing()) {return;}

    // Make xarMod::getInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    $GLOBALS['xarTheme_noCacheState'] = true;

    $installer->installmodule($regid);
    return true;
}
?>