<?php
/**
 * Install a theme.
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
 * Install a theme.
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['maindId'] ID of the module to look dependents for
 * @return boolean|void true on dependencies activated, false for not
 */
function themes_adminapi_install(Array $args=array())
{
    //    static $installed_ids = array();
    $regid = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurity::check('AdminThemes', 1, 'All', 'All', 'themes')) return;

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');
    // See if we have lost any modules since last generation
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance('themes');  
    if (!$installer->checkformissing()) {return;}

    // Make xarMod::getInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    xarTheme::$noCacheState = true;

    $installer->installmodule($regid);
    return true;
}
