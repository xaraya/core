<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 */
/* Include parent class */
sys::import('modules.dynamicdata.class.properties.master');
/**
 * Class to handle file upload properties
 */
class FileUploadProperty extends DataProperty
{
    public $id         = 9;
    public $name       = 'fileupload';
    public $desc       = 'File Upload';
    public $reqmodules = array('base');

    public $display_size                    = 40;
    public $validation_max_file_size          = 1000000;
    public $initialization_basedirectory    = 'var/uploads';
    public $initialization_importdirectory  = null;
    public $validation_file_extensions      = 'gif|jpg|jpeg|png|bmp|pdf|doc|txt';
    public $initialization_basepath         = null;
    public $initialization_multiple         = TRUE;
    public $methods = array('trusted'  => false,
                            'external' => false,
                            'upload'   => false,
                            'stored'   => false);

    // this is used by DataPropertyMaster::addProperty() to set the $object->upload flag
    public $upload = true;
    public $UploadsModule_isHooked          = FALSE;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'fileupload';
        $this->filepath   = 'modules/base/xarproperties';

        // Determine if the uploads module is hooked to the calling module
        // if so, we will use the uploads modules functionality
        if (xarVarGetCached('Hooks.uploads','ishooked')) {
            $this->UploadsModule_isHooked = TRUE;
        } else {
        // FIXME: this doesn't take into account the itemtype or non-main module objects
            $list = xarModGetHookList(xarModGetName(), 'item', 'transform');
            foreach ($list as $hook) {
                if ($hook['module'] == 'uploads') {
                    $this->UploadsModule_isHooked = TRUE;
                    break;
                }
            }
        }

        if(xarServerGetVar('PATH_TRANSLATED')) {
            $base_directory = dirname(realpath(xarServerGetVar('PATH_TRANSLATED')));
        } elseif(xarServerGetVar('SCRIPT_FILENAME')) {
            $base_directory = dirname(realpath(xarServerGetVar('SCRIPT_FILENAME')));
        } else {
            $base_directory = './';
        }

        $this->initialization_basepath = $base_directory;

        if (empty($this->initialization_basedirectory) && $this->UploadsModule_isHooked != TRUE) {
            $this->initialization_basedirectory = 'var/uploads';
        }

        if (empty($this->validation_file_extensions)) $this->validation_file_extensions = '';

        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->initialization_basedirectory) && preg_match('/\{theme\}/',$this->initialization_basedirectory)) {
            $curtheme = xarTplGetThemeDir();
            $this->initialization_basedirectory = preg_replace('/\{theme\}/',$curtheme,$this->initialization_basedirectory);
        }

        // Note : {user} will be replaced by the current user uploading the file - e.g. var/uploads/{user} -&gt; var/uploads/myusername_123
        if (!empty($this->initialization_basedirectory) && preg_match('/\{user\}/',$this->initialization_basedirectory)) {
            $uname = 'user';
            $id = xarUserGetVar('id');
            // Note: we add the userid just to make sure it's unique e.g. when filtering
            // out unwanted characters through xarVarPrepForOS, or if the database makes
            // a difference between upper-case and lower-case and the OS doesn't...
            $udir = $uname . '_' . $id;
            $this->initialization_basedirectory = preg_replace('/\{user\}/',$udir,$this->initialization_basedirectory);
        }
        if (!empty($this->initialization_importdirectory) && preg_match('/\{user\}/',$this->initialization_importdirectory)) {
            $uname = 'user';
            $id = xarUserGetVar('id');
            // Note: we add the userid just to make sure it's unique e.g. when filtering
            // out unwanted characters through xarVarPrepForOS, or if the database makes
            // a difference between upper-case and lower-case and the OS doesn't...
            $udir = $uname . '_' . $id;
            $this->initialization_importdirectory = preg_replace('/\{user\}/',$udir,$this->initialization_importdirectory);
        }
    }

    function checkInput($name='', $value = null)
    {
        if (empty($name)) $name = 'dd_' . $this->id;

        // Store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET);
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        // the variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a hidden field to keep track of any previously uploaded file here
        if (!parent::validateValue($value)) return false;

        if (isset($this->fieldname)) $name = $this->fieldname;
        else $name = 'dd_'.$this->id;

        // retrieve new value for preview + new/modify combinations
        if (xarVarIsCached('DynamicData.FileUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.FileUpload',$name);
            return true;
        }

        // if the uploads module is hooked in, use it's functionality instead
        if ($this->UploadsModule_isHooked == TRUE) {
            // set override for the upload/import paths if necessary
            if (!empty($this->initialization_basedirectory) || !empty($this->initialization_importdirectory)) {
                $override = array();
                if (!empty($this->initialization_basedirectory)) {
                    $override['upload'] = array('path' => $this->initialization_basedirectory);
                }
                if (!empty($this->initialization_importdirectory)) {
                    $override['import'] = array('path' => $this->initialization_importdirectory);
                }
            } else {
                $override = null;
            }

            $return = xarModAPIFunc('uploads','admin','validatevalue',
                                    array('id' => $name, // not $this->id
                                          'value' => $value,
                                          // pass the module id, item type and item id (if available) for associations
                                          'moduleid' => $this->_moduleid,
                                          'itemtype' => $this->_itemtype,
                                          'itemid'   => !empty($this->_itemid) ? $this->_itemid : null,
                                          'multiple' => $this->initialization_multiple,
                                          'format' => 'fileupload',
                                          'methods' => $this->methods,
                                          'override' => $override,
                                          'maxsize' => $this->validation_max_file_size));
            // TODO: this raises exceptions now, we want to catch some of them
            // TODO: Insert try/catch clause once we know what uploads raises
            // TODO:
            if (!isset($return) || !is_array($return) || count($return) < 2) {
                $this->value = null;
                return false;
            }
            if (empty($return[0])) {
                $this->value = null;
                $this->invalid = xarML('value');
                return false;
            } else {
                if (empty($return[1])) {
                    $this->value = '';
                } else {
                    $this->value = $return[1];
                }
                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload',$name,$this->value);
                return true;
            }
        }

//        $upname = $name .'_upload';

        if (isset($_FILES[$name])) {
            $file =& $_FILES[$name];
        } else {
            $file = array();
        }

//            var_dump($file);exit;
        if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && $file['size'] > 0 && $file['size'] < $this->validation_max_file_size) {
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($_FILES[$name]['name'])) {
                if (!$this->validateExtension($file['name'])) {
                    $this->invalid = xarML('The file type is not allowed');
                    $this->value = null;
                    return false;
                } elseif (!move_uploaded_file($file['tmp_name'], $this->initialization_basepath . '/' . $this->initialization_basedirectory . '/'. $fileName)) {
                    $this->invalid = xarML('The file upload failed');
                    $this->value = null;
                    return false;
                }
                $this->value = $fileName;
                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload',$name,$fileName);
            } else {
            // TODO: assign random name + figure out mime type to add the right extension ?
                $this->invalid = xarML('The file name for upload is empty');
                $this->value = null;
                return false;
            }
        // retrieve new value for preview + new/modify combinations
        } elseif (xarVarIsCached('DynamicData.FileUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.FileUpload',$name);
        } elseif (!empty($value) &&  !(is_numeric($value) || stristr($value, ';'))) {
            if (!$this->validateExtension($value)) {
                $this->invalid = xarML('The file type is not allowed');
                $this->value = null;
                return false;
            } elseif (!file_exists($this->initialization_basedirectory . '/'. $value) || !is_file($this->initialization_basedirectory . '/'. $value)) {
                $this->invalid = xarML('The file cannot be found');
                $this->value = null;
                return false;
            }
            $this->value = $value;
        } else {
            // No file name entered, ignore
            $this->value = null;
            return true;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        $data['name'] = empty($data['name']) ? 'dd_'.$this->id : $data['name'];
        $data['upname'] = $data['name'] .'_upload';
//        $id = empty($id) ? $name : $id;
//        if (!isset($value)) {
//            $value = $this->value;
//        }
        
        // Allow overriding by specific parameters
            if (isset($data['size']))   $this->display_size = $data['size'];
            if (isset($data['maxsize']))   $this->validation_max_file_size = $data['maxsize'];
            if (isset($data['extensions']))   $this->validation_file_extensions = $data['extensions'];

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVarSetCached('Hooks.dynamicdata','withupload',1);

        if ($this->UploadsModule_isHooked == TRUE) {
            // user must have hooked the uploads module after uploading files directly
            // CHECKME: remove any left over values - or migrate entries to uploads table ?
            if (!empty($value) && !is_numeric($value) && !stristr($value, ';')) {
                $value = '';
            }
            // set override for the upload/import paths if necessary
            if (!empty($this->initialization_basedirectory) || !empty($this->initialization_importdirectory)) {
                $override = array();
                if (!empty($this->initialization_basedirectory)) {
                    $override['upload'] = array('path' => $this->initialization_basedirectory);
                }
                if (!empty($this->initialization_importdirectory)) {
                    $override['import'] = array('path' => $this->initialization_importdirectory);
                }
            } else {
                $override = null;
            }
            // @todo try to get rid of this
            return xarModAPIFunc('uploads','admin','showinput',
                                 array('id' => $name, // not $this->id
                                       'value' => $value,
                                       'multiple' => $this->initialization_multiple,
                                       'format' => 'fileupload',
                                       'methods' => $this->methods,
                                       'override' => $override,
                                       'invalid' => $this->invalid));
        }

        // user must have unhooked the uploads module
        // remove any left over values
        if (!empty($data['value']) && (is_numeric($data['value']) || stristr($data['value'], ';'))) $data['value'] = '';

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        if ($this->UploadsModule_isHooked) {
            // @todo get rid of this one too
            return xarModAPIFunc('uploads','user','showoutput',
                                 array('value' => $value,
                                       'format' => 'fileupload',
                                       'multiple' => $this->initialization_multiple));
        }

        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            if (is_numeric($value) || stristr($value, ';')) {
                // user must have unhooked the uploads module
                // remove any left over values
                return '';
            }
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($this->initialization_basedirectory) && file_exists($this->initialization_basedirectory . '/'. $value) && is_file($this->initialization_basedirectory . '/'. $value)) {
                $data['basedir'] = $this->initialization_basedirectory;
            } else {
                $data['basedir'] = null; // something went wrong here
            }
            return parent::showOutput($data);
        } else {
            return '';
        }
    }

    public function validateExtension($filename='')
    {
        $filetype = $this->validation_file_extensions;
        $filename = xarVarPrepForOS(basename(strval($filename)));
        return (!empty($filetype) && preg_match("/\.$filetype$/",$filename));
    }
    
/*    public function parseConfiguration($validation = '')
    {
        if ($this->UploadsModule_isHooked == TRUE) {
            list($multiple, $methods, $basedir, $importdir) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $validation);

            $this->initialization_multiple = $multiple;
            $this->methods = $methods;
            $this->initialization_basedirectory = $basedir;
            $this->initialization_importdirectory = $importdir;
            $this->validation_max_file_size = xarModVars::get('uploads', 'file.maxsize');

        } elseif (!empty($validation)) {
            // specify base directory and optional file types in validation
            // field - e.g. this/dir or this/dir;(gif|jpg|png|bmp) or this/dir;(gif|jpg|png|bmp);1500000
            $fields = explode(';',$validation);
            $this->initialization_basedirectory = trim($fields[0]);
            if (count($fields) > 1) {
                $this->validation_file_extensions = trim($fields[1]);
                if (count($fields) > 2) {
                    $this->validation_max_file_size = trim($fields[2]);
                }
            } else {
                $this->validation_file_extensions = '';
            }
        } else {
            // use the default values
        }
    }

    public function showConfiguration(Array $args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        $data['size']       = !empty($size) ? $size : 50;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;

        if (isset($validation)) {
            $this->configuration = $validation;
            $this->parseConfiguration($validation);
        }

        if (xarVarGetCached('Hooks.uploads','ishooked')) {
            $data['ishooked'] = true;
        } else {
            $data['ishooked'] = false;
        }
        if ($data['ishooked']) {
            $data['multiple'] = $this->initialization_multiple;
            $data['methods'] = $this->methods;
            $data['basedir'] = $this->initialization_basedirectory;
            $data['importdir'] = $this->initialization_importdirectory;
        } else {
            $data['basedir'] = $this->initialization_basedirectory;
            if (!empty($this->validation_file_extensions)) {
                $this->validation_file_extensions = strtr($this->validation_file_extensions, array('(' => '', ')' => ''));
                $data['filetype'] = explode('|',$this->validation_file_extensions);
            } else {
                $data['filetype'] = array();
            }
            $numtypes = count($data['filetype']);
            if ($numtypes < 4) {
                for ($i = $numtypes; $i < 4; $i++) {
                    $data['filetype'][] = '';
                }
            }
            $data['maxsize'] = $this->validation_max_file_size;
        }
        $data['other'] = '';

        return xarTplProperty('base', $template, 'configuration', $data);
    }

    public function updateConfiguration(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // do something with the validation and save it in $this->configuration
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['other'])) {
                    $this->configuration = $validation['other'];

                } elseif ($this->UploadsModule_isHooked) {
                    $this->configuration = '';
                    if (!empty($validation['multiple'])) {
                        $this->configuration = 'multiple';
                    } else {
                        $this->configuration = 'single';
                    }
// CHECKME: verify format of methods(...) part
                    if (!empty($validation['methods'])) {
                        $todo = array();
                        foreach (array_keys($this->methods) as $method) {
                            if (!empty($validation['methods'][$method])) {
                                $todo[] = '+' .$method;
                            } else {
                                $todo[] = '-' .$method;
                            }
                        }
                        if (count($todo) > 0) {
                            $this->configuration .= ';methods(';
                            $this->configuration .= join(',',$todo);
                            $this->configuration .= ')';
                        }
                    }
                    if (!empty($validation['basedir'])) {
                        $this->configuration .= ';basedir(' . $validation['basedir'] . ')';
                    }
                    if (!empty($validation['importdir'])) {
                        $this->configuration .= ';importdir(' . $validation['importdir'] . ')';
                    }
                } else {
                    $this->configuration = '';
                    if (!empty($validation['basedir'])) {
                        $this->configuration = $validation['basedir'];
                    }
                    if (!empty($validation['filetype'])) {
                        $todo = array();
                        foreach ($validation['filetype'] as $ext) {
                            if (empty($ext)) continue;
                            $todo[] = $ext;
                        }
                        if (count($todo) > 0) {
                            $this->configuration .= ';(';
                            $this->configuration .= join('|',$todo);
                            $this->configuration .= ')';
                        }
                    }
                    if (!empty($validation['maxsize'])) {
                        if (empty($todo)) {
                            $this->configuration .= ';';
                        }
                        $this->configuration .= ';' . $validation['maxsize'];
                    }
                }
            } else {
                $this->configuration = $validation;
            }
        }

        return true;
    }
*/
}

?>
