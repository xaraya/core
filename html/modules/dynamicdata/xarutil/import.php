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
 * Import an object definition or an object item from XML
 */
function dynamicdata_util_import($args)
{
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('basedir',    'isset', $basedir,     NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('import',     'isset', $import,      NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('xml',        'isset', $xml,         NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('refresh',    'isset', $refresh,     NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('keepitemid', 'isset', $keepitemid,  NULL,  XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('overwrite',  'bool',  $overwrite,   false, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prefix', 'isset', $data['prefix'],  xarDB::getPrefix(), XARVAR_DONT_SET)) {return;}

    extract($args);

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
                throw new FileNotFoundException($basedir,'No files were found to import in directory "#(1)"');
            }
            $objectid = xarModAPIFunc('dynamicdata','util','import',
                                      array('file' => $basedir . '/' . $file,
                                            'keepitemid' => $keepitemid,
                                            'overwrite' =>  $overwrite,
                                            'prefix' => $data['prefix'],
                                            ));
        } else {
            $objectid = xarModAPIFunc('dynamicdata','util','import',
                                      array('xml' => $xml,
                                            'keepitemid' => $keepitemid,
                                            'overwrite' =>  $overwrite,
                                            'prefix' => $data['prefix'],
                                            ));
        }
        if (empty($objectid)) return;

//        $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
//                                    array('objectid' => $objectid));
//        if (empty($objectinfo)) return;

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

    if (xarModVars::get('themes','usedashboard')) {
        $admin_tpl = xarModVars::get('themes','dashtemplate');
    }else {
       $admin_tpl='default';
    }
    xarTplSetPageTemplateName($admin_tpl);

    return $data;
}

?>
