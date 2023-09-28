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

function dynamicdata_admin_export(array $args = [])
{
    // Security
    if (!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    extract($args);

    if(!xarVar::fetch('objectid', 'isset', $objectid, 1, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('name', 'isset', $name, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('module_id', 'isset', $moduleid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('itemid', 'isset', $itemid, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('tofile', 'isset', $tofile, null, xarVar::DONT_SET)) {
        return;
    }
    if(!xarVar::fetch('convert', 'isset', $convert, null, xarVar::DONT_SET)) {
        return;
    }

    $data = [];
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $myobject = DataObjectMaster::getObject(['objectid' => $objectid,
                                         'name'     => $name,
                                         'itemid'   => $itemid,
                                         'allprops' => true]);

    if (!isset($myobject) || empty($myobject->label)) {
        $data['label'] = xarML('Unknown Object');
        $data['xml'] = '';
        return $data;
    }
    // check security of the object
    if (!$myobject->checkAccess('config')) {
        return xarResponse::Forbidden(xarML('Configure #(1) is forbidden', $myobject->label));
    }

    $proptypes = DataPropertyMaster::getPropertyTypes();

    $prefix = xarDB::getPrefix();
    $prefix .= '_';

    $xml = '';
    $ext = '';

    // export object definition
    if (empty($itemid)) {
        $data['label'] = xarML('Export Object Definition for #(1)', $myobject->label);

        $xml = xarMod::apiFunc(
            'dynamicdata',
            'util',
            'export',
            ['objectref' => &$myobject]
        );
        $ext = '-def';

        /**
        if (!empty($myobject->datastores) && count($myobject->datastores) == 1 && !empty($myobject->datastores['_dynamic_data_'])) {
            $data['convertlink'] = xarController::URL('dynamicdata','admin','export',
                                             array('objectid' => $myobject->objectid,
                                                   'convert'  => 1));
            if (!empty($convert)) {
                if (!xarMod::apiFunc('dynamicdata','util','maketable',
                                   array('objectref' => &$myobject))) return;

            }
        }
         */

        // export specific item
    } elseif (is_numeric($itemid)) {
        $data['label'] = xarML('Export Data for #(1) # #(2)', $myobject->label, $itemid);

        $xml = xarMod::apiFunc(
            'dynamicdata',
            'util',
            'export_item',
            ['objectid' => $myobject->objectid,
                                     'itemid'   => $itemid]
        );
        $ext = '-dat.' . $itemid;

        // export all items (better save this to file, e.g. in var/cache/...)
    } elseif ($itemid == 'all') {
        $data['label'] = xarML('Export Data for all #(1) Items', $myobject->label);

        $xml = xarMod::apiFunc(
            'dynamicdata',
            'util',
            'export_items',
            ['objectid' => $myobject->objectid]
        );
        $ext = '-dat';

    } else {
        $data['label'] = xarML('Unknown Request for #(1)', $myobject->label);
        $xml = '';
    }

    $data['formlink'] = xarController::URL(
        'dynamicdata',
        'admin',
        'export',
        ['objectid' => $myobject->objectid,
                                        'itemid'   => 'all']
    );
    $data['filelink'] = xarController::URL(
        'dynamicdata',
        'admin',
        'export',
        ['objectid' => $myobject->objectid,
                                        'itemid'   => 'all',
                                        'tofile'   => 1]
    );
    $data['savelink'] = xarController::URL(
        'dynamicdata',
        'admin',
        'export',
        ['objectid' => $myobject->objectid,
                                        'tofile'   => 1]
    );

    if (!empty($tofile) && !empty($ext)) {
        $varDir = sys::varpath();
        $outfile = $varDir . '/uploads/' . xarVar::prepForOS($myobject->name) . $ext . '.' . xarLocale::formatDate('%Y%m%d%H%M%S', time()) . '.xml';
        $fp = @fopen($outfile, 'w');
        if (!$fp) {
            $data['xml'] = xarML('Unable to open file #(1)', $outfile);
            return $data;
        }
        $written = fwrite($fp, $xml);
        fclose($fp);
        $towrite = strlen($xml);
        if ($written < $towrite) {
            throw new RuntimeException("could only write {$written}/{$towrite} bytes!");
        }
        $xml = xarML('Data saved to #(1)', $outfile);
    }

    $data['objectid'] = $objectid;
    $data['xml'] = xarVar::prepForDisplay($xml);

    xarTpl::setPageTemplateName('admin');

    return $data;
}
