<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 * @todo move the xml generate code to a template based system.
 */
/**
 * Export an object definition or an object item to XML
 */
sys::import('modules.dynamicdata.class.objects.master');

function dynamicdata_admin_export(Array $args=array())
{
    // Security
    if (!xarSecurity::check('AdminDynamicData')) return;

    extract($args);

    if(!xarVar::fetch('objectid', 'isset', $objectid, 1, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('name',     'isset', $name    , NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('module_id','isset', $moduleid, NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('itemid',   'isset', $itemid,   NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('tofile',   'isset', $tofile,   NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('convert',  'isset', $convert,  NULL, xarVar::DONT_SET)) {return;}

    $data = array();
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $myobject = DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name'     => $name,
                                         'itemid'   => $itemid,
                                         'allprops' => true));

    if (!isset($myobject) || empty($myobject->label)) {
        $data['label'] = xarML('Unknown Object');
        $data['xml'] = '';
        return $data;
    }
    // check security of the object
    if (!$myobject->checkAccess('config'))
        return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $myobject->label));


    $proptypes = DataPropertyMaster::getPropertyTypes();

    $prefix = xarDB::getPrefix();
    $prefix .= '_';

    $xml = '';

    // export object definition
    if (empty($itemid)) {
        $data['label'] = xarML('Export Object Definition for #(1)', $myobject->label);

        $xml = xarMod::apiFunc('dynamicdata','util','export',
                             array('objectref' => &$myobject));

        $data['formlink'] = xarController::URL('dynamicdata','admin','export',
                                      array('objectid' => $myobject->objectid,
                                            'itemid'   => 'all'));
        $data['filelink'] = xarController::URL('dynamicdata','admin','export',
                                      array('objectid' => $myobject->objectid,
                                            'itemid'   => 'all',
                                            'tofile'   => 1));

        if (!empty($myobject->datastores) && count($myobject->datastores) == 1 && !empty($myobject->datastores['_dynamic_data_'])) {
            $data['convertlink'] = xarController::URL('dynamicdata','admin','export',
                                             array('objectid' => $myobject->objectid,
                                                   'convert'  => 1));
            if (!empty($convert)) {
                if (!xarMod::apiFunc('dynamicdata','util','maketable',
                                   array('objectref' => &$myobject))) return;

            }
        }

    // export specific item
    } elseif (is_numeric($itemid)) {
        $data['label'] = xarML('Export Data for #(1) # #(2)', $myobject->label, $itemid);

        $xml = xarMod::apiFunc('dynamicdata','util','export_item',
                               array('objectid' => $myobject->objectid,
                                     'itemid'   => $itemid));

    // export all items (better save this to file, e.g. in var/cache/...)
    } elseif ($itemid == 'all') {
        $data['label'] = xarML('Export Data for all #(1) Items', $myobject->label);

        $xml = xarMod::apiFunc('dynamicdata','util','export_items',
                               array('objectid' => $myobject->objectid));

        if (!empty($tofile)) {
            $varDir = sys::varpath();
            $outfile = $varDir . '/uploads/' . xarVar::prepForOS($myobject->name) . '.data.' . xarLocale::formatDate('%Y%m%d%H%M%S',time()) . '.xml';
            $fp = @fopen($outfile,'w');
            if (!$fp) {
                $data['xml'] = xarML('Unable to open file #(1)',$outfile);
                return $data;
            }
            $written = fwrite($fp, $xml);
            fclose($fp);
            $towrite = strlen($xml);
            if ($written < $towrite) {
                throw new RuntimeException("could only write {$written}/{$towrite} bytes!");
            }
            $xml = xarML('Data saved to #(1)',$outfile);
        }
    } else {
        $data['label'] = xarML('Unknown Request for #(1)', $label);
        $xml = '';
    }

    $data['objectid'] = $objectid;
    $data['xml'] = xarVar::prepForDisplay($xml);

    xarTpl::setPageTemplateName('admin');

    return $data;
}

