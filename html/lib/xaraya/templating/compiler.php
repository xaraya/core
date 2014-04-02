<?php
/**
 * @package core
 * @subpackage templating
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/* This one exception depends on BL being inside Xaraya, try to correct this later */
if (!class_exists('xarExceptions')) {
    sys::import('xaraya.exceptions');
}
/**
 * Exceptions raised by this subsystem
 *
 * @package compiler
 */
class BLCompilerException extends xarExceptions
{
    protected $message = "Cannot open template file '#(1)'";
}

/**
 * XarayaCompiler - the abstraction of the BL compiler
 *
 * 
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
        // Compressing excess whitespace
        try {
            $this->compresswhitespace = xarConfigVars::get(null, 'Site.BL.CompressWhitespace');
        } catch (Exception $e) {
            $this->compresswhitespace = 1;
        }

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
        
        // Get any custom tags in themes/common/tags
        $baseDir = 'themes/common/tags';
        $baseDir = realpath($baseDir);
        if (strpos($baseDir, '\\') != false) {
            // On Windows, drive letters are preceeded by an extra / [file:///C:/...]
            $baseURI = 'file:///' . str_replace('\\','/',$baseDir);
        } else {
            $baseURI = 'file://' . $baseDir;
        }
        $xslFiles = array_merge($xslFiles,$this->getTagPaths($baseDir, $baseURI));
        
        // Add the custom tags from modules
        $xslFiles = array_merge($xslFiles,$this->getModuleTagPaths());

        // Get any custom tags in standalone blocks
        $xslFiles = array_merge($xslFiles,$this->getBlockTagPaths());

        return $xslFiles;
    }

    public function compileFile($fileName)
    {
        xarLog::message("BL: compiling $fileName");
        return parent::compileFile($fileName);
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

    private function getBlockTagPaths()
    {
        if (function_exists('xarModAPIFunc')) {
            $activeBlocks = xarMod::apiFunc('blocks', 'instances', 'getitems', array('state' => 2));
        } else {
            return array();
        }
//        assert('!empty($activeBlocks)'); // this should never happen

        $files = array();
        foreach($activeBlocks as $blockInfo) {
            $filepath = sys::code() . 'blocks/' .$blockInfo['name'] . '/tags';
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
