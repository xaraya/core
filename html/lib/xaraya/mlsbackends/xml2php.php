<?php
/**
 * Multi Language System
 *
 * @package core\multilanguage
 * @subpackage multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 */

/**
 * This is the default translations backend and should be used for production sites.
 * Note that it does not support the xarMLS__ReferencesBackend interface.
 * <marc> why? have changed this to be able to collapse common methods
 *
 */
sys::import('xaraya.mlsbackends.reference');

class xarMLS__XML2PHPTranslationsBackend extends xarMLS__ReferencesBackend implements ITranslationsBackend
{
    public static $PHPBackend_entries = array();
    public static $PHPBackend_keyEntries = array();
    public $gen;
    public $basePHPDir;
    public $baseXMLDir;

    function __construct($locales)
    {
        parent::__construct($locales);
        $this->backendtype = "php";

        $this->gen = new PHPBackendGenerator(xarMLS::getCurrentLocale());
        if (!isset($this->gen)) return false;
    }

    function translate($string, $type = 0)
    {
        if (isset(self::$PHPBackend_entries[$string]))
            return self::$PHPBackend_entries[$string];
        else {
            if ($type == 1) {
                return $string;
            }
            else {
                return "";
            }
        }
    }

    function translateByKey($key, $type = 0)
    {
        if (isset(self::$PHPBackend_keyEntries[$key]))
            return self::$PHPBackend_keyEntries[$key];
        else {
            if ($type == 1) {
                return $key;
            }
            else {
                return "";
            }
        }
    }

    function clear()
    {
        self::$PHPBackend_entries = array();
        self::$PHPBackend_keyEntries = array();
    }

    function bindDomain($domainType=xarMLS::DNTYPE_CORE, $domainName='xaraya')
    {
        $bindResult = parent::bindDomain($domainType, $domainName);

        $php_locale_dir = sys::varpath()."/locales/{$this->locale}";

        if (!$parsedLocale = xarMLS::parseLocaleString("{$this->locale}")) return false;
        $xml_locale_dir = sys::varpath().'/locales/';
        $xml_locale_dir .= $parsedLocale['lang'].'_'.$parsedLocale['country'].'.utf-8';

        $php_dir = "$php_locale_dir/php";
        $xml_dir = "$xml_locale_dir/xml";

        // Determine the contextType: bein by getting its prefix
        $contextType = xarMLSContext::getContextTypePrefix($domainType);

        $this->basePHPDir = $php_dir . "/" . $contextType . "/";
        $this->baseXMLDir = $xml_dir . "/" . $contextType . "/";

        // The core and objects don't have a domain name in the file path, the other do
        switch ($domainType) {
            case xarMLS::DNTYPE_THEME:
            case xarMLS::DNTYPE_MODULE:
            case xarMLS::DNTYPE_PROPERTY:
            case xarMLS::DNTYPE_BLOCK:
            $this->basePHPDir .= $domainName . "/";
            $this->baseXMLDir = $domainName . "/";
            break;
        }
        $this->baseXMLDir = xarMLSContext::getDomainPath($domainType, $this->locale, 'xml', $domainName) . "/";
        $this->basePHPDir = xarMLSContext::getDomainPath($domainType, $this->locale, 'php', $domainName) . "/";

        if ($bindResult) {
            if (!isset($this->gen)) return false;
            //            if (!isset($this->gen)) {
            //                $this->gen = new PHPBackendGenerator(xarMLS::getCurrentLocale());
            //                if (!isset($this->gen)) return false;
            //            }

            if (!$this->gen->bindDomain($domainType, $domainName)) return false;
            // We already did this above
            if (parent::bindDomain($domainType, $domainName)) return true;
            return true;
        }

        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xarMod::getBaseInfo -> xarDisplayableName -> xarMod::getFileInfo -> MLS
        // We don't use and don't translate KEYS files now,
        // but I will recheck this code in the menus clone
        // if ($dnType == xarMLS::DNTYPE_MODULE) {
        //     $this->loadKEYS($dnName);
        // }

        if (!$this->gen->bindDomain($domainType, $domainName)) return false;
        if (parent::bindDomain($domainType, $domainName)) return true;

        return false;
    }
/*
    function loadKEYS($dnName)
    {
        $modBaseInfo = xarMod::getBaseInfo($dnName);
        $fileName = "modules/$modBaseInfo[directory]/KEYS";
        if (file_exists($fileName)) {

            $lines = file($fileName);
            foreach ($lines as $line) {
                if ($line[0] == '#') continue;
                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $value = trim($value);
                self::$PHPBackend_keyEntries[$key] = $value;
            }
        }
    }
*/
    function findContext($contextType, $contextName)
    {
        // Check if the file already exists
        // Returns filename or false if absent
        $fileName = parent::findContext($contextType, $contextName);

        $phpFileName = $this->basePHPDir;
        $xmlFileName = $this->baseXMLDir;
            
        if (!preg_match("/^[a-z]+:$/", $contextType)) {
            $contextParts = xarMLSContext::getContextTypeComponents($contextType);
            if (!empty($contextParts[1])) {
                $phpFileName .= $contextParts[1] . "/";
                $xmlFileName .= $contextParts[1] . "/";
            }
        }
        $phpFileName .= $contextName . ".php";
        $xmlFileName .= $contextName . ".xml";

        // We need both XML and PHP files at present
        // Check whether PHP files need to be regenerated
        $needGeneration = true;

        if (!file_exists($xmlFileName)) {
            // No XML file, so ignore this case
            $needGeneration = false;
        } elseif (file_exists($xmlFileName) && !file_exists($phpFileName)) {
            // We have an XML file, but no PHP file: generate one
        } else {
            // The PHP file exists but it is newer than the XML file: nothing needs doing
            if (file_exists($phpFileName) && (filemtime($xmlFileName) < filemtime($phpFileName))) {
                $needGeneration = false;
            }
            // Any other case will cause file generation
        }

        if ($needGeneration) {
            //$gen = new PHPBackendGenerator(xarMLS::getCurrentLocale());
            //if (!isset($gen)) return false;
            //if (!$gen->bindDomain($dnType, $dnName)) return false;
            //if (parent::bindDomain($dnType, $dnName)) return true;

            if (!isset($this->gen)) return false;
            if (!$this->gen->create($contextType, $contextName)) return false;

            $fileName = parent::findContext($contextType, $contextName);
            if ($fileName === false) return false;
        }
        return $fileName;
    }

    function loadContext($contextType, $contextName)
    {
        if (!$fileName = $this->findContext($contextType, $contextName)) {
            return true;
        }
        include_once $fileName;

        return true;
    }

    function getContextNames($ctxType)
    {
        // FIXME need more global check
        if (($ctxType == 'core:') || ($ctxType == 'modules:') || ($ctxType == 'properties:') || ($ctxType == 'blocks:') || ($ctxType == 'themes:')) $directory = '';
        else list($prefix,$directory) = explode(':',$ctxType);
        $this->contextlocation = $this->domainlocation . "/" . $directory;
        $ctxNames = array();
        if (!file_exists($this->contextlocation)) {
            return $ctxNames;
        }
        $dd = opendir($this->contextlocation);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.php$/', $fileName, $matches)) continue;
            $ctxNames[] = $matches[1];
        }
        closedir($dd);
        return $ctxNames;
    }
}

/**
 * @package core\multilanguage
 * @subpackage multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 */
class PHPBackendGenerator extends xarObject
{

    public $locale;
    public $outCharset;
    public $fp;
    public $baseDir;
    public $baseXMLDir;

    function __construct($locale)
    {
        $this->locale = $locale;
        $l = xarMLS::localeGetInfo($locale);
        $this->outCharset = $l['charset'];
        $this->isUTF8 = ($l['charset'] == 'utf-8');

        $varDir = sys::varpath();
        $locales_dir = "$varDir/locales";

        $php_locale_dir = "$locales_dir/{$this->locale}";
        $php_dir        = "$php_locale_dir/php";
        $core_dir       = "$php_dir/core";
        $modules_dir    = "$php_dir/modules";
        $themes_dir     = "$php_dir/themes";
        $properties_dir = "$php_dir/properties";
        $blocks_dir     = "$php_dir/blocks";
        $objects_dir    = "$php_dir/objects";

        xarMLS::mkdirr($php_locale_dir);
        xarMLS::mkdirr($php_dir);
        xarMLS::mkdirr($modules_dir);
        xarMLS::mkdirr($properties_dir);
        xarMLS::mkdirr($blocks_dir);
        xarMLS::mkdirr($themes_dir);
        xarMLS::mkdirr($objects_dir);
        xarMLS::mkdirr($core_dir);
    }

    function bindDomain($domainType=xarMLS::DNTYPE_CORE, $domainName='xaraya')
    {
        $varDir = sys::varpath();
        $locales_dir = "$varDir/locales";

        $php_locale_dir = "$locales_dir/{$this->locale}";

        if (!$parsedLocale = xarMLS::parseLocaleString("{$this->locale}")) return false;
        $xml_locale_dir = "$locales_dir/";
        $xml_locale_dir .= $parsedLocale['lang'].'_'.$parsedLocale['country'].'.utf-8';

        $this->baseDir = "$php_locale_dir/php";
        $this->baseXMLDir = "$xml_locale_dir/xml";
        
        // Determine the contextType: bein by getting its prefix
        $contextType = xarMLSContext::getContextTypePrefix($domainType);

        $this->baseDir .= "/" . $contextType . "/";
        $this->baseXMLDir .= "/" . $contextType . "/";

        // The core and objects don't have a domain name in the file path, the other do
        switch ($domainType) {
            case xarMLS::DNTYPE_THEME:
            case xarMLS::DNTYPE_MODULE:
            case xarMLS::DNTYPE_PROPERTY:
            case xarMLS::DNTYPE_BLOCK:
            $this->baseDir .= $domainName . "/";
            $this->baseXMLDir = $domainName . "/";
            if (file_exists($this->baseXMLDir) && !file_exists($this->baseDir)) xarMLS::mkdirr($this->baseDir);
            break;
        }

        return true;
    }

    function create($ctxType, $ctxName)
    {
        assert(!empty($this->baseDir));
        assert(!empty($this->baseXMLDir));
        $this->fileName = $this->baseDir;
        $this->xmlFileName = $this->baseXMLDir;

        if (!preg_match("/^[a-z]+:$/", $ctxType)) {
            list($prefix,$directory) = explode(':',$ctxType);
            if ($directory != "") {
                $this->fileName .= $directory . "/";
                $this->xmlFileName .= $directory . "/";
            }
        }

        $dirForMkDir = $this->fileName;
        $this->fileName .= $ctxName . ".php";
        $this->xmlFileName .= $ctxName . ".xml";

        $xmlFileExists = false;
        if (file_exists($this->xmlFileName)) {
            if (!($fp1 = fopen($this->xmlFileName, "r"))) {
                xarLog::message("Could not open XML input: ".$this->xmlFileName, xarLog::LEVEL_ERROR);
            }
            $data = fread($fp1, filesize($this->xmlFileName));
            fclose($fp1);
            $xml_parser = xml_parser_create();
            xml_parse_into_struct($xml_parser, $data, $vals, $index);
            xml_parser_free($xml_parser);
            $xmlFileExists = true;
        } else {
            xarLog::message("Context Type: ".$ctxType." Context Name: ".$ctxName, xarLog::LEVEL_ERROR);
            xarLog::message("MLS Could not find XML input: ".$this->xmlFileName, xarLog::LEVEL_ERROR);
        }

        if (!$xmlFileExists) return true;

        if (!file_exists($dirForMkDir)) xarMLS::mkdirr($dirForMkDir);
        $fp2 = @fopen ($this->fileName, "w" );
        if ($fp2 !== false) {
            fputs($fp2, '<?php'."\n");
            fputs($fp2, 'global $xarML_PHPBackend_entries;'."\n");
            fputs($fp2, 'global $xarML_PHPBackend_keyEntries;'."\n");
            foreach ($vals as $node) {
                if (!isset($node['tag'])) continue;
                if (!isset($node['value'])) $node['value'] = '';
                if ($node['tag'] == 'STRING') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    $start = '$xarML_PHPBackend_entries[\''.$node['value']."']";
                } elseif ($node['tag'] == 'KEY') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    $start = '$xarML_PHPBackend_keyEntries[\''.$node['value']."']";
                } elseif ($node['tag'] == 'TRANSLATION') {
                    if ($this->outCharset != 'utf-8') {
                        $node['value'] = xarMLS::$newEncoding->convert($node['value'], 'utf-8', $this->outCharset, 0);
                    }
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    if (!empty($node['value'])) {
                        fputs($fp2, $start . " = '".$node['value']."';\n");
                    }
                }
            }
            fputs($fp2, "?>");
            fclose($fp2);
        } else {
            xarLog::message("Could not create file: ".$this->fileName, xarLog::LEVEL_ERROR);
            global $xarML_PHPBackend_entries;
            global $xarML_PHPBackend_keyEntries;
            foreach ($vals as $node) {
                if (!isset($node['tag'])) continue;
                if (!isset($node['value'])) $node['value'] = '';
                if ($node['tag'] == 'STRING') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    $entryIndex = $node['value'];
                    $entryType = 'string';
                } elseif ($node['tag'] == 'KEY') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    $entryIndex = $node['value'];
                    $entryType = 'key';
                } elseif ($node['tag'] == 'TRANSLATION') {
                    if ($this->outCharset != 'utf-8') {
                        $node['value'] = xarMLS::$newEncoding->convert($node['value'], 'utf-8', $this->outCharset, 0);
                    }
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    if ($entryType == 'string') {
                        $xarML_PHPBackend_entries[$entryIndex] = $node['value'];
                    } elseif ($entryType == 'key') {
                        $xarML_PHPBackend_keyEntries[$entryIndex] = $node['value'];
                    }
                }
            }
        }
        return true;
    }
}

