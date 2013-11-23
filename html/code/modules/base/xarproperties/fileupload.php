<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/* Include parent class */
sys::import('modules.dynamicdata.class.properties.base');
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
//    public $initialization_basepath         = null;
    // TODO: support the different options in code below
    public $initialization_multiple         = TRUE;
    public $methods = array('trusted'  => false,
                            'external' => false,
                            'upload'   => false,
                            'stored'   => false);
    public $validation_allow_duplicates     = 2; // Overwrite the old instance
    public $obfuscate_filename              = false;
    // Note: if you use this, make sure you unlink($this->value) yourself once you're done with it
    public $use_temporary_file              = false;
    // Remove leftover values (numeric or ;...) when the uploads module is unhooked
    public $remove_leftover_values          = true;

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
            if (xarModIsHooked('uploads', xarModGetName())) {
                $this->UploadsModule_isHooked = true;
            }
            /*
            $list = xarModGetHookList(xarModGetName(), 'item', 'transform');
            foreach ($list as $hook) {
                if ($hook['module'] == 'uploads') {
                    $this->UploadsModule_isHooked = TRUE;
                    break;
                }
            }
            */
        }
/*
        if(xarServer::getVar('PATH_TRANSLATED')) {
            $basepath = dirname(realpath(xarServer::getVar('PATH_TRANSLATED')));
        } elseif(xarServer::getVar('SCRIPT_FILENAME')) {
            $basepath = dirname(realpath(xarServer::getVar('SCRIPT_FILENAME')));
        } else {
            $basepath = './';
        }

        $this->initialization_basepath = $basepath;
*/
        $this->initialization_basepath = sys::root();
        
        if (empty($this->initialization_basedirectory) && $this->UploadsModule_isHooked != TRUE) {
            $this->initialization_basedirectory = 'var/uploads';
        }

        if (empty($this->validation_file_extensions)) $this->validation_file_extensions = '';

        // Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory
        $this->initialization_basedirectory = preg_replace('/\{user_theme\}/',"themes/".xarModVars::get('themes', 'default_theme'),$this->initialization_basedirectory);
        $this->initialization_basedirectory = preg_replace('/\{admin_theme\}/',"themes/".xarModVars::get('themes', 'admin_theme'),$this->initialization_basedirectory);
        $this->initialization_basedirectory = preg_replace('/\{theme\}/',xarTpl::getThemeDir(),$this->initialization_basedirectory);

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
        if (empty($name)) $name = $this->propertyprefix . $this->id;

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
        else $name = $this->propertyprefix . $this->id;

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

            $return = xarMod::apiFunc('uploads','admin','validatevalue',
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

        if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && $file['size'] > 0 && $file['size'] < $this->validation_max_file_size) {
            if (!empty($_FILES[$name]['name'])) {
                if (!$this->validateExtension($file['name'])) {
                    $this->invalid = xarML('The file type is not allowed');
                    $this->value = null;
                    return false;
                }
                $filename = $file['name'];
            } else {
            // TODO: assign random name + figure out mime type to add the right extension ?
                $filename = uniqid(md5(mt_rand()), true);
            }

            // use a temporary file if we process the file directly after validation (read & delete, move, save to db, ...)
            if ($this->use_temporary_file) {
                $filepath = tempnam(realpath($this->initialization_basepath . $this->initialization_basedirectory), 'tempdd');

            //} elseif ($this->obfuscate_filename) {
            // TODO: obfuscate filename + return hash & original filename + handle that combined value in the other methods
            //    // cfr. file_obfuscate_name() function in uploads module
            //    $filehash = crypt($filename, substr(md5(time() . $filename . getmypid()), 0, 2));
            //    $filehash = substr(md5($filehash), 0, 8) . time() . getmypid();
            //    $fileparts = explode('.', $filename);
            //    if (count($fileparts) > 1) {
            //        $filehash .= '.' . array_pop($fileparts);
            //    }
            //    $filepath = $this->initialization_basepath . $this->initialization_basedirectory . '/'. $filehash;

            } else {
                $filename = $file['name'];
                $filepath = $this->initialization_basepath . $this->initialization_basedirectory . '/'. $filename;
                if ($this->validation_allow_duplicates == 2) {
                    // overwrite existing file if necessary
                } elseif ($this->validation_allow_duplicates == 1 && file_exists($filepath)) {
                    // create new instance of the file
                    $fileparts = explode('.', $filename);
                    if (count($fileparts) > 1) {
                        $fileext = '.' . array_pop($fileparts);
                        $filebase = implode('.', $fileparts);
                    } else {
                        $fileext = '';
                        $filebase = $filename;
                    }
                    $i = 1;
                    $filename = $filebase . '_' . $i . $fileext;
                    $filepath = $this->initialization_basepath . $this->initialization_basedirectory . '/'. $filename;
                    while (file_exists($filepath)) {
                        $i++;
                        $filename = $filebase . '_' . $i . $fileext;
                        $filepath = $this->initialization_basepath . $this->initialization_basedirectory . '/'. $filename;
                    }
                } elseif ($this->validation_allow_duplicates == 0 && file_exists($filepath)) {
                    // duplicate files are not allowed
                    $this->invalid = xarML('This file already exists');
                    $this->value = null;
                    return false;
                }
            }

            try {
                move_uploaded_file($file['tmp_name'], $filepath);
            } catch(Exception $e) {
                $this->invalid = xarML('The file upload failed');
                $this->value = null;
                return false;
            }

            if ($this->use_temporary_file) {
                // We pass the whole path to the temporary file here, since we're not 100% sure where it'll be created
                // Note: if you use this, make sure you unlink($this->value) yourself once you're done with it
                $this->value = $filepath;
                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload',$name,$this->value);

            //} elseif ($this->obfuscate_filename) {
            // TODO: obfuscate filename + return hash & original filename + handle that combined value in the other methods
            //    $this->value = $filehash . ',' . $filename;
            //    // save new value for preview + new/modify combinations
            //    xarVarSetCached('DynamicData.FileUpload',$name,$this->value);

            } else {
                $this->value = $filename;
                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload',$name,$this->value);
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
            // No file name entered, get previous value
            xarVarFetch($name. '_previous', 'isset', $value,  NULL, XARVAR_DONT_SET);
            $this->value = $value;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        $data['name'] = empty($data['name']) ? $this->propertyprefix . $this->id : $data['name'];
        $data['upname'] = $data['name'] .'_upload';
//        $id = empty($id) ? $name : $id;
//        if (!isset($value)) {
//            $value = $this->value;
//        }
        
        // Allow overriding by specific parameters
            if (isset($data['size']))   $this->display_size = $data['size'];
            if (isset($data['maxsize']))   $this->validation_max_file_size = $data['maxsize'];
            if (isset($data['extensions']))   $this->setExtensions($data['extensions']);

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
            return xarMod::apiFunc('uploads','admin','showinput',
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
        if ($this->remove_leftover_values && !empty($data['value']) && (is_numeric($data['value']) || stristr($data['value'], ';'))) $data['value'] = '';

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        if ($this->UploadsModule_isHooked) {
            // @todo get rid of this one too
            return xarMod::apiFunc('uploads','user','showoutput',
                                 array('value' => $value,
                                       'format' => 'fileupload',
                                       'multiple' => $this->initialization_multiple));
        }

        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            if ($this->remove_leftover_values) {
                if (is_numeric($value) || stristr($value, ';')) {
                    // user must have unhooked the uploads module
                    // remove any left over values
                    return '';
                }
                // if the uploads module is hooked (to be verified and set by the calling module)
                if (file_exists($value) && is_file($value)) {
                    $data['file_OK'] = true;
                } else {
                    $data['file_OK'] = false; // something went wrong here
                }
            }
            return parent::showOutput($data);
        } else {
            return '';
        }
    }

    /**
     * Set the list/regex of allowed file extensions, depending on the syntax used (cfr. image, webpage, ...)
     * 
     * @param string|string[] $file_extensions String or array of file extensions.
     *                                      If a string is used, multiple file extensions shall be
     *                                      separated by "," or valid regular expression
     */
    public function setExtensions($file_extensions = null)
    {
        if (isset($file_extensions)) {
            $this->validation_file_extensions = $file_extensions;
        }
        $this->file_extension_list = null;
        $this->file_extension_regex = null;
        if (!empty($this->validation_file_extensions)) {
            // example: array('gif', 'jpg', 'jpeg', ...)
            if (is_array($this->validation_file_extensions)) {
                $this->file_extension_list = $this->validation_file_extensions;

            // example: gif,jpg,jpeg,png,bmp,txt,htm,html
            } elseif (strpos($this->validation_file_extensions, ',') !== false) {
                $this->file_extension_list = explode(',', $this->validation_file_extensions);

            // example: gif|jpe?g|png|bmp|txt|html?
            } else {
                $this->file_extension_regex = $this->validation_file_extensions;
            }
        }
    }

     /**
     * Validate the given filename against the list/regex of allowed file extensions
     * This method can take an extension or a full file name
     * 
     * @param string $filename Extension or full file name
     * @return boolean
     */
    public function validateExtension($filenames = '')
    {
        // Make sure we cover the case of an array, as we might have multiple uploads
        if (!is_array($filenames)) $filenames = array($filenames);

        // Allow if no filename
        if (count($filenames) == 1) {
            $name = end($filenames);
            if (empty($name)) return true;
        }
        
        // If no filetype restriction then let it through
        $filetypes = $this->validation_file_extensions;
        if (empty($filetypes)) return true;
        
        // Validate each array element (name)
        $valid = true;
        foreach ($filenames as $name) {
            $name = xarVarPrepForOS(basename(strval($name)));
            $valid = $valid && preg_match("/\.$filetypes$/",strtolower($name));
        }
        return $valid;
    }
}

?>
