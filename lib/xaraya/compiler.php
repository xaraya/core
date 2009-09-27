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
        $baseDir = sys::lib() . 'blocklayout/xslt/tags/xaraya';
        $xslFiles = $this->getXSLFilesString($baseDir, 'tags/xaraya');
        // Pass the module tags
        $xslFiles = array_merge($xslFiles,$this->getXSLModuleFilesString());
        return $xslFiles;
    }

    /**
     * Private methods
     */
    private function getXSLModuleFilesString()
    {
        if (function_exists('xarModAPIFunc')) {
            $activeMods = xarModAPIFunc('modules','admin','getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        } else {
            return array();
        }
        assert('!empty($activeMods)'); // this should never happen

        $files = array();
        foreach($activeMods as $modInfo) {
            $filepath = 'modules/' .$modInfo['osdirectory'] . '/tags';
            if (!file_exists($filepath)) continue;
            foreach (new DirectoryIterator($filepath) as $fileInfo) {
                if($fileInfo->isDot()) continue;
                $pathinfo = pathinfo($fileInfo->getPathName());
                if(isset($pathinfo['extension']) && $pathinfo['extension'] != 'xsl') continue;
                $files[] = $prefix . "/" . $fileInfo->getFileName();
            }
        }            
        return $files;
    }
}

?>