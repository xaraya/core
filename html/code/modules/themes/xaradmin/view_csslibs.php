<?php
/**
 * View the configuration options
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */

sys::import('modules.dynamicdata.class.objects.master');

function themes_admin_view_csslibs()
{
    // Security
     if(!xarSecurityCheck('EditThemes')) return;

    if (!xarVarFetch('tab',   'str:1:100', $data['tab'], 'remote', XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('name' => 'themes_csslibraries'));

    if (!isset($data['object'])) {return;}
    if (!$data['object']->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $data['object']->label));
    $data['properties'] = $data['object']->getProperties();
       
    sys::import('modules.themes.class.xarcss');
    $libobject = xarCSS::getInstance(); 
    // CHECKME: is this the right place to do it?
    $libobject->refresh();

    if ($data['tab'] == 'auto') {
        if(!xarVarFetch('confirm',      'bool', $confirm,          false, XARVAR_DONT_SET)) {return;}
        if ($confirm) {
            if(!xarVarFetch('dd_seq',      'array', $dd_seq,          array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_position', 'array', $dd_position,     array(), XARVAR_DONT_SET)) {return;}
            foreach (array_keys($dd_seq) as $id) {
                $libobject->default_libs[$id]['seq'] = $dd_seq[$id];
                $libobject->default_libs[$id]['position'] = $dd_position[$id];
            }
            // Sort the array by sequence
            $temp = array();
            foreach($libobject->default_libs as &$ma) {
                $temp[] = &$ma["seq"];
            }
            array_multisort($temp, $libobject->default_libs);
            // Now resequence the array to start with seq = 1
            $index = 0;
            foreach($libobject->default_libs as $key => $value) {
                $index++;
                $libobject->default_libs[$key]['seq'] = $index;
                $libobject->default_libs[$key]['load'] = 1;
            }
            // let xarCSS::__destruct know we need to save this
            $libobject->refreshed = true;
        }
        $data['fieldvalues'] = $libobject->default_libs;
    } elseif ($data['tab'] == 'local') {
        // Arrange all the lib files in a nice list, with a unique ID index
        $data['fieldvalues'] = array();
        $seqindex = count($data['fieldvalues']);
        if (is_array($libobject->local_libs)) {
            foreach($libobject->local_libs as $lib) {
                foreach($lib->styles as $version => $versionarray) {
                    foreach($versionarray as $scope => $scopearray) {
                        foreach($scopearray as $package => $packagearray) {
                            foreach($packagearray as $path => $patharray) {
                                foreach($patharray as $file => $filearray) {//echo "<pre>";var_dump($filearray['lib']);
                                    $id = $filearray['lib'] . "." . $version . "." . $scope . "." . $package . "." . $path . "." . $file;
                                    $id = str_replace('/', '_', $id);
                                    $id = str_replace(' ', '_', $id);
                                    $id = str_replace('.', '_', $id);
                                    $id = str_replace('-', '_', $id);
                                    $seqindex++;
                                    $data['fieldvalues'][$id] = array(
                                        'id' => $id,
                                        'seq' => $seqindex,
                                        'type' => 'lib',
                                        'lib' => $filearray['lib'],
                                        'version' => $version,
                                        'scope' => $scope,
                                        'package' => $package,
                                        'base' => $path,
                                        'src' => $file,
                                        'load' => 0,
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        // Sort the libraries by name and by descending version
        if (!empty($data['fieldvalues'])) {
            foreach ($data['fieldvalues'] as $key => $row) {
                $templib[$key]  = $row['lib'];
                $tempversion[$key] = $row['version'];
            }
            array_multisort($templib, SORT_ASC, $tempversion, SORT_DESC, $data['fieldvalues']);
        }

        if(!xarVarFetch('confirm',      'bool', $confirm,          false, XARVAR_DONT_SET)) {return;}
        if ($confirm) {
            if(!xarVarFetch('dd_load',     'array', $dd_load,         array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_seq',      'array', $dd_seq,          array(), XARVAR_DONT_SET)) {return;}
            
            // We remove the local auto loading libraries and then repopulate them
            foreach($libobject->default_libs as $key => $value) {
                if ($value['origin'] == 'local') {
                    unset($libobject->default_libs[$key]);
                }
            }
            foreach (array_keys($dd_load) as $id) {
                // The file may have disappeared in the meantime
                if (!isset($data['fieldvalues'][$id])) continue;
                $libobject->default_libs[$id] = $data['fieldvalues'][$id];
                $libobject->default_libs[$id]['seq'] = $dd_seq[$id];
                $libobject->default_libs[$id]['position'] = 'head';
                $libobject->default_libs[$id]['origin'] = 'local';
            }
            // Sort the array by sequence
            $temp = array();
            foreach($libobject->default_libs as &$ma) {
                $temp[] = &$ma["seq"];
            }
            array_multisort($temp, $libobject->default_libs);
            // Now resequence the array to start with seq = 1
            $index = 0;
            foreach($libobject->default_libs as $key => $value) {
                if ($value['origin'] == 'remote') continue;
                $index++;
                $libobject->default_libs[$key]['seq'] = $index;
                $libobject->default_libs[$key]['load'] = 1;
            }
            // let xarCSS::__destruct know we need to save this
            $libobject->refreshed = true;
        }
    
        // Strip out any default libs whose local files are no longer present
        // For those still present remove their equivalent in the defaultvalue array
        // We will now have each entry present in only one of the two arays
        foreach ($libobject->default_libs as $row) {
            if (!isset($data['fieldvalues'][$row['id']])) {
                unset($libobject->default_libs[$row['id']]);
            } else {
                if ($row['origin'] == 'remote') continue;
                unset($data['fieldvalues'][$row['id']]);
            }
        }
    
        // Now merge the two arrays, with the default libs at the top
        $temp = $data['fieldvalues'];
        $data['fieldvalues'] = $libobject->default_libs;
        $seqindex = count($data['fieldvalues']);
        foreach ($temp as $row) {
            $seqindex++;
            $row['seq'] = $seqindex;
            $row['load'] = 0;
            $data['fieldvalues'][$row['id']] = $row;
        }
    } elseif ($data['tab'] == 'remote') {
        if(!xarVarFetch('confirm',      'bool', $confirm,          false, XARVAR_DONT_SET)) {return;}
        if ($confirm) {
            if(!xarVarFetch('dd_id',       'array', $dd_id,       array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_type',     'array', $dd_type,     array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_lib',      'array', $dd_lib,      array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_version',  'array', $dd_version,  array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_scope',    'array', $dd_scope,    array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_package',  'array', $dd_package,  array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_base',     'array', $dd_base,     array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_src',      'array', $dd_src,      array(), XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch('dd_load',     'array', $dd_load,     array(), XARVAR_DONT_SET)) {return;}

            $libobject->remote_libs = array();
            foreach ($dd_id as $id) {
                if (empty($dd_lib[$id])) continue;
                $libobject->remote_libs[$id]['id'] = $id;
                $libobject->remote_libs[$id]['type'] = $dd_type[$id];
                $libobject->remote_libs[$id]['lib'] = $dd_lib[$id];
                $libobject->remote_libs[$id]['version'] = $dd_version[$id];
                $libobject->remote_libs[$id]['scope'] = $dd_scope[$id];
                $libobject->remote_libs[$id]['package'] = $dd_package[$id];
                $libobject->remote_libs[$id]['base'] = $dd_base[$id];
                $libobject->remote_libs[$id]['src'] = $dd_src[$id];
                if (!isset($dd_load[$id])) $dd_load[$id] = 0;
                $libobject->remote_libs[$id]['load'] = $dd_load[$id];
                $libobject->remote_libs[$id]['origin'] = 'remote';
            }
            
            // Add a new remote stylesheet
            if(!xarVarFetch('new_lib',      'str', $new_lib,      '', XARVAR_DONT_SET)) {return;}
            if (!empty($new_lib)) {
                if(!xarVarFetch('new_id',       'str', $new_id,       '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_type',     'str', $new_type,     '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_version',  'str', $new_version,  '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_scope',    'str', $new_scope,    '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_package',  'str', $new_package,  '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_base',     'str', $new_base,     '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_src',      'str', $new_src,      '', XARVAR_DONT_SET)) {return;}
                if(!xarVarFetch('new_load',     'str', $new_load,     '', XARVAR_DONT_SET)) {return;}
                $id = $new_type . "." . $new_lib . "." . $new_version . "." . $new_scope . "." . $new_base;
                $libobject->remote_libs[$id]['id'] = $id;
                $libobject->remote_libs[$id]['type'] = $new_type;
                $libobject->remote_libs[$id]['lib'] = $new_lib;
                $libobject->remote_libs[$id]['version'] = $new_version;
                $libobject->remote_libs[$id]['scope'] = $new_scope;
                $libobject->remote_libs[$id]['package'] = $new_package;
                $libobject->remote_libs[$id]['base'] = $new_base;
                $libobject->remote_libs[$id]['src'] = $new_src;
                $libobject->remote_libs[$id]['load'] = $new_load;
                $libobject->remote_libs[$id]['origin'] = 'remote';
            }
            // let xarCSS::__destruct know we need to save this
            $libobject->refreshed = true;
        }

        foreach($libobject->default_libs as $key => $value) {
            if ($value['origin'] == 'remote') {
                unset($libobject->default_libs[$key]);
            }
        }
        foreach ($libobject->remote_libs as $id => $lib) {
            if (empty($lib['load'])) continue;
            $libobject->default_libs[$id] = $lib;
            $libobject->default_libs[$id]['seq'] = 0;
            $libobject->default_libs[$id]['package'] = 'remote';
            $libobject->default_libs[$id]['position'] = 'head';
            $libobject->default_libs[$id]['origin'] = 'remote';
        }
        // Sort the array by sequence
        $temp = array();
        foreach($libobject->default_libs as &$ma) {
            $temp[] = &$ma["seq"];
        }
        array_multisort($temp, $libobject->default_libs);
        // Now resequence the array to start with seq = 1
        $index = 0;
        foreach($libobject->default_libs as $key => $value) {
            $index++;
            $libobject->default_libs[$key]['seq'] = $index;
            $libobject->default_libs[$key]['load'] = 1;
        }

        $data['fieldvalues'] = $libobject->remote_libs;

        // Sort the libraries by name and by descending version
        if (!empty($data['fieldvalues'])) {
            foreach ($data['fieldvalues'] as $key => $row) {
                $templib[$key]  = $row['lib'];
                $tempversion[$key] = $row['version'];
            }
            array_multisort($templib, SORT_ASC, $tempversion, SORT_DESC, $data['fieldvalues']);
        }
        
        // For remote libraries we need the fields to be modifiable
        foreach ($data['properties'] as $name => $property) {
            $data['properties'][$name]->setInputStatus(DataPropertyMaster::DD_INPUTSTATE_MODIFY);
        }
    } else {
    }
    return $data;
}

?>