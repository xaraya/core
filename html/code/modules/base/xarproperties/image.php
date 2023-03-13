<?php
/**
 * Include the base class
 */
 sys::import('modules.base.xarproperties.textbox');
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * This property displays an image
 */
class ImageProperty extends TextBoxProperty
{
    public $id         = 12;
    public $name       = 'image';
    public $desc       = 'Image';

    public $imagetext  = 'No image';
    public $imagealt   = 'Image';

    public $initialization_image_source  = 'url';
    public $initialization_basedirectory;
    public $validation_file_extensions   = 'gif,jpg,jpeg,png,bmp';
    public $validation_file_extensions_invalid;    // TODO: not yet implemented

    // this is used by DataPropertyMaster::addProperty() to set the $object->upload flag
    public $upload = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'image';

        // Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory
        $this->initialization_basedirectory = $this->getThemeDir();
        // FIXME: baseurl is no longer initialized - could be different from basedir !

    	$this->initialization_basedirectory = sys::varpath() . '/uploads';
        if ($this->initialization_image_source == 'upload') $this->upload = true;
    }

    /**
     * Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory - move to templates/themes?
     * 
     * @param  string basedir Base directory to be replaced
     * @return string         Corresponding theme directory
     */
    public function getThemeDir($basedir = '')
    {
        if (empty($basedir)) $basedir = $this->initialization_basedirectory;
        if (strpos($basedir ?? '', '{user_theme}') !== false) {
            $basedir = str_replace('{user_theme}',"themes/".xarModVars::get('themes', 'default_theme'),$basedir);
        }
        if (strpos($basedir ?? '', '{admin_theme}') !== false) {
            $basedir = str_replace('{admin_theme}',"themes/".xarModVars::get('themes', 'admin_theme'),$basedir);
        }
        if (strpos($basedir ?? '', '{theme}') !== false) {
            $basedir = str_replace('{theme}',xarTpl::getThemeDir(),$basedir);
        }
        return $basedir;
    }

	/**
	 * Validate the value of a field
	 *
	 * @return bool|void Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // make sure we check the right image_source when dealing with several image properties
        if (isset($this->fieldname)) $name = $this->fieldname;
        else $name = 'dd_'.$this->id;
        $sourcename = $name . '_source';
        if (!xarVar::fetch($sourcename, 'str:1:100', $image_source, NULL, xarVar::NOT_REQUIRED)) return;
        if (!empty($image_source)) $this->initialization_image_source = $image_source;

        if ($this->initialization_image_source == 'url') {
            $prop = DataPropertyMaster::getProperty(array('type' => 'url'));
            $prop->validateValue($value);
            $this->value = $prop->value;
        } elseif ($this->initialization_image_source == 'upload') {
            /** @var FileUploadProperty $prop */
            $prop = DataPropertyMaster::getProperty(array('type' => 'fileupload'));
            $prop->initialization_basedirectory = $this->initialization_basedirectory;
            $prop->setExtensions($this->validation_file_extensions);
            $prop->fieldname = $this->fieldname;
            $prop->validateValue($value);
            $this->value = $prop->value;
        }
        return true;
    }

	/**
	 * Display the property for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        // CHECKME: why not use image_source as attribute instead of inputtype ?
        $data['image_source'] = isset($data['inputtype']) ? $data['inputtype'] : $this->initialization_image_source;
        if ($data['image_source'] == 'upload') $this->upload = true;
        $data['basedirectory'] = isset($data['basedir']) ? $data['basedir'] : $this->initialization_basedirectory;
        $data['extensions'] = isset($data['extensions']) ? $data['extensions'] : $this->validation_file_extensions;
        $data['value']    = isset($data['value']) ? xarVar::prepForDisplay($data['value']) : xarVar::prepForDisplay($this->value);

        return parent::showInput($data);
    }

	/**
	 * Display the property for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */	
    public function showOutput(Array $data = array())
    {
        if(!empty($data['inputtype'])) $this->initialization_image_source = $data['inputtype'];
        if(!empty($data['basedir'])) $this->initialization_basedirectory = $this->getThemeDir($data['basedir']);
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
