<?php
/* This one exception depends on BL being inside Xaraya, try to correct this later */
if (!class_exists('xarExceptions')) {
    sys::import('xaraya.exceptions');
}
/**
 * Exception raised by the templating subsystem
 *
 * @package core\templating
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class BLCompilerException extends xarExceptions
{
    protected $message = "Cannot open template file '#(1)'";
}

sys::import('blocklayout.compiler');

/**
 * XarayaCompiler - an extension of the BL compiler
 *
 * @package core\templating
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
class XarayaCompiler extends xarBLCompiler
{    
    private $legacy_compile = true;

    /**
     * Summary of instance
     * @return IxarBLCompiler
     */
    public static function &instance()
    {
        if(self::$instance == null) {
            self::$instance = new XarayaCompiler();
        }
        return self::$instance;
    }

    /**
     * Summary of configure
     * @return array<string>
     */
    public function configure()
    {
        parent::configure();

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
        $baseDir = sys::web() . 'themes/common/tags';
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

        // Add the custom tags from standalone properties
        $xslFiles = array_merge($xslFiles,$this->getPropertyTagPaths());

        // Add the custom tags from standalone blocks
        $xslFiles = array_merge($xslFiles,$this->getBlockTagPaths());

        return $xslFiles;
    }

    /**
     * Private methods
     */

     /**
     * Summary of boot
     * @param DOMDocument|null $customDoc
     * @return string
     */
    protected function boot($customDoc=null)
    {
        // TODO: generalize this functionality to any custom markup
        
        // Look for the stylesheet with the tag transforms
        $xmlFile = sys::code() . 'modules/themeworks/xslt/tag-transforms.xsl';
        
        if (file_exists($xmlFile)) {
            // Create a document object and load the stylesheet
            $doc = new DOMDocument();
            $doc->load($xmlFile);
        
            // Get the stylesheet for the booter 
            $xslFile = sys::code() . 'modules/themeworks/xslt/booter.xsl';
            // Make it an XSL processor
            $xslProc = $this->getProcessor($xslFile);
        
            // Get the value for the framework tag, which is defined in a modvar
            if (method_exists('xarModVars', 'get')) {
                $framework = xarModVars::get('themeworks', 'framework');
            } else {
                $framework = '';
            }
            // Make sure we have a default value. Remove this line later
            if (empty($framework)) $framework = 'bootstrap';
            // Pass it to the processor
            $xslProc->setParameter('', 'framework', $framework);

            // Process this object to get the document to insert into the compiler
            $customDoc = $xslProc->transformToDoc($doc);
        }
        
        $outDoc = parent::boot($customDoc);
        return $outDoc;
    }

    private function getModuleTagPaths()
    {
        if (method_exists('xarMod', 'apiFunc') && empty(xarCoreCache::getCached('installer','installing'))) {
            $activeMods = xarMod::apiFunc('modules','admin','getlist', array('filter' => array('State' => xarMod::STATE_ACTIVE)));
        } else {
            return array();
        }
        assert(!empty($activeMods)); // this should never happen

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

    private function getPropertyTagPaths()
    {
        // Loop through properties directory and look for tags
        sys::import('xaraya.structures.relativedirectoryiterator');
        $propertiesdir = sys::code() . 'properties/';
        if (!file_exists($propertiesdir)) throw new DirectoryNotFoundException($propertiesdir);

        $dir = new RelativeDirectoryIterator($propertiesdir);
        $files = array();
        for ($dir->rewind();$dir->valid();$dir->next()) {
            if ($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            if (!$dir->isDir()) continue; // only dirs

            // Check this property for a tags directory
            $file = $dir->getPathName();
            $filepath = $file . '/tags';
            if (!is_dir($filepath)) continue; // only the tags directory (if it exists)
            
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
        if (method_exists('xarMod', 'apiFunc') && empty(xarCoreCache::getCached('installer','installing'))) {
            $activeBlocks = xarMod::apiFunc('blocks', 'instances', 'getitems', array('state' => 2));
        } else {
            return array();
        }
//        assert(!empty($activeBlocks)); // this should never happen

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
