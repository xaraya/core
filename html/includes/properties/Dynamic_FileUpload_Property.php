<?php
/**
 * Dynamic File Upload Property (TODO: work with uploads module)
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Class to handle file upload properties
 *
 * @package dynamicdata
 */

class Dynamic_FileUpload_Property extends Dynamic_Property
{
    var $size = 40;
    var $maxSize = 1000000;
    var $basedir;
    var $filetype;
    var $UploadsModule_isHooked = FALSE;
    var $basePath;
    var $multiple = TRUE;

    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    var $upload = true;

    function Dynamic_FileUpload_Property($args)
    {
        parent::Dynamic_Property($args);

        if (empty($this->id)) {
            $this->id = $this->name;
        }

        if (!empty($this->validation)) {
            $options = explode(';', strtolower($this->validation));
            if (in_array('single', $options)) {
                $this->multiple = FALSE;
            } else {
                $this->multiple = TRUE;
            }
            unset($options);
        }

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

        $this->basePath = $base_directory;

        // specify base directory and optional file types in validation
        // field - e.g. this/dir or this/dir;(gif|jpg|png|bmp).
        if (empty($this->basedir) && !empty($this->validation)) {
            if (strchr($this->validation,';')) {
                list($dir,$type) = explode(';',$this->validation);
                $this->basedir = trim($dir);
                $this->filetype = trim($type);
            } else {
                $this->basedir = $this->validation;
                $this->filetype = '';
            }
        }

        if (empty($this->basedir)) {
            $this->basedir = 'var/uploads';
        }

        if (empty($this->filetype)) {
            $this->filetype = '';
        }
        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->basedir) && preg_match('/\{theme\}/',$this->basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->basedir = preg_replace('/\{theme\}/',$curtheme,$this->basedir);
        }
    }

    function validateValue($value = null)
    {
        // the variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a hidden field to keep track of any previously uploaded file here
        if (!isset($value)) {
            $value = $this->value;
        }
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }

        // retrieve new value for preview + new/modify combinations
        if (xarVarIsCached('DynamicData.FileUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.FileUpload',$name);
            return true;
        }

        // if the uploads module is hooked in, use it's functionality instead
        if ($this->UploadsModule_isHooked == TRUE) {
            list($multiple, $methods) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $this->validation);
            $return = xarModAPIFunc('uploads','admin','validatevalue',
                                    array('id' => $name, // not $this->id
                                          'value' => $value,
                                          'multiple' => $multiple,
                                          'format' => 'fileupload',
                                          'methods' => $methods,
                                          'maxsize' => $this->maxSize));
            if (!isset($return) || !is_array($return) || count($return) < 2) {
                $this->value = null;
            // CHECKME: copied from autolinks :)
                // 'text' rendering will return an array
                $errorstack = xarErrorGet();
                $errorstack = array_shift($errorstack);
                $this->invalid = $errorstack['short'];
                xarErrorHandled();
                return false;
            }
            if (empty($return[0])) {
                $this->value = null;
                $this->invalid = xarML('value');
                return false;
            } else {
                $this->value = $return[1];
                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload',$name,$this->value);
                return true;
            }
        }

        $upname = $name .'_upload';
        $filetype = $this->filetype;

        if (isset($_FILES[$upname])) {
            $file =& $_FILES[$upname];
        } else {
            $file = array();
        }

        if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && $file['size'] > 0 && $file['size'] < $this->maxSize) {
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($_FILES[$upname]['name'])) {
                $fileName = xarVarPrepForOS(basename(strval($file['name'])));
                if (!empty($filetype) && !preg_match("/\.$filetype$/",$fileName)) {
                    $this->invalid = xarML('file type');
                    $this->value = null;
                    return false;
                } elseif (!move_uploaded_file($file['tmp_name'], $this->basePath . '/' . $this->basedir . '/'. $fileName)) {
                    $this->invalid = xarML('file upload failed');
                    $this->value = null;
                    return false;
                }
                $this->value = $fileName;
                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload',$name,$fileName);
            } else {
            // TODO: assign random name + figure out mime type to add the right extension ?
                $this->invalid = xarML('file name for upload');
                $this->value = null;
                return false;
            }
        // retrieve new value for preview + new/modify combinations
        } elseif (xarVarIsCached('DynamicData.FileUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.FileUpload',$name);
        } elseif (!empty($value) &&  !(is_numeric($value) || stristr($value, ';'))) {
            if (!empty($filetype) && !preg_match("/\.$filetype$/",$value)) {
                $this->invalid = xarML('file type');
                $this->value = null;
                return false;
            } elseif (!file_exists($this->basedir . '/'. $value) || !is_file($this->basedir . '/'. $value)) {
                $this->invalid = xarML('file');
                $this->value = null;
                return false;
            }
            $this->value = $value;
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxSize = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        $upname = $name .'_upload';

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVarSetCached('Hooks.dynamicdata','withupload',1);

        if ($this->UploadsModule_isHooked == TRUE) {
            list($multiple, $methods) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $this->validation);

            // user must have hooked the uploads module after uploading files directly
            // CHECKME: remove any left over values - or migrate entries to uploads table ?
            if (!empty($value) && !is_numeric($value) && !stristr($value, ';')) {
                $value = '';
            }
            return xarModAPIFunc('uploads','admin','showinput',
                                 array('id' => $name, // not $this->id
                                       'value' => $value,
                                       'multiple' => $multiple,
                                       'format' => 'fileupload',
                                       'methods' => $methods));
        }

        // user must have unhooked the uploads module
        // remove any left over values
        if (!empty($value) && (is_numeric($value) || stristr($value, ';'))) {
            $value = '';
        }

        if (!empty($this->filetype)) {
            $extensions = $this->filetype;
            // TODO: get rid of the break
            $allowed = '<br />' . xarML('Allowed file types : #(1)',$extensions);
        } else {
            $extensions = '';
            $allowed = '';
        }

        $data               = array();
        $data['name']       = $name;
        $data['value']      = xarVarPrepForDisplay($value);
        $data['id']         = $id;
        $data['upname']     = $upname;
        $data['size']       = !empty($size) ? $size : $this->size;
        $data['maxsize']    = !empty($maxSize) ? $maxSize : $this->maxSize;
        $data['tabindex']   = !empty($tabindex) ? $tabindex  : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)',  $this->invalid) : '';
        $data['allowed']    = $allowed;
        $data['extensions'] = $extensions;

        $template="fileupload";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
    }

    function showOutput($args = array())
    {

        extract($args);

        if (!isset($value)) {
            $value = $this->value;
        }

        if ($this->UploadsModule_isHooked) {
            return xarModAPIFunc('uploads','user','showoutput',
                                 array('value' => $value,
                                       'format' => 'fileupload',
                                       'multiple' => $this->multiple));
        }

        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            if (is_numeric($value) || stristr($value, ';')) {
                // user must have unhooked the uploads module
                // remove any left over values
                return '';
            }
            $data = array();
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($this->basedir) && file_exists($this->basedir . '/'. $value) && is_file($this->basedir . '/'. $value)) {
                $data['basedir'] = $this->basedir;
            } else {
                $data['basedir'] = null; // something went wrong here
            }
            $data['value'] = xarVarPrepForDisplay($value);

            $template="fileupload";
            return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
        } else {
            return '';
        }
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 9,
                              'name'       => 'fileupload',
                              'label'      => 'File Upload',
                              'format'     => '9',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }

}

?>
