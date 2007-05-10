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
 * @todo move the xml generate code to a template based system.
 */
/**
 * Export an object definition or an object item to XML
 */
function dynamicdata_util_export($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name    , NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $moduleid, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tofile',   'isset', $tofile,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('convert',  'isset', $convert,  NULL, XARVAR_DONT_SET)) {return;}

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $myobject = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name'     => $name,
                                         'moduleid' => $moduleid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid,
                                         'allprops' => true));

    if (!isset($myobject) || empty($myobject->label)) {
        $data['label'] = xarML('Unknown Object');
        $data['xml'] = '';
        return $data;
    }

    $proptypes = DataPropertyMaster::getPropertyTypes();

    $prefix = xarDBGetSystemTablePrefix();
    $prefix .= '_';

    $xml = '';

    // export object definition
    if (empty($itemid)) {
        $data['label'] = xarML('Export Object Definition for #(1)', $myobject->label);

        $xml = xarModAPIFunc('dynamicdata','util','export',
                             array('objectref' => &$myobject));

        $data['formlink'] = xarModURL('dynamicdata','util','export',
                                      array('objectid' => $myobject->objectid,
                                            'itemid'   => 'all'));
        $data['filelink'] = xarModURL('dynamicdata','util','export',
                                      array('objectid' => $myobject->objectid,
                                            'itemid'   => 'all',
                                            'tofile'   => 1));

        if (!empty($myobject->datastores) && count($myobject->datastores) == 1 && !empty($myobject->datastores['_dynamic_data_'])) {
            $data['convertlink'] = xarModURL('dynamicdata','util','export',
                                             array('objectid' => $myobject->objectid,
                                                   'convert'  => 1));
            if (!empty($convert)) {
                if (!xarModAPIFunc('dynamicdata','util','maketable',
                                   array('objectref' => &$myobject))) return;

            }
        }

    // export specific item
    } elseif (is_numeric($itemid)) {
        $data['label'] = xarML('Export Data for #(1) # #(2)', $myobject->label, $itemid);

        $myobject->getItem();

        $xml .= '<'.$myobject->name.' itemid="'.$itemid.'">'."\n";
        foreach (array_keys($myobject->properties) as $name) {
            $xml .= "  <$name>" . xarVarPrepForDisplay($myobject->properties[$name]->value) . "</$name>\n";
        }
        $xml .= '</'.$myobject->name.">\n";

    // export all items (better save this to file, e.g. in var/cache/...)
    } elseif ($itemid == 'all') {
        $data['label'] = xarML('Export Data for all #(1) Items', $myobject->label);

        $mylist = DataObjectMaster::getObjectList(array('objectid' => $objectid,
                                                'moduleid' => $moduleid,
                                                'itemtype' => $itemtype));
        $mylist->getItems();

        if (empty($tofile)) {
            $xml .= "<items>\n";
            foreach ($mylist->items as $itemid => $item) {
                $xml .= '  <'.$mylist->name.' itemid="'.$itemid.'">'."\n";
                foreach (array_keys($mylist->properties) as $name) {
                    if (isset($item[$name])) {
                        $xml .= "    <$name>" . xarVarPrepForDisplay($item[$name]);
                    } else {
                        $xml .= "    <$name>";
                    }
                    $xml .= "</$name>\n";
                }
                $xml .= '  </'.$mylist->name.">\n";
            }
            $xml .= "</items>\n";

        } else {
            $varDir = sys::varpath();
            $outfile = $varDir . '/uploads/' . xarVarPrepForOS($mylist->name) . '.data.' . xarLocaleFormatDate('%Y%m%d%H%M%S',time()) . '.xml';
            $fp = @fopen($outfile,'w');
            if (!$fp) {
                $data['xml'] = xarML('Unable to open file #(1)',$outfile);
                return $data;
            }
            fputs($fp, "<items>\n");
            foreach ($mylist->items as $itemid => $item) {
                fputs($fp, "  <".$mylist->name." itemid=\"$itemid\">\n");
                foreach (array_keys($mylist->properties) as $name) {
                    if (isset($item[$name])) {
                        fputs($fp, "    <$name>" . xarVarPrepForDisplay($item[$name]) . "</$name>\n");
                    } else {
                        fputs($fp, "    <$name></$name>\n");
                    }
                }
                fputs($fp, "  </".$mylist->name.">\n");
            }
            fputs($fp, "</items>\n");
            fclose($fp);
            $xml .= xarML('Data saved to #(1)',$outfile);
        }

    } else {
        $data['label'] = xarML('Unknown Request for #(1)', $label);
        $xml = '';
    }

    $data['xml'] = xarVarPrepForDisplay($xml);

    if (xarModVars::get('themes','usedashboard')) {
        $admin_tpl = xarModVars::get('themes','dashtemplate');
    }else {
       $admin_tpl='default';
    }
    xarTplSetPageTemplateName($admin_tpl);

    return $data;
}

?>
