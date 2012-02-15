<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
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
    public $imagealt   = 'Image';

    public $initialization_image_source  = 'url';
    public $initialization_basedirectory = 'var/uploads';
    public $validation_file_extensions   = 'gif,jpg,jpeg,png,bmp';
    public $validation_file_extensions_invalid;    // TODO: not yet implemented

    // this is used by DataPropertyMaster::addProperty() to set the $object->upload flag
    public $upload = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'image';

        // Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory
        $this->initialization_basedirectory = preg_replace('/\{user_theme\}/',"themes/".xarModVars::get('themes', 'default_theme'),$this->initialization_basedirectory);
        $this->initialization_basedirectory = preg_replace('/\{admin_theme\}/',"themes/".xarModVars::get('themes', 'admin_theme'),$this->initialization_basedirectory);
        $this->initialization_basedirectory = preg_replace('/\{theme\}/',xarTpl::getThemeDir(),$this->initialization_basedirectory);
        // FIXME: baseurl is no longer initialized - could be different from basedir !

        if ($this->initialization_image_source == 'upload') $this->upload = true;
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // make sure we check the right image_source when dealing with several image properties
        if (isset($this->fieldname)) $name = $this->fieldname;
        else $name = 'dd_'.$this->id;
        $sourcename = $name . '_source';
        if (!xarVarFetch($sourcename, 'str:1:100', $image_source, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!empty($image_source)) $this->initialization_image_source = $image_source;

        if ($this->initialization_image_source == 'url') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'url'));
            $prop->validateValue($value);
            $this->value = $prop->value;
        } elseif ($this->initialization_image_source == 'upload') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'fileupload'));
            $prop->initialization_basedirectory = $this->initialization_basedirectory;
            $prop->setExtensions($this->validation_file_extensions);
            $prop->fieldname = $this->fieldname;
            $prop->validateValue($value);
            $this->value = $prop->value;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        // CHECKME: why not use image_source as attribute instead of inputtype ?
        $data['image_source'] = isset($data['inputtype']) ? $data['inputtype'] : $this->initialization_image_source;
        if ($data['image_source'] == 'upload') $this->upload = true;
        $data['basedirectory'] = isset($data['basedir']) ? $data['basedir'] : $this->initialization_basedirectory;
        $data['extensions'] = isset($data['extensions']) ? $data['extensions'] : $this->validation_file_extensions;
        $data['value']    = isset($data['value']) ? xarVarPrepForDisplay($data['value']) : xarVarPrepForDisplay($this->value);

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if(!empty($data['inputtype'])) $this->initialization_image_source = $data['inputtype'];
        if(!empty($data['basedir'])) $this->initialization_basedirectory = $data['basedir'];
        if (empty($data['value'])) $data['value'] = $this->value;
        if (!empty($data['value'])) {
            // FIXME: baseurl is no longer initialized - could be different from basedir !
            if (($this->initialization_image_source == 'local') || ($this->initialization_image_source == 'upload')) {
                $data['value'] = $this->initialization_basedirectory . "/" . $data['value'];
            }
        }
        if (empty($data['imagetext'])) $data['imagetext'] = $this->imagetext;
        if (empty($data['imagealt'])) $data['imagealt'] = $this->imagealt;

        return parent::showOutput($data);
    }

}
?>
