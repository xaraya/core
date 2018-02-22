<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

class RelativeDirectoryIterator extends DirectoryIterator
{
    public function __construct($file)
    {
        $realpath = realpath($file);
        if (!$realpath) return false;
        parent::__construct($realpath);
    }

    public function getExtension()
    {
        $filename = $this->GetFilename();
        $extension = strrpos($filename, ".", 1) + 1;
        if ($extension != false)
            return strtolower(substr($filename, $extension, strlen($filename) - $extension));
        else
            return "";
    }
}
?>