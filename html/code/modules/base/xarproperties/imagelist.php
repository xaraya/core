<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.filepicker');
/**
 * Handle the imagelist property
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

        // Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory
        $this->initialization_basedirectory = preg_replace('/\{user_theme\}/',"themes/".xarModVars::get('themes', 'default_theme'),$this->initialization_basedirectory);
        $this->initialization_basedirectory = preg_replace('/\{admin_theme\}/',"themes/".xarModVars::get('themes', 'admin_theme'),$this->initialization_basedirectory);
        $this->initialization_basedirectory = preg_replace('/\{theme\}/',xarTpl::getThemeDir(),$this->initialization_basedirectory);

        // FIXME: baseurl is no longer initialized - could be different from basedir !
        if (isset($this->baseurl)) {
            $this->baseurl = preg_replace('/\{user_theme\}/',"themes/".xarModVars::get('themes', 'default_theme'),$this->baseurl);
            $this->baseurl = preg_replace('/\{admin_theme\}/',"themes/".xarModVars::get('themes', 'admin_theme'),$this->baseurl);
            $this->baseurl = preg_replace('/\{theme\}/',xarTpl::getThemeDir(),$this->baseurl);
        }
        
        // Default selection
        if (!isset($this->initialization_firstline)) $this->initialization_firstline = ',' . xarML('Select Image');
    }

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
