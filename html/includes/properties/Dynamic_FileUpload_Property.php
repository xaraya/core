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
    
    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    var $upload = true;

    function Dynamic_FileUpload_Property($args)
    {
        $this->Dynamic_Property($args);
        
        // Determine if the uploads module is hooked to the calling module
        // if so, we will use the uploads modules functionality 
        $list = xarModGetHookList(xarModGetName(), 'item', 'transform');
        foreach ($list as $hook) {
            if ($hook['module'] == 'uploads') {
                $this->UploadsModule_isHooked = TRUE;
                break;
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
        
        // if the uploads module is hooked in, use it's functionality instead
        if ($this->UploadsModule_isHooked == TRUE) {
            return $this->_UploadModule_validateValue($value);
        }
        
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
                $fileName = xarVarPrepForOS(basename($file['name']));
                if (!empty($filetype) && !preg_match("/\.$filetype$/",$file)) {
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
                xarVarSetCached('DynamicData.FileUpload',$name,$file);
            } else {
            // TODO: assign random name + figure out mime type to add the right extension ?
                $this->invalid = xarML('file name for upload');
                $this->value = null;
                return false;
            }
        // retrieve new value for preview + new/modify combinations
        } elseif (xarVarIsCached('DynamicData.FileUpload',$name)) {
            $this->value = xarVarGetCached('DynamicData.FileUpload',$name);
        } elseif (!empty($value) &&  !(is_numeric($value) || stristr(';', $value))) {
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
    function showInput($args = array()) {
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
            return $this->_UploadModule_showInput($value);
        } else {
            // user must have unhooked the uploads module
            // remove any left over values
            if (!empty($value) && (is_numeric($value) || stristr(';', $value))) {
                $value = '';
            }
            
            if (!empty($this->filetype)) {
                $extensions = $this->filetype;
                $allowed = '<br />' . xarML('Allowed file types : #(1)',$extensions);
            } else {
                $allowed = '';
            }
        }
        
        $size       = !empty($size) ? $size : $this->size;
        $maxSize    = !empty($maxSize) ? $maxSize : $this->maxSize;
        $tabindex   = !empty($tabindex) ? ' tabindex="' . $tabindex . '" ' : '';
        $value      = !empty($value) ? xarML('Uploaded file: #(1)',$value) . '<br /> <input type="hidden" name="' . $name . '" value="' . $value .'" />' : '';
        $invalid    = !empty($this->invalid) ? '<span class="xar-error">' . xarML('Invalid #(1)',  $this->invalid) . '</span>' : '';
        
        // we're using a hidden field to keep track of any previously uploaded file here
        return ($value .
               '<input type="hidden" name="MAX_FILE_SIZE" value="'. $maxSize  .'" />' .
               '<input type="file" name="'.$upname.'" size="'. $size . '" id="'. $id . '"' . $tabindex . ' /> ' . $allowed . $invalid);
    }

    function showOutput($args = array()) {
        
        extract($args);
        
        if (!isset($value)) {
            $value = $this->value;
        }
        
        if ($this->UploadsModule_isHooked) {
            return $this->_UploadModule_showOutput($value);
        } 
        
        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            if (is_numeric($value) || stristr(';', $value)) {
                // user must have unhooked the uploads module
                // remove any left over values
                return '';
            } 
            
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($this->basedir) && file_exists($this->basedir . '/'. $value) && is_file($this->basedir . '/'. $value)) {
                $value = xarVarPrepForDisplay($value);
            // TODO: convert basedir to base URL when necessary ?
                return '<a href="'.$this->basedir.'/'.$value.'" title="'.xarML('Download').'">'.$value.'</a>';
            } else {
                return xarVarPrepForDisplay($value); // something went wrong here
            }
        } else {
            return '';
        }
    }
        
    function _UploadModule_validateValue($value) {
        
        // If we've just been previewed, then use the value that was passed in :)
        if (!is_null($value) && !empty($value) && (is_numeric($value) || stristr(';', $value))) {
            $this->value = $value;
            return true;
        }        
        
        xarModAPILoad('uploads', 'user');
        
        $typeCheck = 'enum:0:' . _UPLOADS_GET_STORED;
        $typeCheck .= (xarModGetVar('uploads', 'dd.fileupload.external') == TRUE) ? ':' . _UPLOADS_GET_EXTERNAL : '';
        $typeCheck .= (xarModGetVar('uploads', 'dd.fileupload.trusted') == TRUE) ? ':' . _UPLOADS_GET_LOCAL : '';
        $typeCheck .= (xarModGetVar('uploads', 'dd.fileupload.upload') == TRUE) ? ':' . _UPLOADS_GET_UPLOAD : '';
        $typeCheck .= ':';
        

        if (!xarVarFetch('attach_type', $typeCheck, $action, NULL)) return;
        

        $args['action']    = $action;
        

        switch ($action) {
            case _UPLOADS_GET_UPLOAD:
                
                $file_maxsize = xarModGetVar('uploads', 'file.maxsize');
                $file_maxsize = $file_maxsize > 0 ? $file_maxsize : $this->maxSize;
                

                if (!xarVarFetch('MAX_FILE_SIZE', "int::$file_maxsize", $maxSize)) return;

                if (!xarVarFetch('', 'array:1:', $_FILES['attach_upload'])) return;

                $upload =& $_FILES['attach_upload'];
                $args['upload'] = &$_FILES['attach_upload'];
            case _UPLOADS_GET_EXTERNAL:
                // minimum external import link must be: ftp://a.ws  <-- 10 characters total

                if (!xarVarFetch('attach_external', 'regexp:/^([a-z]*).\/\/(.{7,})/', $import, '', XARVAR_NOT_REQUIRED)) return;

                $args['import'] = $import;
                break;
            case _UPLOADS_GET_LOCAL:

                if (!xarVarFetch('attach_trusted', 'list:regexp:/(?<!\.{2,2}\/)[\w\d]*/', $fileList)) return;

                $importDir = xarmodGetVar('uploads', 'path.imports-directory');
                foreach ($fileList as $file) {
                    $file = str_replace('/trusted', $importDir, $file);
                    $args['fileList']["$file"] = xarModAPIFunc('uploads', 'user', 'file_get_metadata',
                                                                array('fileLocation' => "$file"));
                    $args['fileList']["$file"]['fileSize'] = $args['fileList']["$file"]['fileSize']['long'];
                }
                break;
            case _UPLOADS_GET_STORED:

                if (!xarVarFetch('attach_stored', 'list:str:1:', $fileList)) return;

                $this->value = implode(';', $fileList);

                return true;
                break;
            case '-1':
            case 0: 
                $this->value = NULL;

                return true;
            default: 
                break;
        }

        if (!empty($action)) { 
            
            if (isset($storeType)) {
                $args['storeType'] = $storeType;
            }

            $list = xarModAPIFunc('uploads','user','process_files', $args);
            $storeList = array();
            foreach ($list as $file => $fileInfo) {
                if (!isset($fileInfo['errors'])) {
                    $storeList[] = $fileInfo['fileId'];
                } else {
                    $msg = xarML('Error Found: #(1)', $fileInfo['errors'][0]['errorMesg']);
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN_ERROR', new SystemException($msg));

                    return;
                }
            } 
            if (is_array($storeList) && count($storeList)) {
                $this->value = implode(';', $storeList);
            } else {
                $this->value = NULL;

                return false;
            }
        } else {

            return false;
        }


        return true;
    }

    function _UploadModule_showInput($value = NULL) {
            
        $trusted_dir = xarModGetVar('uploads', 'path.imports-directory');
        $descend = TRUE;

        xarModAPILoad('uploads', 'user');        
        
        $data['getAction']['LOCAL']       = _UPLOADS_GET_LOCAL;
        $data['getAction']['EXTERNAL']    = _UPLOADS_GET_EXTERNAL;
        $data['getAction']['UPLOAD']      = _UPLOADS_GET_UPLOAD;
        $data['getAction']['STORED']      = _UPLOADS_GET_STORED;
        $data['getAction']['REFRESH']     = _UPLOADS_GET_REFRESH_LOCAL;

        $data['file_maxsize'] = xarModGetVar('uploads', 'file.maxsize');;
        $data['fileList']     = xarModAPIFunc('uploads', 'user', 'import_get_filelist', 
                                               array('descend' => $descend, 'fileLocation' => $trusted_dir));
        $data['storedList']   = xarModAPIFunc('uploads', 'user', 'db_getall_files');
        

        if (!empty($value)) {
            $value = explode(';', $value);
            
            if (is_array($value)) {
                $data['inodeType']['DIRECTORY']   = _INODE_TYPE_DIRECTORY;
                $data['inodeType']['FILE']        = _INODE_TYPE_FILE;
                $Attachments = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $value));
                $data['Attachments'] = $Attachments;
                $list = $this->showOutput(array('value' => implode(';', $value)));
                
                foreach ($value as $fileId) {
                    if (isset($data['storedList'][$fileId])) {
                        $data['storedList'][$fileId]['selected'] = TRUE;
                    }
                }
            }
        }    
        
        return (isset($list) ? $list : '') . xarTplModule('uploads', 'user', 'attach_files', $data, NULL);
        
    }
    
    function _UploadModule_showOutput($value) {
        
        //echo "<br /><pre>value => "; print_r($value); echo "</pre>";
        $value = explode(';', $value);
       // echo "<br /><pre>value => "; print_r($value); echo "</pre>";  
        
        if (is_array($value) && !empty($value[0])) {
            $data['Attachments'] = xarModAPIFunc('uploads', 'user', 'db_get_file', array('fileId' => $value));    
        } else {
            $data['Attachments'] = '';
        } 
                
        return xarTplModule('dynamicdata', 'dd-output', 'attachment-list', $data, NULL);        
    }

}

?>