<?php
/**
 * Dynamic Image List Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * Include the base class
 *
 */
include_once "includes/properties/Dynamic_Select_Property.php";

/**
 * Handle the imagelist property
 *
 * @package dynamicdata
 */
class Dynamic_ImageList_Property extends Dynamic_Select_Property
{
    var $basedir;
    var $filetype = '(gif|jpg|jpeg|png|bmp)';

    function Dynamic_ImageList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        // specify base directory in validation field
        if (empty($this->basedir) && !empty($this->validation)) {
            $this->basedir = $this->validation;
        }
        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->basedir) && preg_match('/\{theme\}/',$this->basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->basedir = preg_replace('/\{theme\}/',$curtheme,$this->basedir);
        }
        if (count($this->options) == 0 && !empty($this->basedir)) {
            $files = xarModAPIFunc('dynamicdata','admin','browse',
                                   array('basedir' => $this->basedir,
                                         'filetype' => $this->filetype));
            if (!isset($files)) {
                $files = array();
            }
            natsort($files);
            array_unshift($files,'');
            foreach ($files as $file) {
                $this->options[] = array('id' => $file,
                                         'name' => $file);
            }
            unset($files);
        }
    }

    // default showInput() from Dynamic_Select_Property

    function showOutput($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->basedir;
        $filetype = $this->filetype;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
        // TODO: make sure basedir and baseurl match
            return '<img src="'.$basedir.'/'.$value.'" />';
        } else {
            return '';
        }
    }

}

?>