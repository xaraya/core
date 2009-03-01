<?php
/**
 * Modify a table field
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_util_modify_static()
    {
        if (!xarSecurityCheck('EditDynamicData')) return;

        if (!xarVarFetch('table',      'str:1',  $data['table'], '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('field' ,     'str:1',  $data['field'] , '' ,          XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('oldname',    'str:1',  $data['oldname'], '',       XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));
        $data['authid'] = xarSecGenAuthKey();

        if ($data['confirm']) {
        
            // Check for a valid confirmation key
            if(!xarSecConfirmAuthKey()) return;

            // Get the data from the form
            $isvalid = $data['object']->checkInput();
            
            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                return xarTplModule('dynamicdata','util','modify_static', $data);        
            } else {
                if (empty($data['table'])) throw new Exception(xarML('Table name missing'));
                if (empty($data['oldname'])) throw new Exception(xarML('Previous field name missing'));
                
                // Good data: create the field
                $options = xarModAPIFunc('dynamicdata','data','getdatatypeoptions');
                $query = 'ALTER TABLE ' .$data['table'] . ' CHANGE COLUMN ' . $data['oldname'] . ' ';;
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
                xarResponseRedirect(xarModURL('dynamicdata','util','view_static',array('table' => $data['table'])));
                return true;
            }
        } else {
            $dbconn = xarDB::getConn();
            $dbInfo = $dbconn->getDatabaseInfo();
            $tableinfo = $dbInfo->getTable($data['table']);
            $fieldinfo = $tableinfo->getColumn($data['field']);
            $fieldargs = array(
                'name' => $fieldinfo->getName(),
                'type' => $fieldinfo->getType(),
                'nativetype' => $fieldinfo->getNativeType(),
                'size' => $fieldinfo->getSize(),
                'scale' => $fieldinfo->getScale(),
                'default' => $fieldinfo->getDefaultValue(),
                'null' => $fieldinfo->isNullable(),
                'autoincrement' => $fieldinfo->isAutoIncrement(),
                'vendor' => $fieldinfo->getVendorSpecificInfo(),
            );
            
            // This is a bit dodgy, but lets first see how many distinct datatypes we actually want to allow before we get too fancy here
            $fieldargs['attributes'] = 0;
            switch ($fieldargs['size']) {
                case 3:
                    $fieldargs['attributes'] = 1;
                case 4:
                    $fieldargs['type'] = 3;
                break;
                case 10:
                    $fieldargs['attributes'] = 1;
                case 11:
                    $fieldargs['type'] = 4;
                break;
                case 64:
                    $fieldargs['type'] = 1;
                break;
                case 254:
                    $fieldargs['type'] = 2;
                break;                
            }
            if ($fieldargs['nativetype'] == 'text') $fieldargs['type'] = 6;
            if (empty($fieldargs['nativetype'])) $fieldargs['type'] = 5;
    
        $data['object']->setFieldValues($fieldargs);
        $data['oldname'] = $fieldargs['name'];

        }
        return $data;
    }
?>