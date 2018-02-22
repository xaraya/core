<?php
/**
 * Include the base class
 */
 sys::import('modules.base.xarproperties.filepicker');
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
 * This property displays a dropdown of available image icons
 */
class ImageListProperty extends FilePickerProperty
{
    public $id         = 35;
    public $name       = 'imagelist';
    public $desc       = 'Image List';

    public $imagetext  = 'no image';
    public $imagealt   = 'Image';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template = 'imagelist';

        if (empty($this->validation_file_extensions)) $this->setExtensions('gif,jpg,jpeg,png,bmp');

        // Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory - already done in parent
        //$this->initialization_basedirectory = $this->getThemeDir();

        // FIXME: baseurl is no longer initialized - could be different from basedir !
        if (isset($this->baseurl)) {
            $this->baseurl = $this->getThemeDir($this->baseurl);
        }
        
        // Default selection
        if (!isset($this->initialization_firstline)) $this->initialization_firstline = ',' . xarML('Select Image');
    }
/**
 * Display the output 
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for output on a web page
 */	
    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        $basedir = $this->initialization_basedirectory;

        // FIXME: baseurl is no longer initialized - could be different from basedir !

        if (!empty($value)) {
            $srcpath = $basedir.'/'.$value;
        } else {
            $srcpath = '';
        }

        $data['value']    = $value;
        $data['basedir']  = $basedir;
        $data['srcpath']  = $srcpath;

        if (empty($data['imagetext'])) $data['imagetext'] = $this->imagetext;
        if (empty($data['imagealt'])) $data['imagealt'] = $this->imagealt;

        return parent::showOutput($data);
    }
}

?>