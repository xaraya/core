<?php
/**
 * Import an object definition or an object item from XML
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import an object definition or an object item from XML
 */
function dynamicdata_util_import($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('basedir',    'isset', $basedir,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('import',     'isset', $import,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('xml',        'isset', $xml,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('refresh',    'isset', $refresh,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('keepitemid', 'isset', $keepitemid,  NULL, XARVAR_DONT_SET)) {return;}

    extract($args);

    $data = array();

    $data['warning'] = '';
    $data['options'] = array();

    if (empty($basedir)) {
        $basedir = 'modules/dynamicdata';
    }
    $data['basedir'] = $basedir;
    $data['authid'] = xarSecGenAuthKey();

    $filetype = 'xml';
    $files = xarModAPIFunc('dynamicdata','admin','browse',
                           array('basedir' => $basedir,
                                 'filetype' => $filetype));
    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no XML files available for import in "#(1)"',$basedir);
        return $data;
    }

    if (empty($refresh) && (!empty($import) || !empty($xml))) {
        if (!xarSecConfirmAuthKey()) return;

        if (empty($keepitemid)) {
            $keepitemid = 0;
        }
        if (!empty($import)) {
            $found = '';
            foreach ($files as $file) {
                if ($file == $import) {
                    $found = $file;
                    break;
                }
            }
            if (empty($found) || !file_exists($basedir . '/' . $file)) {
                $msg = xarML('File not found');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
            $objectid = xarModAPIFunc('dynamicdata','util','import',
                                      array('file' => $basedir . '/' . $file,
                                            'keepitemid' => $keepitemid));
        } else {
            $objectid = xarModAPIFunc('dynamicdata','util','import',
                                      array('xml' => $xml,
                                            'keepitemid' => $keepitemid));
        }
        if (empty($objectid)) return;

        $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                    array('objectid' => $objectid));
        if (empty($objectinfo)) return;

        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                      array('itemid' => $objectid)));
        return true;
    }

    natsort($files);
    array_unshift($files,'');
    foreach ($files as $file) {
         $data['options'][] = array('id' => $file,
                                    'name' => $file);
    }

    if (xarModGetVar('adminpanels','dashboard')) {
        xarTplSetPageTemplateName('admin');
    }else {
        xarTplSetPageTemplateName('default');
    }

    return $data;
}

?>