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

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        if (empty($this->validation_file_extensions)) $this->validation_file_extensions = 'gif,jpg,jpeg,png,bmp';

        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->initialization_basedirectory) && preg_match('/\{theme\}/',$this->initialization_basedirectory)) {
            $curtheme = xarTplGetThemeDir();
            $this->initialization_basedirectory = preg_replace('/\{theme\}/',$curtheme,$this->initialization_basedirectory);
            if (isset($this->baseurl)) {
                $this->baseurl = preg_replace('/\{theme\}/',$curtheme,$this->baseurl);
            }
        }
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        $basedir = $this->initialization_basedirectory;
        $filetype = $this->filetype;

        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.\-\040]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $srcpath=$basedir.'/'.$value;
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
