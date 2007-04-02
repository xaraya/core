<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.textbox');
/**
 * Handle the image property
 */
class ImageProperty extends TextBoxProperty
{
    public $id         = 12;
    public $name       = 'image';
    public $desc       = 'Image';

    public $inputtype  = 'url';
    public $basedir    = 'var/uploads';
    public $extensions    = 'gif,jpg,jpeg,png,bmp';

    // this is used by DataPropertyMaster::addProperty() to set the $object->upload flag
    public $upload = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'image';
        $this->parseValidation($this->validation);
        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->basedir) && preg_match('/\{theme\}/',$this->basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->basedir = preg_replace('/\{theme\}/',$curtheme,$this->basedir);
        }
        if ($this->inputtype == 'upload') $this->upload = true;
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) $value = $this->value;
        if ($this->inputtype == 'url') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'url'));
            $prop->validateValue($value);
            $this->value = $prop->value;
        } elseif ($this->inputtype == 'upload') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'fileupload'));
            $prop->fieldname = $this->fieldname;
            $prop->validateValue($value);
            $this->value = $prop->value;
        } else {
            $this->value = $value;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        $data['inputtype'] = isset($data['inputtype']) ? $data['inputtype'] : $this->inputtype;
        if ($data['inputtype'] == 'upload') $this->upload = true;
        $data['basedir'] = isset($data['basedir']) ? $data['basedir'] : $this->basedir;
        $data['extensions'] = isset($data['extensions']) ? $data['extensions'] : $this->extensions;
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        $data['value'] = isset($data['value']) ? $data['value'] : $this->value;
        if (($this->inputtype == 'local') || ($this->inputtype == 'upload')) {
            $data['value'] = $this->basedir . "/" . $data['value'];
        }

        return parent::showOutput($data);
    }

    public function parseValidation($validation = '')
    {
        if (!empty($validation)) {
            try  {
                $this->validation = unserialize($validation);
            } catch(Exception $e) {}
        }
        if (isset($this->validation['inputtype'])) $this->inputtype = $this->validation['inputtype'];
        if (isset($this->validation['basedir'])) $this->basedir = $this->validation['basedir'];
        if (isset($this->validation['extensions'])) $this->extensions = $this->validation['extensions'];
    }

    public function showValidation(Array $data = array())
    {
        extract($data);
        if (isset($validation)) $this->parseValidation($validation);
        $data['inputtype'] = isset($data['inputtype']) ? $data['inputtype'] : $this->inputtype;
        $data['basedir'] = isset($data['basedir']) ? $data['basedir'] : $this->basedir;
        $data['extensions'] = isset($data['extensions']) ? $data['extensions'] : $this->extensions;
        return xarTplProperty('base', $this->template, 'validation', $data);
    }

    public function updateValidation(Array $data = array())
     {
        extract($data);
        // do something with the validation and save it in $this->validation
        if (isset($validation['inputtype'])) $this->validation['inputtype'] = $validation['inputtype'];
        if (isset($validation['basedir'])) $this->validation['basedir'] = $validation['basedir'];
        if (isset($validation['extensions'])) $this->validation['extensions'] = $validation['extensions'];
        $this->validation = serialize($this->validation);
        return true;
     }
}
?>
