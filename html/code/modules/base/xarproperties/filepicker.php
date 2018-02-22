<?php
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.dropdown');
sys::import('xaraya.structures.relativedirectoryiterator');


/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */


/**
 * This property displays a dropdown of file names
 * 
 * Parameters:
 * basedir          base directory whose contents are displayed
 * matches          string to filter the directory contents with
 * extensions       list of valid extensions to show
 * display_fullname displax filenames with extensions
 */
class FilePickerProperty extends SelectProperty
{
    public $id         = 30052;
    public $name       = 'filepicker';
    public $desc       = 'File Picker';

    public $initialization_basedirectory;
    public $validation_file_extensions   = '';          // holds a string of comma delimited extensions
    public $validation_matches           = '';
    public $display_fullname             = false;
    
    public $file_extension_list         = array();      // holds an array of filename extensions
    public $file_extension_regex        = '';           // holds a string of type 'jpg|gif|png'

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/base/xarproperties';
        // keep things relative here if possible (cfr. basedir vs. baseurl issue for images et al.)
        if (empty($this->initialization_basedirectory)) {
            $this->initialization_basedirectory = 'var';
        } else {
            // Cater to common Xaraya calls
            if ((strpos($this->initialization_basedirectory,'sys') === 0) || (strpos($this->initialization_basedirectory,'xar') === 0)) {
                eval('$temp='.$this->initialization_basedirectory.";"); 
                $this->initialization_basedirectory = $temp;
            }
        }
        // Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory
        $this->initialization_basedirectory = $this->getThemeDir();

        $this->setExtensions();
    }

    /**
     * Replace {theme}, {user_theme}, {admin_theme} with the appropriate theme directory - move to templates/themes?
     * 
     * @param  string basedir Base directory to be replaced
     * @return string         Corresponding theme directory
     */
    public function getThemeDir($basedir = null)
    {
        if (!$basedir) $basedir = $this->initialization_basedirectory;
        if (strpos($basedir, '{user_theme}') !== false) {
            $basedir = str_replace('{user_theme}',"themes/".xarModVars::get('themes', 'default_theme'),$basedir);
        }
        if (strpos($basedir, '{admin_theme}') !== false) {
            $basedir = str_replace('{admin_theme}',"themes/".xarModVars::get('themes', 'admin_theme'),$basedir);
        }
        if (strpos($basedir, '{theme}') !== false) {
            $basedir = str_replace('{theme}',xarTpl::getThemeDir(),$basedir);
        }
        return $basedir;
    }

/**
 * Display a Dropdown for input
 * 
 * @param  array data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
    public function showInput(Array $data = array())
    {
        if (isset($data['basedir'])) $this->initialization_basedirectory = $this->getThemeDir($data['basedir']);

        if (isset($data['matches'])) $this->validation_matches = $data['matches'];
        if (isset($data['extensions'])) $this->setExtensions($data['extensions']);
        if (isset($data['display_fullname'])) $this->display_fullname = $data['display_fullname'];
        if (isset($data['firstline']))  $this->initialization_firstline = $data['firstline'];
        return parent::showInput($data);
    }
/**
 * Validate the file
 *
 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
 */
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
        xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
        $this->value = null;
        return false;
    }
    /**
     * Retrieve the list of options on demand
     * 
     * @param void N/A
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        $options = array();
        if (empty($this->initialization_basedirectory)) return array();
        // this works with relative directories
        $dir = new RelativeDirectoryIterator($this->initialization_basedirectory);
        if ($dir == false) return array();
        
        for($dir->rewind();$dir->valid();$dir->next()) {
            if($dir->isDir()) continue; // no dirs
            if(!$this->validateExtension($dir->getExtension())) continue;
            if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            $name = $dir->getFileName();
            $id = $name;
            if (!$this->display_fullname) $name = substr($name, 0, strlen($name) - strlen($dir->getExtension()) - 1);
            if(!empty($this->validation_matches) && (strpos($this->validation_matches,$name) === false)) continue;
            $options[$name] = array('id' => $id, 'name' => $name);
        }
        ksort($options);

        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }
        return $options;
    }

    /**
     * Set the list/regex of allowed file extensions, depending on the syntax used (cfr. image, webpage, ...)
     * 
     * @param string|string[] $file_extensions String or array of file extensions.
     *                                      If a string is used, multiple file extensions shall be
     *                                      separated by "," or valid regular expression
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
     * This method can take an extension or a full file name
     * 
     * @param string $filename Extension or full file name
     * @return boolean
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