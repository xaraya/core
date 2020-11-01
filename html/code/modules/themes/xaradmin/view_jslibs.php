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

function themes_admin_view_jslibs()
{
    // Security
    if(!xarSecurity::check('EditThemes')) return;

    if (!xarVar::fetch('tab',   'str:1:100', $data['tab'], 'local', xarVar::NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('name' => 'themes_jslibraries'));

    if (!isset($data['object'])) {return;}
    if (!$data['object']->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $data['object']->label));
    $data['properties'] = $data['object']->getProperties();
       
    sys::import('modules.themes.class.xarjs');
    $libobject = xarJS::getInstance(); 
    // CHECKME: is this the right place to do it?
    $libobject->refresh();

    if ($data['tab'] == 'auto') {
        if(!xarVar::fetch('confirm',      'bool', $confirm,          false, xarVar::DONT_SET)) {return;}
        if ($confirm) {
            if(!xarVar::fetch('dd_seq',      'array', $dd_seq,          array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_position', 'array', $dd_position,     array(), xarVar::DONT_SET)) {return;}
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
            // let xarJS::__destruct know we need to save this
            $libobject->refreshed = true;
        }
        $data['fieldvalues'] = $libobject->default_libs;
    } elseif ($data['tab'] == 'local') {
        // Arrange all the lib files in a nice list, with a unique ID index
        $data['fieldvalues'] = array();
        $seqindex = count($data['fieldvalues']);
        if (is_array($libobject->local_libs)) {
            foreach($libobject->local_libs as $lib) {
                foreach($lib->scripts as $version => $versionarray) {
                    foreach($versionarray as $scope => $scopearray) {
                        foreach($scopearray as $package => $packagearray) {
                            foreach($packagearray as $path => $patharray) {
                                foreach($patharray as $file => $filearray) {
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
                                        'plugins' => implode(',',array_keys($lib->plugins)),
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

        if(!xarVar::fetch('confirm',      'bool', $confirm,          false, xarVar::DONT_SET)) {return;}
        if ($confirm) {
            if(!xarVar::fetch('dd_load',     'array', $dd_load,         array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_seq',      'array', $dd_seq,          array(), xarVar::DONT_SET)) {return;}
            
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
            // let xarJS::__destruct know we need to save this
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
        if(!xarVar::fetch('confirm',      'bool', $confirm,          false, xarVar::DONT_SET)) {return;}
        if ($confirm) {
            if(!xarVar::fetch('dd_id',       'array', $dd_id,       array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_type',     'array', $dd_type,     array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_parent',   'array', $dd_parent,   array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_lib',      'array', $dd_lib,      array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_version',  'array', $dd_version,  array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_scope',    'array', $dd_scope,    array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_package',  'array', $dd_package,  array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_base',     'array', $dd_base,     array(), xarVar::DONT_SET)) {return;}
            if(!xarVar::fetch('dd_src',      'array', $dd_src,      array(), xarVar::DONT_SET)) {return;}

            $libobject->remote_libs = array();
            foreach ($dd_id as $id) {
                if (empty($dd_lib[$id])) continue;
                $newid = $dd_type[$id] . "." . $dd_lib[$id] . "." . $dd_version[$id] . "." . $dd_scope[$id] . "." . $dd_base[$id];
                $libobject->remote_libs[$newid]['id'] = $newid;
                $libobject->remote_libs[$newid]['type'] = $dd_type[$id];
                $libobject->remote_libs[$newid]['parent'] = $dd_parent[$id];
                $libobject->remote_libs[$newid]['lib'] = $dd_lib[$id];
                $libobject->remote_libs[$newid]['version'] = $dd_version[$id];
                $libobject->remote_libs[$newid]['scope'] = $dd_scope[$id];
                $libobject->remote_libs[$newid]['package'] = $dd_package[$id];
                $libobject->remote_libs[$newid]['base'] = $dd_base[$id];
                $libobject->remote_libs[$newid]['src'] = $dd_src[$id];
                if (!isset($dd_load[$id])) $dd_load[$id] = 0;
                $libobject->remote_libs[$newid]['load'] = $dd_load[$id];
                $libobject->remote_libs[$newid]['origin'] = 'remote';
            }
            
            // Add a new remote library
            if(!xarVar::fetch('new_lib',      'str', $new_lib,      '', xarVar::DONT_SET)) {return;}
            if (!empty($new_lib)) {
                if(!xarVar::fetch('new_id',       'str', $new_id,       '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_type',     'str', $new_type,     '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_parent',   'str', $new_parent,   '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_version',  'str', $new_version,  '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_scope',    'str', $new_scope,    '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_package',  'str', $new_package,  '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_base',     'str', $new_base,     '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_src',      'str', $new_src,      '', xarVar::DONT_SET)) {return;}
                if(!xarVar::fetch('new_load',     'str', $new_load,     '', xarVar::DONT_SET)) {return;}
                $id = $new_type . "." . $new_lib . "." . $new_version . "." . $new_scope . "." . $new_base;
                $libobject->remote_libs[$id]['id'] = $id;
                $libobject->remote_libs[$id]['type'] = $new_type;
                $libobject->remote_libs[$id]['parent'] = $new_parent;
                $libobject->remote_libs[$id]['lib'] = $new_lib;
                $libobject->remote_libs[$id]['version'] = $new_version;
                $libobject->remote_libs[$id]['scope'] = $new_scope;
                $libobject->remote_libs[$id]['package'] = $new_package;
                $libobject->remote_libs[$id]['base'] = $new_base;
                $libobject->remote_libs[$id]['src'] = $new_src;
                $libobject->remote_libs[$id]['load'] = $new_load;
                $libobject->remote_libs[$id]['origin'] = 'remote';
            }
            // let xarJS::__destruct know we need to save this
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