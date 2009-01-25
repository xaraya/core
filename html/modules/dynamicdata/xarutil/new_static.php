<?php
/**
 * Create a new table field
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_util_new_static()
    {
        if (!xarSecurityCheck('AdminDynamicData')) return;

        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,     XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));
        $data['authid'] = xarSecGenAuthKey();

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if(!xarSecConfirmAuthKey()) return;
            
            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTplModule('dynamicdata','util','new_static', $data);        
            } else {
                // Good data: create the item
                $item = $data['object']->createItem();
                
                // Jump to the next page
                xarResponseRedirect(xarModURL('dynamicdata','util','view_static'));
                return true;
            }
        }
        return $data;
    }
?>