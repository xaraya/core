<?php
/**
 * HTML Page property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
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
    public $basedir  = '';
    public $filetype = '((xml)|(html))?';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template = 'webpage';
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

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 13;
        $info->name = 'webpage';
        $info->desc = 'HTML Page';

        return $info;
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

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }
        if (count($data['options']) == 0 && !empty($this->basedir)) {
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

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        
        $basedir = $this->basedir;
        $filetype = $this->filetype;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $srcpath = join('', @file($basedir.'/'.$value));
        } else {
            $srcpath='';
        }
        $data['value']    = $value;
        $data['basedir']  = $basedir;
        $data['filetype'] = $filetype;
        $data['srcpath']  = $srcpath;
        return parent::showOutput($data);
    }
}
?>
