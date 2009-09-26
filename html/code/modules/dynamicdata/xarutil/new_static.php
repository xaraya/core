<?php
/**
 * Create a new table field
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_util_new_static()
    {
        if (!xarSecurityCheck('AdminDynamicData')) return;

        if (!xarVarFetch('table',    'str:1',  $data['table'], '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',  'bool',   $data['confirm'], false,     XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));
        $data['authid'] = xarSecGenAuthKey();

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            
            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTplModule('dynamicdata','util','new_static', $data);        
            } else {
                if (empty($data['table'])) throw new Exception(xarML('Table name missing'));
                
                // Good data: create the field
                $options = xarMod::apiFunc('dynamicdata','data','getdatatypeoptions');
                $query = 'ALTER TABLE ' .$data['table'] . ' ADD ';
                $query .= $data['object']->properties['name']->value . ' ';
                $query .= $options['datatypes'][$data['object']->properties['type']->value] . ' ';
                if ((in_array($data['object']->properties['type']->value,array(3,4,5)))) {
                    $query .= $options['attributes'][$data['object']->properties['attributes']->value] . " ";
                }
                $query .= $options['nulls'][$data['object']->properties['null']->value] . " ";
//                $query .= 'COLLATE ' . $options['collations'][$data['object']->properties['collation']->value] . " ";

                if ($data['object']->properties['type']->value != 6) {
                    if (in_array($data['object']->properties['type']->value,array(3,4,5))) {
                        if (is_numeric($data['object']->properties['default']->value)) 
                            $query .= 'default ' . $data['object']->properties['default']->value;
                    } else {
                        $query .= 'default "' . $data['object']->properties['default']->value . '"';
                    }
                }
                $dbconn = xarDB::getConn();
                $dbconn->Execute($query);
                
                // Jump to the next page
                xarResponse::Redirect(xarModURL('dynamicdata','util','view_static',array('table' => $data['table'])));
                return true;
            }
        }
        return $data;
    }
?>