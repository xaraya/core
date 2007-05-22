<?php
/**
 * Multi Language System
 *
 * @package multilanguage
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 */

/**
 * This is the default translations backend and should be used for production sites.
 * Note that it does not support the xarMLS__ReferencesBackend interface.
 * <marc> why? have changed this to be able to collapse common methods
 *
 * @package multilanguage
 */
sys::import('xaraya.mls');

class xarMLS__XML2PHPTranslationsBackend extends xarMLS__ReferencesBackend implements ITranslationsBackend
{
    public $gen;
    public $basePHPDir;
    public $baseXMLDir;

    function __construct($locales)
    {
        parent::__construct($locales);
        $this->backendtype = "php";

        $this->gen = new PHPBackendGenerator(xarMLSGetCurrentLocale());
        if (!isset($this->gen)) return false;
    }

    function translate($string, $type = 0)
    {
        if (isset($GLOBALS['xarML_PHPBackend_entries'][$string]))
            return $GLOBALS['xarML_PHPBackend_entries'][$string];
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
        if (isset($GLOBALS['xarML_PHPBackend_keyEntries'][$key]))
            return $GLOBALS['xarML_PHPBackend_keyEntries'][$key];
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
        $GLOBALS['xarML_PHPBackend_entries'] = array();
        $GLOBALS['xarML_PHPBackend_keyEntries'] = array();
    }

    function bindDomain($dnType, $dnName)
    {
        $bindResult = parent::bindDomain($dnType, $dnName);

        $php_locale_dir = sys::varpath()."/locales/{$this->locale}";

        if (!$parsedLocale = xarMLS__parseLocaleString("{$this->locale}")) return false;
        $xml_locale_dir = sys::varpath().'/locales/';
        $xml_locale_dir .= $parsedLocale['lang'].'_'.$parsedLocale['country'].'.utf-8';

        $php_dir = "$php_locale_dir/php";
        $xml_dir = "$xml_locale_dir/xml";

        switch ($dnType) {
            case XARMLS_DNTYPE_MODULE:
            $this->basePHPDir = "$php_dir/modules/$dnName/";
            $this->baseXMLDir = "$xml_dir/modules/$dnName/";
            break;
            case XARMLS_DNTYPE_THEME:
            $this->basePHPDir = "$php_dir/themes/$dnName/";
            $this->baseXMLDir = "$xml_dir/themes/$dnName/";
            break;
            case XARMLS_DNTYPE_CORE:
            $this->basePHPDir = "$php_dir/core/";
            $this->baseXMLDir = "$xml_dir/core/";
            break;
        }

        if ($bindResult) {
            if (!isset($this->gen)) return false;
            //            if (!isset($this->gen)) {
            //                $this->gen = new PHPBackendGenerator(xarMLSGetCurrentLocale());
            //                if (!isset($this->gen)) return false;
            //            }

            if (!$this->gen->bindDomain($dnType, $dnName)) return false;
            if (parent::bindDomain($dnType, $dnName)) return true;
            return false;
        }

        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xarMod::getBaseInfo -> xarDisplayableName -> xarMod::getFileInfo -> MLS
        // We don't use and don't translate KEYS files now,
        // but I will recheck this code in the menus clone
        // if ($dnType == XARMLS_DNTYPE_MODULE) {
        //     $this->loadKEYS($dnName);
        // }

        if (!$this->gen->bindDomain($dnType, $dnName)) return false;
        if (parent::bindDomain($dnType, $dnName)) return true;

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
                if ($line{0} == '#') continue;
                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $value = trim($value);
                $GLOBALS['xarML_PHPBackend_keyEntries'][$key] = $value;
            }
        }
    }
*/
    function findContext($ctxType, $ctxName)
    {
        // Returns filename or false if absent
        $fileName = parent::findContext($ctxType, $ctxName);

        $phpFileName = $this->basePHPDir;
        $xmlFileName = $this->baseXMLDir;

        if (!ereg("^[a-z]+:$", $ctxType)) {
            list($prefix,$directory) = explode(':',$ctxType);
            if ($directory != "") {
                $phpFileName .= $directory . "/";
                $xmlFileName .= $directory . "/";
            }
        }

        $phpFileName .= $ctxName . ".php";
        $xmlFileName .= $ctxName . ".xml";

        $needGeneration = true;

        if (!file_exists($xmlFileName)) {
            $needGeneration = false;
        } else {
            if (file_exists($phpFileName) && (filemtime($xmlFileName) < filemtime($phpFileName))) {
                $needGeneration = false;
            }
        }

        if ($needGeneration) {
            //$gen = new PHPBackendGenerator(xarMLSGetCurrentLocale());
            //if (!isset($gen)) return false;
            //if (!$gen->bindDomain($dnType, $dnName)) return false;
            //if (parent::bindDomain($dnType, $dnName)) return true;

            if (!isset($this->gen)) return false;
            if (!$this->gen->create($ctxType, $ctxName)) return false;
            $fileName = parent::findContext($ctxType, $ctxName);
            if ($fileName === false) return false;
        }
        return $fileName;
    }

    function loadContext($ctxType, $ctxName)
    {
        if (!$fileName = $this->findContext($ctxType, $ctxName)) {
            return true;
        }
        // @todo do we need to guard this?
        include $fileName;

        return true;
    }

    function getContextNames($ctxType)
    {
        // FIXME need more global check
        if (($ctxType == 'core:') || ($ctxType == 'modules:') || ($ctxType == 'themes:')) $directory = '';
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

class PHPBackendGenerator extends Object
{

    public $locale;
    public $outCharset;
    public $fp;
    public $baseDir;
    public $baseXMLDir;

    function PHPBackendGenerator($locale)
    {
        $this->locale = $locale;
        $l = xarLocaleGetInfo($locale);
        $this->outCharset = $l['charset'];
        $this->isUTF8 = ($l['charset'] == 'utf-8');

        $varDir = sys::varpath();
        $locales_dir = "$varDir/locales";

        $php_locale_dir = "$locales_dir/{$this->locale}";
        $php_dir = "$php_locale_dir/php";
        $modules_dir = "$php_dir/modules";
        $themes_dir = "$php_dir/themes";
        $core_dir = "$php_dir/core";

        xarMLS__mkdirr($php_locale_dir);
        xarMLS__mkdirr($php_dir);
        xarMLS__mkdirr($modules_dir);
        xarMLS__mkdirr($themes_dir);
        xarMLS__mkdirr($core_dir);
    }

    function bindDomain($dnType='core', $dnName='xaraya')
    {
        $varDir = sys::varpath();
        $locales_dir = "$varDir/locales";

        $php_locale_dir = "$locales_dir/{$this->locale}";

        if (!$parsedLocale = xarMLS__parseLocaleString("{$this->locale}")) return false;
        $xml_locale_dir = "$locales_dir/";
        $xml_locale_dir .= $parsedLocale['lang'].'_'.$parsedLocale['country'].'.utf-8';

        $php_dir = "$php_locale_dir/php";
        $xml_dir = "$xml_locale_dir/xml";
        $modules_dir = "$php_dir/modules";
        $themes_dir = "$php_dir/themes";
        $core_dir = "$php_dir/core";
        $xml_modules_dir = "$xml_dir/modules";
        $xml_themes_dir = "$xml_dir/themes";
        $xml_core_dir = "$xml_dir/core";

        switch ($dnType) {
        case XARMLS_DNTYPE_MODULE:
            $this->baseDir = "$modules_dir/$dnName/";
            $this->baseXMLDir = "$xml_modules_dir/$dnName/";
            if (file_exists($this->baseXMLDir) && !file_exists($this->baseDir)) xarMLS__mkdirr($this->baseDir);
            break;
        case XARMLS_DNTYPE_THEME:
            $this->baseDir = "$themes_dir/$dnName/";
            $this->baseXMLDir = "$xml_themes_dir/$dnName/";
            if (file_exists($this->baseXMLDir) && !file_exists($this->baseDir)) xarMLS__mkdirr($this->baseDir);
            break;
        case XARMLS_DNTYPE_CORE:
            $this->baseDir = $core_dir.'/';
            $this->baseXMLDir = $xml_core_dir.'/';
        }

        return true;
    }

    function create($ctxType, $ctxName)
    {
        assert('!empty($this->baseDir)');
        assert('!empty($this->baseXMLDir)');
        $this->fileName = $this->baseDir;
        $this->xmlFileName = $this->baseXMLDir;

        if (!ereg("^[a-z]+:$", $ctxType)) {
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
                xarLogMessage("Could not open XML input: ".$this->xmlFileName);
            }
            $data = fread($fp1, filesize($this->xmlFileName));
            fclose($fp1);
            $xml_parser = xml_parser_create();
            xml_parse_into_struct($xml_parser, $data, $vals, $index);
            xml_parser_free($xml_parser);
            $xmlFileExists = true;
        } else {
            xarLogMessage("MLS Could not find XML input: ".$this->xmlFileName);
        }

        if (!$xmlFileExists) return true;

        if (!file_exists($dirForMkDir)) xarMLS__mkdirr($dirForMkDir);
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
                        $node['value'] = $GLOBALS['xarMLS_newEncoding']->convert($node['value'], 'utf-8', $this->outCharset, 0);
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
            xarLogMessage("Could not create file: ".$this->fileName);
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
                        $node['value'] = $GLOBALS['xarMLS_newEncoding']->convert($node['value'], 'utf-8', $this->outCharset, 0);
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

?>
