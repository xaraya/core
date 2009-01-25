<?php
/**
 * Modify a table field
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_util_modify_static()
    {
        if (!xarSecurityCheck('EditDynamicData')) return;

        if (!xarVarFetch('itemid' ,    'int',    $data['itemid'] , 0 ,          XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => $name));
        $data['authid'] = xarSecGenAuthKey();

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if(!xarSecConfirmAuthKey()) return;
            echo "X";
            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTplModule('dynamicdata','util','modify_static', $data);        
            } else {
                // Good data: create the item
                $item = $data['object']->updateItem();
                
                // Jump to the next page
                xarResponseRedirect(xarModURL('dynamicdata','util','view_static'));
                return true;
            }
        } else {
            $data['object']->getItem(array('itemid' => $data['itemid']));
        }
        return $data;
    }
?>