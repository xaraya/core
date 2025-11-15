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
use Xaraya\Services\xar;

class xarMLS__XML2PHPTranslationsBackend extends xarMLS__ReferencesBackend implements ITranslationsBackend
{
    public $PHPBackend_entries = [];
    public $PHPBackend_keyEntries = [];
    public $gen;
    public $basePHPDir;
    public $baseXMLDir;

    public function __construct($locales, $currentLocale)
    {
        parent::__construct($locales);
        $this->backendtype = "php";

        $this->gen = new PHPBackendGenerator($currentLocale);
        if (!isset($this->gen)) {
            return;
        }
    }

    public function translate($string, $type = 0)
    {
        if (isset($this->PHPBackend_entries[$string])) {
            return $this->PHPBackend_entries[$string];
        } else {
            if ($type == 1) {
                return $string;
            } else {
                return "";
            }
        }
    }

    public function translateByKey($key, $type = 0)
    {
        if (isset($this->PHPBackend_keyEntries[$key])) {
            return $this->PHPBackend_keyEntries[$key];
        } else {
            if ($type == 1) {
                return $key;
            } else {
                return "";
            }
        }
    }

    public function clear()
    {
        $this->PHPBackend_entries = [];
        $this->PHPBackend_keyEntries = [];
    }

    public function bindDomain($domainType = ixarMLS::DNTYPE_CORE, $domainName = 'xaraya')
    {
        $bindResult = parent::bindDomain($domainType, $domainName);

        $php_locale_dir = sys::varpath() . "/locales/{$this->locale}";

        if (!$parsedLocale = xarLocale::parseLocaleString("{$this->locale}")) {
            return false;
        }
        $xml_locale_dir = sys::varpath() . '/locales/';
        $xml_locale_dir .= $parsedLocale['lang'] . '_' . $parsedLocale['country'] . '.utf-8';

        $php_dir = "$php_locale_dir/php";
        $xml_dir = "$xml_locale_dir/xml";

        // Determine the contextType: bein by getting its prefix
        $contextType = xarMLSContext::getContextTypePrefix($domainType);

        $this->basePHPDir = $php_dir . "/" . $contextType . "/";
        $this->baseXMLDir = $xml_dir . "/" . $contextType . "/";

        // The core and objects don't have a domain name in the file path, the other do
        switch ($domainType) {
            case ixarMLS::DNTYPE_THEME:
            case ixarMLS::DNTYPE_MODULE:
            case ixarMLS::DNTYPE_PROPERTY:
            case ixarMLS::DNTYPE_BLOCK:
                $this->basePHPDir .= $domainName . "/";
                $this->baseXMLDir = $domainName . "/";
                break;
        }
        $this->baseXMLDir = xarMLSContext::getDomainPath($domainType, $this->locale, 'xml', $domainName) . "/";
        $this->basePHPDir = xarMLSContext::getDomainPath($domainType, $this->locale, 'php', $domainName) . "/";

        if ($bindResult) {
            if (!isset($this->gen)) {
                return false;
            }
            //            if (!isset($this->gen)) {
            //                $this->gen = new PHPBackendGenerator(xar::mls()->getCurrentLocale());
            //                if (!isset($this->gen)) return false;
            //            }

            if (!$this->gen->bindDomain($domainType, $domainName)) {
                return false;
            }
            // We already did this above
            if (parent::bindDomain($domainType, $domainName)) {
                return true;
            }
            return true;
        }

        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xar::mod()->getBaseInfo -> xarDisplayableName -> xar::mod()->getFileInfo -> MLS
        // We don't use and don't translate KEYS files now,
        // but I will recheck this code in the menus clone
        // if ($dnType == ixarMLS::DNTYPE_MODULE) {
        //     $this->loadKEYS($dnName);
        // }

        if (!$this->gen->bindDomain($domainType, $domainName)) {
            return false;
        }
        if (parent::bindDomain($domainType, $domainName)) {
            return true;
        }

        return false;
    }

    public function findContext($contextType, $contextName)
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
            //$gen = new PHPBackendGenerator(xar::mls()->getCurrentLocale());
            //if (!isset($gen)) return false;
            //if (!$gen->bindDomain($dnType, $dnName)) return false;
            //if (parent::bindDomain($dnType, $dnName)) return true;

            if (!isset($this->gen)) {
                return false;
            }
            if (!$this->gen->create($contextType, $contextName)) {
                return false;
            }

            $fileName = parent::findContext($contextType, $contextName);
            if ($fileName === false) {
                return false;
            }
        }
        return $fileName;
    }

    public function loadContext($contextType, $contextName)
    {
        if (!$fileName = $this->findContext($contextType, $contextName)) {
            return true;
        }
        // @todo return entries and keys from include file instead of using globals
        global $xarML_PHPBackend_entries;
        global $xarML_PHPBackend_keyEntries;
        include_once $fileName;
        if (!empty($xarML_PHPBackend_entries)) {
            $this->PHPBackend_entries = array_merge($this->PHPBackend_entries, $xarML_PHPBackend_entries);
        }
        if (!empty($xarML_PHPBackend_keyEntries)) {
            $this->PHPBackend_keyEntries = array_merge($this->PHPBackend_keyEntries, $xarML_PHPBackend_keyEntries);
        }

        return true;
    }

    public function getContextNames($ctxType)
    {
        // FIXME need more global check
        if (($ctxType == 'core:') || ($ctxType == 'modules:') || ($ctxType == 'properties:') || ($ctxType == 'blocks:') || ($ctxType == 'themes:')) {
            $directory = '';
        } else {
            [$prefix, $directory] = explode(':', $ctxType);
        }
        $this->contextlocation = $this->domainlocation . "/" . $directory;
        $ctxNames = [];
        if (!file_exists($this->contextlocation)) {
            return $ctxNames;
        }
        $dd = opendir($this->contextlocation);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.php$/', $fileName, $matches)) {
                continue;
            }
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
    public $isUTF8;
    public $fileName;
    public $xmlFileName;
    public $newEncoding;

    public function __construct($locale)
    {
        $this->locale = $locale;
        $l = xarLocale::parseLocaleString($locale);
        $this->outCharset = $l['charset'];
        $this->isUTF8 = ($l['charset'] == 'utf-8');
        $this->newEncoding = new xarCharset();

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

        self::mkdirr($php_locale_dir);
        self::mkdirr($php_dir);
        self::mkdirr($modules_dir);
        self::mkdirr($properties_dir);
        self::mkdirr($blocks_dir);
        self::mkdirr($themes_dir);
        self::mkdirr($objects_dir);
        self::mkdirr($core_dir);
    }

    public function bindDomain($domainType = ixarMLS::DNTYPE_CORE, $domainName = 'xaraya')
    {
        $varDir = sys::varpath();
        $locales_dir = "$varDir/locales";

        $php_locale_dir = "$locales_dir/{$this->locale}";

        if (!$parsedLocale = xarLocale::parseLocaleString("{$this->locale}")) {
            return false;
        }
        $xml_locale_dir = "$locales_dir/";
        $xml_locale_dir .= $parsedLocale['lang'] . '_' . $parsedLocale['country'] . '.utf-8';

        $this->baseDir = "$php_locale_dir/php";
        $this->baseXMLDir = "$xml_locale_dir/xml";

        // Determine the contextType: bein by getting its prefix
        $contextType = xarMLSContext::getContextTypePrefix($domainType);

        $this->baseDir .= "/" . $contextType . "/";
        $this->baseXMLDir .= "/" . $contextType . "/";

        // The core and objects don't have a domain name in the file path, the other do
        switch ($domainType) {
            case ixarMLS::DNTYPE_THEME:
            case ixarMLS::DNTYPE_MODULE:
            case ixarMLS::DNTYPE_PROPERTY:
            case ixarMLS::DNTYPE_BLOCK:
                $this->baseDir .= $domainName . "/";
                $this->baseXMLDir = $domainName . "/";
                if (file_exists($this->baseXMLDir) && !file_exists($this->baseDir)) {
                    self::mkdirr($this->baseDir);
                }
                break;
        }

        return true;
    }

    public function create($ctxType, $ctxName)
    {
        assert(!empty($this->baseDir));
        assert(!empty($this->baseXMLDir));
        $this->fileName = $this->baseDir;
        $this->xmlFileName = $this->baseXMLDir;

        if (!preg_match("/^[a-z]+:$/", $ctxType)) {
            [$prefix, $directory] = explode(':', $ctxType);
            if ($directory != "") {
                $this->fileName .= $directory . "/";
                $this->xmlFileName .= $directory . "/";
            }
        }

        $dirForMkDir = $this->fileName;
        $this->fileName .= $ctxName . ".php";
        $this->xmlFileName .= $ctxName . ".xml";

        $xar = xar::getServicesClass();
        $xmlFileExists = false;
        if (file_exists($this->xmlFileName)) {
            if (!($fp1 = fopen($this->xmlFileName, "r"))) {
                $xar->log()->error("Could not open XML input: " . $this->xmlFileName);
            }
            $data = fread($fp1, filesize($this->xmlFileName));
            fclose($fp1);
            $xml_parser = xml_parser_create();
            xml_parse_into_struct($xml_parser, $data, $vals, $index);
            xml_parser_free($xml_parser);
            $xmlFileExists = true;
        } else {
            $xar->log()->error("Context Type: " . $ctxType . " Context Name: " . $ctxName);
            $xar->log()->error("MLS Could not find XML input: " . $this->xmlFileName);
        }

        if (!$xmlFileExists) {
            return true;
        }

        if (!file_exists($dirForMkDir)) {
            self::mkdirr($dirForMkDir);
        }
        $fp2 = @fopen($this->fileName, "w");
        if ($fp2 !== false) {
            fputs($fp2, '<?php' . "\n");
            fputs($fp2, 'global $xarML_PHPBackend_entries;' . "\n");
            fputs($fp2, 'global $xarML_PHPBackend_keyEntries;' . "\n");
            $start = '';
            foreach ($vals as $node) {
                if (!isset($node['tag'])) {
                    continue;
                }
                if (!isset($node['value'])) {
                    $node['value'] = '';
                }
                if ($node['tag'] == 'STRING') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    $start = '$xarML_PHPBackend_entries[\'' . $node['value'] . "']";
                } elseif ($node['tag'] == 'KEY') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    $start = '$xarML_PHPBackend_keyEntries[\'' . $node['value'] . "']";
                } elseif ($node['tag'] == 'TRANSLATION') {
                    if ($this->outCharset != 'utf-8') {
                        $node['value'] = $this->newEncoding->convert($node['value'], 'utf-8', $this->outCharset, 0);
                    }
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    if (!empty($node['value'])) {
                        fputs($fp2, $start . " = '" . $node['value'] . "';\n");
                    }
                }
            }
            fputs($fp2, "\n");
            fclose($fp2);
        } else {
            $xar->log()->error("Could not create file: " . $this->fileName);
            global $xarML_PHPBackend_entries;
            global $xarML_PHPBackend_keyEntries;
            $entryIndex = '';
            $entryType = '';
            foreach ($vals as $node) {
                if (!isset($node['tag'])) {
                    continue;
                }
                if (!isset($node['value'])) {
                    $node['value'] = '';
                }
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
                        $node['value'] = $this->newEncoding->convert($node['value'], 'utf-8', $this->outCharset, 0);
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

    /**
     * Create directories tree
     *
     * @author Volodymyr Metenchuk <voll@xaraya.com>
     * @return boolean true
     */
    public static function mkdirr($path)
    {
        // Check if directory already exists
        if (is_dir($path) || empty($path)) {
            return true;
        }

        // Crawl up the directory tree
        $next_path = substr($path, 0, strrpos($path, '/'));
        if (self::mkdirr($next_path)) {
            if (!file_exists($path)) {
                try {
                    $madeDir = mkdir($path, 0o700);
                    return $madeDir;
                } catch (Exception $e) {
                    $xar = xar::getServicesClass();
                    $msg = $xar->mls()->translate("Could not create directory #(1). The directories under #(2) must be writeable by PHP.", $path, $next_path);
                    $xar->log()->error($msg);
                    xarCore::exit($msg);
                    // throw new PermissionException?
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Check directory writability and create directory if it doesn't exist
     *
     * @author Volodymyr Metenchuk <voll@xaraya.com>
     * @access protected
     * @return bool true
     */
    public static function iswritable($directory = null)
    {
        if ($directory == null) {
            $directory = getcwd();
        }

        if (file_exists($directory)) {
            if (!is_dir($directory)) {
                return false;
            }
            $isWritable = true;
            $isWritable &= is_writable($directory);
            $handle = opendir($directory);
            while ($isWritable && (false !== ($filename = readdir($handle)))) {
                if (($filename != ".") && ($filename != "..") && ($filename != "SCCS")) {
                    if (is_dir($directory . "/" . $filename)) {
                        $isWritable &= is_writable($directory . "/" . $filename);
                        $isWritable &= self::iswritable($directory . "/" . $filename);
                    } else {
                        $isWritable &= is_writable($directory . "/" . $filename);
                    }
                }
            }
            return $isWritable;
        } else {
            $isWritable = self::mkdirr($directory);
            return $isWritable;
        }
    }
}
