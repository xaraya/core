<?php
/**
 * @package core
 * @package templating
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * 
 */

/**
 * XarayaCompiler - the abstraction of the BL compiler
 *
 * @access public
 */
sys::import('blocklayout.compiler');

class XarayaCompiler extends xarBLCompiler
{    
    public static function &instance()
    {
        if(self::$instance == null) {
            self::$instance = new XarayaCompiler();
        }
        return self::$instance;
    }

    public function configure()
    {
        // Get the Xaraya tags
        $baseDir = sys::lib() . 'xaraya/templating/tags';
        $baseDir = realpath($baseDir);
        if (strpos($baseDir, '\\') != false) {
            // On Windows, drive letters are preceeded by an extra / [file:///C:/...]
            $baseURI = 'file:///' . str_replace('\\','/',$baseDir);
        } else {
            $baseURI = 'file://' . $baseDir;
        }
        $xslFiles = $this->getTagPaths($baseDir, $baseURI);
        // Add the custom tags from modules
        $xslFiles = array_merge($xslFiles,$this->getModuleTagPaths());
        return $xslFiles;
    }

    /**
     * Private methods
     */
    private function getModuleTagPaths()
    {
        if (function_exists('xarModAPIFunc')) {
            $activeMods = xarModAPIFunc('modules','admin','getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        } else {
            return array();
        }
        assert('!empty($activeMods)'); // this should never happen

        $files = array();
        foreach($activeMods as $modInfo) {
            $filepath = sys::code() . 'modules/' .$modInfo['osdirectory'] . '/tags';
            if (!is_dir($filepath)) continue;
            $filepath = realpath($filepath);
            if (strpos($filepath, '\\') != false) {
                // On Windows, drive letters are preceeded by an extra / [file:///C:/...]
                $fileURI = 'file:///' . str_replace('\\','/',$filepath);
            } else {
                $fileURI = 'file://' . $filepath;
            }
            foreach (new DirectoryIterator($filepath) as $fileInfo) {
                if($fileInfo->isDot()) continue;
                $pathinfo = pathinfo($fileInfo->getPathName());
                if(isset($pathinfo['extension']) && $pathinfo['extension'] != 'xsl') continue;
                $files[] = $fileURI . "/" . $fileInfo->getFileName();
            }
        }            
        return $files;
    }
}

?>
