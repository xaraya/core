<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Update configuration parameters of the module
 *
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @return boolean and redirect to view_propertydefs
 */
function dynamicdata_admin_update_propertydefs(Array $args=array())
{
    extract($args);

    if (!xarVarFetch('flushPropertyCache', 'isset', $flushPropertyCache,  NULL, XARVAR_DONT_SET)) {return;}

    // Security
    if (!xarSecurityCheck('AdminDynamicData')) return;

    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if ( isset($flushPropertyCache) && ($flushPropertyCache == true) ) {
        $args['flush'] = 'true';
        if(xarMod::apiFunc('dynamicdata','admin','importpropertytypes', $args)) {
            xarController::redirect(xarModURL('dynamicdata','admin','view_propertydefs'));
            return true;
        } else {
            return 'Unknown error while clearing and reloading Property Definition Cache.';
        }
    }

    xarController::redirect(xarModURL('dynamicdata','admin','view_propertydefs'));
    return true;
}
?>
