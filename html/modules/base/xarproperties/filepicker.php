<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage math
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

    public $basedir;
    public $extensions = array('');
    public $matches   = '';
    public $fullname   = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/base/xarproperties';
        $this->basedir = realpath(xarServerGetBaseURL());
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['basedir'])) $this->basedir = $data['basedir'];
        if (isset($data['matches'])) $this->matches = $data['matches'];
        if (isset($data['extensions'])) {
            if (!is_array($data['extensions'])) $this->extensions = explode(',',$data['extensions']);
            else $this->extensions = array($data['extensions']);
        }
        return parent::showInput($data);
    }

    function getOptions()
    {
        if (empty($this->basedir)) return array();
        $dir = new RelativeDirectoryIterator($this->basedir);

        for($dir->rewind();$dir->valid();$dir->next()) {
            if($dir->isDir()) continue; // no dirs
            if(!in_array($dir->getExtension(),$this->extensions)) continue;
            if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            $name = $dir->getFileName() . "." . $dir->getExtension();
            if (!$this->fullname) $name = substr($name, 0, strlen($name) - strlen($dir->getExtension()) - 1);
            if(!empty($this->matches) && (strpos($name,$this->matches) === false)) continue;
            $this->options[] = array('id' => $name, 'name' => $name);
        }
        return $this->options;
    }
}
?>
