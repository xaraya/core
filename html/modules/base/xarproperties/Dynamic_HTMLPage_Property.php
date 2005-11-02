<?php
/**
 * HTML Page property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/

include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle dynamic html page property
 *
 * @package dynamicdata
 */
class Dynamic_HTMLPage_Property extends Dynamic_Select_Property
{
    var $basedir = '';
    var $filetype = '((xml)|(html))?';

    function Dynamic_HTMLPage_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        // specify base directory in validation field
        if (empty($this->basedir) && !empty($this->validation)) {
            // Hack for passing this thing into transform hooks 
            // validation may start with 'transform:' and we
            // obviously dont want that in basedir
            if(substr($this->validation,0,10) == 'transform:') {
                $basedir = substr($this->validation,10,strlen($this->validation)-10);
            } else {
                $basedir = $this->validation;
            }
            $this->basedir = $basedir;
        }
    }

    function validateValue($value = null)
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
            $this->value = $value;
            return true;
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->getOptions();
        }
        if (count($options) == 0 && !empty($this->basedir)) {
            $files = xarModAPIFunc('dynamicdata','admin','browse',
                                   array('basedir' => $this->basedir,
                                         'filetype' => $this->filetype));
            if (!isset($files)) {
                $files = array();
            }
            natsort($files);
            array_unshift($files,'');
            foreach ($files as $file) {
                $options[] = array('id' => $file,
                                   'name' => $file);
            }
            unset($files);
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['name']    = $name;
        $data['value']    = $value;        
        $data['id']      = $id;
        $data['options'] = $options;
        $data['tabindex']= !empty($tabindex) ? $tabindex : 0;
        $data['invalid'] = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        $template="";
        return xarTplProperty('base', 'webpage', 'showinput', $data);

    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

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
            $srcpath = join('', @file($basedir.'/'.$value));

        } else {
        //    return xarVarPrepForDisplay($value);
            $srcpath='';
            //return '';
        }
        $data['value']=$value;
        $data['basedir']=$basedir;
        $data['filetype']=$filetype;
        $data['srcpath']=$srcpath;

        $template="";
        return xarTplProperty('base', 'webpage', 'showoutput', $data);

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
                              'id'         => 13,
                              'name'       => 'webpage',
                              'label'      => 'HTML Page',
                              'format'     => '13',
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
