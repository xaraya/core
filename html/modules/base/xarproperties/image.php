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

    public $imagetext  = 'no image';

    public $initialization_image_source  = 'url';
    public $initialization_basedirectory  = 'var/uploads';
    public $validation_file_extensions  = 'gif,jpg,jpeg,png,bmp';
    public $validation_file_extensions_invalid;    // TODO: not yet implemented

    // this is used by DataPropertyMaster::addProperty() to set the $object->upload flag
    public $upload = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'image';

        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->initialization_basedirectory) && preg_match('/\{theme\}/',$this->initialization_basedirectory)) {
            $curtheme = xarTplGetThemeDir();
            $this->initialization_basedirectory = preg_replace('/\{theme\}/',$curtheme,$this->initialization_basedirectory);
        }
        if ($this->initialization_image_source == 'upload') $this->upload = true;
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if ($this->initialization_image_source == 'url') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'url'));
            $prop->validateValue($value);
            $this->value = $prop->value;
        } elseif ($this->initialization_image_source == 'upload') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'fileupload'));
            $prop->initialization_basedirectory = $this->initialization_basedirectory;
            $prop->filetype= str_replace (',','|',$this->validation_file_extensions);
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
        $data['inputtype'] = isset($data['inputtype']) ? $data['inputtype'] : $this->initialization_image_source;
        if ($data['inputtype'] == 'upload') $this->upload = true;
        $data['basedir'] = isset($data['basedir']) ? $data['basedir'] : $this->initialization_basedirectory;
        $data['extensions'] = isset($data['extensions']) ? $data['extensions'] : $this->validation_file_extensions;
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        $data['value'] = isset($data['value']) ? $data['value'] : $this->value;
        if (($this->initialization_image_source == 'local') || ($this->initialization_image_source == 'upload')) {
            $data['value'] = $this->initialization_basedirectory . "/" . $data['value'];
        }
        $data['imagetext'] = $this->imagetext;

        return parent::showOutput($data);
    }

}
?>
