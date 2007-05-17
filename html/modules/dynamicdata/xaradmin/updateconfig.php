<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Update configuration parameters of the module
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @return bool and redirect to modifyconfig
 */
function dynamicdata_admin_updateconfig($args)
{
    extract($args);

    if (!xarVarFetch('flushPropertyCache', 'isset', $flushPropertyCache,  NULL, XARVAR_DONT_SET)) {return;}

    // Security Check
    if (!xarSecurityCheck('AdminDynamicData')) return;

    if (!xarSecConfirmAuthKey()) return;

    if ( isset($flushPropertyCache) && ($flushPropertyCache == true) )
    {
        $args['flush'] = 'true';
        $success = xarModAPIFunc('dynamicdata','admin','importpropertytypes', $args);

        if( $success )
        {
            xarResponseRedirect(xarModURL('dynamicdata','admin','modifyconfig'));
            return true;
        } else {
            return 'Unknown error while clearing and reloading Property Definition Cache.';
        }
    }

    if (!xarVarFetch('label','list:str:',$label,NULL,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('validation','list:str:',$validation,NULL,XARVAR_NOT_REQUIRED)) return;

    if (empty($label) && empty($validation)) {
        xarResponseRedirect(xarModURL('dynamicdata','admin','modifyconfig'));
        return true;
    }

    $proptypes = DataPropertyMaster::getPropertyTypes();

    $dbconn = xarDB::getConn();
    $xartable = xarDBGetTables();

    $dynamicproptypes = $xartable['dynamic_properties_def'];

    foreach ($proptypes as $proptype) {
        $id = (int) $proptype['id'];
        if (empty($label[$id])) {
            $query = "DELETE FROM $dynamicproptypes
                            WHERE prop_id = ?";
            $bindvars = array($id);
            $dbconn->Execute($query,$bindvars);
        } elseif ($label[$id] != $proptype['label'] || $validation[$id] != $proptype['validation']) {
            $query = "UPDATE $dynamicproptypes
                         SET prop_label = ?,
                             prop_validation = ?
                       WHERE prop_id = ?";
            $bindvars = array($label[$id],$validation[$id],$id);
            $dbconn->Execute($query,$bindvars);
        }
    }

    xarResponseRedirect(xarModURL('dynamicdata','admin','modifyconfig'));
    return true;
}

?>
