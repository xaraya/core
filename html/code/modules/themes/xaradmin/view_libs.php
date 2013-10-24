<?php
/**
 * View the configuration options
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */

sys::import('modules.dynamicdata.class.objects.master');

function themes_admin_view_libs()
{
    // Security
     if(!xarSecurityCheck('EditThemes')) return;

    $data['object'] = DataObjectMaster::getObject(array('name' => 'themes_libraries'));

    if (!isset($data['object'])) {return;}
    if (!$data['object']->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $data['object']->label));
    $data['properties'] = $data['object']->getProperties();
       
    sys::import('modules.themes.class.xarjs');
    $libobject = xarJS::getInstance(); 

    // Arrange all the lib files in a nice list, with a unique ID index
    $data['fieldvalues'] = array();
    $seqindex = count($data['fieldvalues']);
    if (is_array($libobject->libs)) {
        foreach($libobject->libs as $lib) {
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
                                    'position' => 'head',
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

    if(!xarVarFetch('confirm',      'bool', $confirm,          false, XARVAR_DONT_SET)) {return;}
    if ($confirm) {
        if(!xarVarFetch('dd_load',     'array', $dd_load,         array(), XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('dd_seq',      'array', $dd_seq,          array(), XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('dd_position', 'array', $dd_position,     array(), XARVAR_DONT_SET)) {return;}
        $libobject->default_libs = array();
        foreach (array_keys($dd_load) as $id) {
            // The file may have disappeared in the meantime
            if (!isset($data['fieldvalues'][$id])) continue;
            $libobject->default_libs[$id] = $data['fieldvalues'][$id];
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
    }
    
    // Strip out any default libs whose files are no longer present
    // For those still present remove their equivalent in the defaultvalue array
    // We will now have each entry present in only one of the two arays
    foreach ($libobject->default_libs as $row) {
        if (!isset($data['fieldvalues'][$row['id']])) {
            unset($libobject->default_libs[$row['id']]);
        } else {
            unset($data['fieldvalues'][$row['id']]);
        }
    }
    
    // Now mewrge the two arrays, with the default libs at the top
    $temp = $data['fieldvalues'];
    $data['fieldvalues'] = $libobject->default_libs;
    $seqindex = count($data['fieldvalues']);
    foreach ($temp as $row) {
        $seqindex++;
        $row['seq'] = $seqindex;
        $row['load'] = 0;
        $data['fieldvalues'][$row['id']] = $row;
    }
    return $data;
}

?>