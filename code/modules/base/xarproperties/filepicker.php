<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.base.xarproperties.dropdown');
sys::import('xaraya.structures.relativedirectoryiterator');

/**
 * Handle file picker property
 */
class FilePickerProperty extends SelectProperty
{
    public $id         = 30052;
    public $name       = 'filepicker';
    public $desc       = 'File Picker';

    public $initialization_basedirectory;
    public $validation_file_extensions   = '';
    public $validation_matches           = '';
    public $display_fullname             = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/base/xarproperties';
        // keep things relative here if possible (cfr. basedir vs. baseurl issue for images et al.)
        if (empty($this->initialization_basedirectory)) $this->initialization_basedirectory = 'var';
        $this->setExtensions();
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['basedir'])) $this->initialization_basedirectory = $data['basedir'];
        if (isset($data['matches'])) $this->validation_matches = $data['matches'];
        if (isset($data['extensions'])) $this->setExtensions($data['extensions']);
        if (isset($data['display_fullname'])) $this->display_fullname = $data['display_fullname'];
        if (isset($data['firstline']))  $this->initialization_firstline = $data['firstline'];
        return parent::showInput($data);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // use the real path here for file checking
        $filepath = realpath($this->initialization_basedirectory.'/'.$value);
        if (!empty($value) &&
            //slight change to allow spaces
            preg_match('/^[a-zA-Z0-9_\/.\-\040]+$/',$value) &&
            $this->validateExtension($value) &&
            file_exists($filepath) &&
            is_file($filepath)) {
            return true;
        } elseif (empty($value)) {
            return true;
        }
        $this->invalid = xarML('incorrect selection: #(1) for #(2)', $value, $this->name);
        $this->value = null;
        return false;
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }
        
        if (empty($this->initialization_basedirectory)) return array();
        // this works with relative directories
        $dir = new RelativeDirectoryIterator($this->initialization_basedirectory);

        for($dir->rewind();$dir->valid();$dir->next()) {
            if($dir->isDir()) continue; // no dirs
            if(!$this->validateExtension($dir->getExtension())) continue;
            if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            $name = $dir->getFileName();
            $id = $name;
            if (!$this->display_fullname) $name = substr($name, 0, strlen($name) - strlen($dir->getExtension()) - 1);
            if(!empty($this->validation_matches) && (strpos($this->validation_matches,$name) === false)) continue;
            $options[] = array('id' => $id, 'name' => $name);
        }
        return $options;
    }

    /**
     * Set the list/regex of allowed file extensions, depending on the syntax used (cfr. image, webpage, ...)
     */
    public function setExtensions($file_extensions = null)
    {
        if (isset($file_extensions)) {
            $this->validation_file_extensions = $file_extensions;
        }
        $this->file_extension_list = null;
        $this->file_extension_regex = null;
        if (!empty($this->validation_file_extensions)) {
            // example: array('gif', 'jpg', 'jpeg', ...)
            if (is_array($this->validation_file_extensions)) {
                $this->file_extension_list = $this->validation_file_extensions;

            // example: gif,jpg,jpeg,png,bmp,txt,htm,html
            } elseif (strpos($this->validation_file_extensions, ',') !== false) {
                $this->file_extension_list = explode(',', $this->validation_file_extensions);

            // example: gif|jpe?g|png|bmp|txt|html?
            } else {
                $this->file_extension_regex = $this->validation_file_extensions;
            }
        }
    }

    /**
     * Validate the given filename against the list/regex of allowed file extensions
     */
    public function validateExtension($filename = '')
    {
        $pos = strrpos($filename, '.');
        if ($pos !== false) {
            $extension = substr($filename, $pos + 1);
        } else {
            // in case we already got the extension from $dir->getExtension()
            $extension = $filename;
        }

        if (!empty($this->file_extension_list) &&
            !in_array($extension, $this->file_extension_list)) {
            return false;
        }
        if (!empty($this->file_extension_regex) &&
            !preg_match('/^' . $this->file_extension_regex . '$/', $extension)) {
            return false;
        }
        return true;
    }
}
?>
