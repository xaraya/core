<?php
/**
 * Dynamic HTML Page Property
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
 * Class to handle dynamic html page property
 *
 * @package dynamicdata
 */
class Dynamic_HTMLPage_Property extends Dynamic_Select_Property
{
    var $basedir;
    var $filetype = 'html?';

    function Dynamic_HTMLPage_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        // specify base directory in validation field
        if (empty($this->basedir) && !empty($this->validation)) {
            $this->basedir = $this->validation;
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
            return join('', @file($basedir.'/'.$value));
        } else {
        //    return xarVarPrepForDisplay($value);
            return '';
        }
    }

}

?>