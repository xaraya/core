<?php

/**
 * XarayaCompiler - the abstraction of the BL compiler
 *
 * @package xaraya
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
        $xslFiles = $this->getTagPaths($baseDir, 'file://' . str_replace('\\','/',realpath($baseDir)));
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
            $fileURL = 'file://' . str_replace('\\','/',realpath($filepath));
            if (!file_exists($filepath)) continue;
            foreach (new DirectoryIterator($filepath) as $fileInfo) {
                if($fileInfo->isDot()) continue;
                $pathinfo = pathinfo($fileInfo->getPathName());
                if(isset($pathinfo['extension']) && $pathinfo['extension'] != 'xsl') continue;
                $files[] = $fileURL . "/" . $fileInfo->getFileName();
            }
        }            
        return $files;
    }
}

?>