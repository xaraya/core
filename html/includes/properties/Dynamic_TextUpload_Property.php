<?php
/**
 * Dynamic Text Upload Property (TODO: work with uploads module)
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Handle text upload property
 *
 * @package dynamicdata
 *
 */
class Dynamic_TextUpload_Property extends Dynamic_Property
{
    var $rows = 8;
    var $cols = 50;
    var $wrap = 'soft';

    var $size = 40;
    var $maxsize = 1000000;
//    var $basedir;
//    var $filetype;

    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    var $upload = true;

    function Dynamic_TextUpload_Property($args)
    {
        $this->Dynamic_Property($args);
        // TODO: do we want any other verifications here, like file type ?
    }

    function validateValue($value = null)
    {
        // the variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a textarea field to keep track of any previously uploaded file here
        if (!isset($value)) {
            $value = $this->value;
        }
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }
        $upname = $name .'_upload';
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
            } else {
                // this doesn't work on some configurations
                //$this->value = join('', @file($_FILES[$upname]['tmp_name']));
                $tmpdir = xarCoreGetVarDirPath();
                $tmpdir .= '/cache/templates';
                $tmpfile = tempnam($tmpdir, 'dd');
                if (move_uploaded_file($_FILES[$upname]['tmp_name'], $tmpfile) && file_exists($tmpfile)) {
                    $this->value = join('', file($tmpfile));
                    unlink($tmpfile);
                }
            }
        } elseif (!empty($value)) {
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

        // we're using a textarea field to keep track of any previously uploaded file here
        return '<textarea' .
               ' name="' . $name . '"' .
               ' rows="'. (!empty($rows) ? $rows : $this->rows) . '"' .
               ' cols="'. (!empty($cols) ? $cols : $this->cols) . '"' .
               ' wrap="'. (!empty($wrap) ? $wrap : $this->wrap) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               '>' . xarVarPrepForDisplay($value) . '</textarea><br /><br />' .
               '<input type="hidden" name="MAX_FILE_SIZE"'.
               ' value="'. (!empty($maxsize) ? $maxsize : $this->maxsize) .'" />' .
               '<input type="file"'.
               ' name="'.$upname.'"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               (!empty($id) ? ' id="'.$id.'_upload"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return '';
        }
    }

}

?>
