<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminGui;
use DataPropertyMaster;
use xarCSS;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes admin view_csslibs function
 * @extends MethodClass<AdminGui>
 */
class ViewCsslibsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup * @see AdminGui::viewCsslibs()
     */

    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditThemes')) {
            return;
        }

        $data = [];
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'remote');

        $data['object'] = $this->data()->getObject(['name' => 'themes_csslibraries']);

        if (!isset($data['object'])) {
            return;
        }
        if (!$data['object']->checkAccess('view')) {
            return $this->ctl()->forbidden($this->ml('View #(1) is forbidden', $data['object']->label));
        }
        $data['properties'] = $data['object']->getProperties();

        sys::import('modules.themes.class.xarcss');
        $libobject = xarCSS::getInstance();
        // CHECKME: is this the right place to do it?
        $libobject->refresh();

        if ($data['tab'] == 'auto') {
            $this->var()->check('confirm', $confirm, 'bool', false);
            if ($confirm) {
                $this->var()->check('dd_seq', $dd_seq, 'array', []);
                $this->var()->check('dd_position', $dd_position, 'array', []);
                foreach (array_keys($dd_seq) as $id) {
                    $libobject->default_libs[$id]['seq'] = $dd_seq[$id];
                    $libobject->default_libs[$id]['position'] = $dd_position[$id];
                }
                // Sort the array by sequence
                $temp = [];
                foreach ($libobject->default_libs as &$ma) {
                    $temp[] = &$ma["seq"];
                }
                array_multisort($temp, $libobject->default_libs);
                // Now resequence the array to start with seq = 1
                $index = 0;
                foreach ($libobject->default_libs as $key => $value) {
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
            $data['fieldvalues'] = [];
            $seqindex = count($data['fieldvalues']);
            if (is_array($libobject->local_libs)) {
                foreach ($libobject->local_libs as $lib) {
                    foreach ($lib->styles as $version => $versionarray) {
                        foreach ($versionarray as $scope => $scopearray) {
                            foreach ($scopearray as $package => $packagearray) {
                                foreach ($packagearray as $path => $patharray) {
                                    foreach ($patharray as $file => $filearray) {//echo "<pre>";var_dump($filearray['lib']);
                                        $id = $filearray['lib'] . "." . $version . "." . $scope . "." . $package . "." . $path . "." . $file;
                                        $id = str_replace('/', '_', $id);
                                        $id = str_replace(' ', '_', $id);
                                        $id = str_replace('.', '_', $id);
                                        $id = str_replace('-', '_', $id);
                                        $seqindex++;
                                        $data['fieldvalues'][$id] = [
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
                                        ];
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

            $this->var()->check('confirm', $confirm, 'bool', false);
            if ($confirm) {
                $this->var()->check('dd_load', $dd_load, 'array', []);
                $this->var()->check('dd_seq', $dd_seq, 'array', []);

                // We remove the local auto loading libraries and then repopulate them
                foreach ($libobject->default_libs as $key => $value) {
                    if ($value['origin'] == 'local') {
                        unset($libobject->default_libs[$key]);
                    }
                }
                foreach (array_keys($dd_load) as $id) {
                    // The file may have disappeared in the meantime
                    if (!isset($data['fieldvalues'][$id])) {
                        continue;
                    }
                    $libobject->default_libs[$id] = $data['fieldvalues'][$id];
                    $libobject->default_libs[$id]['seq'] = $dd_seq[$id];
                    $libobject->default_libs[$id]['position'] = 'head';
                    $libobject->default_libs[$id]['origin'] = 'local';
                }
                // Sort the array by sequence
                $temp = [];
                foreach ($libobject->default_libs as &$ma) {
                    $temp[] = &$ma["seq"];
                }
                array_multisort($temp, $libobject->default_libs);
                // Now resequence the array to start with seq = 1
                $index = 0;
                foreach ($libobject->default_libs as $key => $value) {
                    if ($value['origin'] == 'remote') {
                        continue;
                    }
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
                    if ($row['origin'] == 'remote') {
                        continue;
                    }
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
            $this->var()->check('confirm', $confirm, 'bool', false);
            if ($confirm) {
                $this->var()->check('dd_id', $dd_id, 'array', []);
                $this->var()->check('dd_type', $dd_type, 'array', []);
                $this->var()->check('dd_lib', $dd_lib, 'array', []);
                $this->var()->check('dd_version', $dd_version, 'array', []);
                $this->var()->check('dd_scope', $dd_scope, 'array', []);
                $this->var()->check('dd_package', $dd_package, 'array', []);
                $this->var()->check('dd_base', $dd_base, 'array', []);
                $this->var()->check('dd_src', $dd_src, 'array', []);
                $this->var()->check('dd_load', $dd_load, 'array', []);

                $libobject->remote_libs = [];
                foreach ($dd_id as $id) {
                    if (empty($dd_lib[$id])) {
                        continue;
                    }
                    $libobject->remote_libs[$id]['id'] = $id;
                    $libobject->remote_libs[$id]['type'] = $dd_type[$id];
                    $libobject->remote_libs[$id]['lib'] = $dd_lib[$id];
                    $libobject->remote_libs[$id]['version'] = $dd_version[$id];
                    $libobject->remote_libs[$id]['scope'] = $dd_scope[$id];
                    $libobject->remote_libs[$id]['package'] = $dd_package[$id];
                    $libobject->remote_libs[$id]['base'] = $dd_base[$id];
                    $libobject->remote_libs[$id]['src'] = $dd_src[$id];
                    if (!isset($dd_load[$id])) {
                        $dd_load[$id] = 0;
                    }
                    $libobject->remote_libs[$id]['load'] = $dd_load[$id];
                    $libobject->remote_libs[$id]['origin'] = 'remote';
                }

                // Add a new remote stylesheet
                $this->var()->check('new_lib', $new_lib, 'str', '');
                if (!empty($new_lib)) {
                    $this->var()->check('new_id', $new_id, 'str', '');
                    $this->var()->check('new_type', $new_type, 'str', '');
                    $this->var()->check('new_version', $new_version, 'str', '');
                    $this->var()->check('new_scope', $new_scope, 'str', '');
                    $this->var()->check('new_package', $new_package, 'str', '');
                    $this->var()->check('new_base', $new_base, 'str', '');
                    $this->var()->check('new_src', $new_src, 'str', '');
                    $this->var()->check('new_load', $new_load, 'str', '');
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

            foreach ($libobject->default_libs as $key => $value) {
                if ($value['origin'] == 'remote') {
                    unset($libobject->default_libs[$key]);
                }
            }
            foreach ($libobject->remote_libs as $id => $lib) {
                if (empty($lib['load'])) {
                    continue;
                }
                $libobject->default_libs[$id] = $lib;
                $libobject->default_libs[$id]['seq'] = 0;
                $libobject->default_libs[$id]['package'] = 'remote';
                $libobject->default_libs[$id]['position'] = 'head';
                $libobject->default_libs[$id]['origin'] = 'remote';
            }
            // Sort the array by sequence
            $temp = [];
            foreach ($libobject->default_libs as &$ma) {
                $temp[] = &$ma["seq"];
            }
            array_multisort($temp, $libobject->default_libs);
            // Now resequence the array to start with seq = 1
            $index = 0;
            foreach ($libobject->default_libs as $key => $value) {
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
}
