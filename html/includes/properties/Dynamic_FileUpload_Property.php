<?php
/**
 * Dynamic File Upload Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_FileUpload_Property extends Dynamic_Property
{
    var $size = 40;
    var $maxsize = 1000000;

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
        // FIXME : xarVarCleanFromInput() with magic_quotes_gpc On clashes with
        //         the tmp_name assigned by PHP on Windows !!!
            global $HTTP_POST_FILES;
            $file = $HTTP_POST_FILES['dd_'.$this->id];
            // is_uploaded_file() : PHP 4 >= 4.0.3
            if (is_uploaded_file($file['tmp_name']) && $file['size'] < $this->maxsize) {
                $this->value = join('', @file($file['tmp_name']));
            } else {
                $this->invalid = xarML('file upload');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $size = 0, $maxsize = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        return '<input type="hidden" name="MAX_FILE_SIZE"'.
               ' value="'. (!empty($maxsize) ? $maxsize : $this->maxsize) .'" />' .
               '<input type="file"'.
               ' name="' . (!empty($name) ? $name : 'dd_'.$this->id) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               (!empty($id) ? ' id="'.$id.'"' : '') .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
    }

    function showOutput($value = null)
    {
    // TODO: link to download file ?
        return '';
    }

}

?>