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
    var $maxsize = 1000000;
    var $basedir;
    var $filetype;

    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    var $upload = true;

    function Dynamic_FileUpload_Property($args)
    {
        $this->Dynamic_Property($args);
        // specify base directory and optional file types in validation field - e.g. this/dir or this/dir;(gif|jpg|png|bmp)
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
        $upname = $name .'_upload';
        $filetype = $this->filetype;
        if (!empty($_FILES) && !empty($_FILES[$upname]) && !empty($_FILES[$upname]['tmp_name'])
            // is_uploaded_file() : PHP 4 >= 4.0.3
            && is_uploaded_file($_FILES[$upname]['tmp_name']) && $_FILES[$upname]['size'] > 0 && $_FILES[$upname]['size'] < 1000000) {

            // if the uploads module is hooked (to be verified and set by the calling module)
            if (xarVarGetCached('Hooks.uploads','ishooked')) {
                $magicLink = xarModAPIFunc('uploads',
                                           'user',
                                           'uploadmagic',
                                           array('uploadfile'=>$upname,
                                                 'mod'=>'dynamicdata',
                                                 'modid'=>0,
                                                 'utype'=>'file'));
                if (!empty($value)) {
                    $value .= ' ' . $magicLink;
                } else {
                    $value = $magicLink;
                }
                $this->value = $value;
            } elseif (!empty($_FILES[$upname]['name'])) {
                $file = xarVarPrepForOS(basename($_FILES[$upname]['name']));
                if (!empty($filetype) && !preg_match("/\.$filetype$/",$file)) {
                    $this->invalid = xarML('file type');
                    $this->value = null;
                    return false;
                } elseif (!move_uploaded_file($_FILES[$upname]['tmp_name'],$this->basedir . '/'. $file)) {
                    $this->invalid = xarML('file upload failed');
                    $this->value = null;
                    return false;
                }
                $this->value = $file;
            } else {
            // TODO: assign random name + figure out mime type to add the right extension ?
                $this->invalid = xarML('file name for upload');
                $this->value = null;
                return false;
            }
        } elseif (!empty($value)) {
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (xarVarGetCached('Hooks.uploads','ishooked') && preg_match("/#ulid\:\d+#/",$value)) {
                // nothing wrong here...
            } elseif (!empty($filetype) && !preg_match("/\.$filetype$/",$value)) {
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

//    function showInput($name = '', $value = null, $size = 0, $maxsize = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        $upname = $name .'_upload';

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVarSetCached('Hooks.dynamicdata','withupload',1);

        if (xarVarGetCached('Hooks.uploads','ishooked')) {
            $extensions = xarModGetVar('uploads','allowed_types');
            if (!empty($extensions)) {
                $allowed = '<br />' . xarML('Allowed extensions : #(1)',$extensions);
            } else {
                $allowed = '';
            }
        } else {
            if (!empty($this->filetype)) {
                $extensions = $this->filetype;
                $allowed = '<br />' . xarML('Allowed file types : #(1)',$extensions);
            } else {
                $allowed = '';
            }
        }

        // we're using a hidden field to keep track of any previously uploaded file here
        return (!empty($value) ? xarML('Uploaded file : #(1)',$value) . '<br /><input type="hidden" name="'.$name.'" value="'.$value.'" />' : '') .
               '<input type="hidden" name="MAX_FILE_SIZE"'.
               ' value="'. (!empty($maxsize) ? $maxsize : $this->maxsize) .'" />' .
               '<input type="file"'.
               ' name="'.$upname.'"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               (!empty($id) ? ' id="'.$id.'_upload"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' /> ' . $allowed .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            // if the uploads module is hooked (to be verified and set by the calling module)
            if (xarVarGetCached('Hooks.uploads','ishooked') && preg_match("/#ulid\:\d+#/",$value)) {
                return xarVarPrepForDisplay($value); // we'll let the transform hook handle the conversion
            } elseif (!empty($this->basedir) && file_exists($this->basedir . '/'. $value) && is_file($this->basedir . '/'. $value)) {
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

}

?>
