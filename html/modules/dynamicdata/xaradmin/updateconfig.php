<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
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

    if (!xarSecConfirmAuthKey()) return;

    if ( isset($flushPropertyCache) && ($flushPropertyCache == true) ) {
        $args['flush'] = 'true';
        if(xarModAPIFunc('dynamicdata','admin','importpropertytypes', $args)) {
            xarResponseRedirect(xarModURL('dynamicdata','admin','modifyconfig'));
            return true;
        } else {
            return 'Unknown error while clearing and reloading Property Definition Cache.';
        }
    }

    xarResponseRedirect(xarModURL('dynamicdata','admin','modifyconfig'));
    return true;
}
?>
