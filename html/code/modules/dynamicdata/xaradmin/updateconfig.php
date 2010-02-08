<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Update configuration parameters of the module
 *
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @return bool and redirect to modifyconfig
 */
function dynamicdata_admin_updateconfig($args)
{
    extract($args);

    if (!xarVarFetch('flushPropertyCache', 'isset', $flushPropertyCache,  NULL, XARVAR_DONT_SET)) {return;}

    if (!xarSecurityCheck('AdminDynamicData')) return;

    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    if ( isset($flushPropertyCache) && ($flushPropertyCache == true) ) {
        $args['flush'] = 'true';
        if(xarMod::apiFunc('dynamicdata','admin','importpropertytypes', $args)) {
            xarController::redirect(xarModURL('dynamicdata','admin','modifyconfig'));
            return true;
        } else {
            return 'Unknown error while clearing and reloading Property Definition Cache.';
        }
    }

    xarController::redirect(xarModURL('dynamicdata','admin','modifyconfig'));
    return true;
}
?>
