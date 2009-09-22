<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.filepicker');
/**
 * Handle the imagelist property
 * @package dynamicdata
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

        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/default/images
        if (!empty($this->initialization_basedirectory) && preg_match('/\{theme\}/',$this->initialization_basedirectory)) {
            $curtheme = xarTplGetThemeDir();
            $this->initialization_basedirectory = preg_replace('/\{theme\}/',$curtheme,$this->initialization_basedirectory);
            // FIXME: baseurl is no longer initialized - could be different from basedir !
            if (isset($this->baseurl)) {
                $this->baseurl = preg_replace('/\{theme\}/',$curtheme,$this->baseurl);
            }
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
