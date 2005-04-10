<?php
/**
 * Multi Language System
 *
 * @package multilanguage
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
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
class xarMLS__XML2PHPTranslationsBackend extends xarMLS__ReferencesBackend
{
    var $gen;
    var $basePHPDir;
    var $baseXMLDir;

    function xarMLS__XML2PHPTranslationsBackend($locales)
    {
        parent::xarMLS__ReferencesBackend($locales);
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

    function bindDomain($dnType, $dnName='xaraya')
    {
        $bindResult = parent::bindDomain($dnType, $dnName);

        $php_locale_dir = xarCoreGetVarDirPath()."/locales/{$this->locale}";

        if (!$parsedLocale = xarMLS__parseLocaleString("{$this->locale}")) return false;
        $xml_locale_dir = xarCoreGetVarDirPath().'/locales/';
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

//            xarLogMessage("MLS Bind gen. directory: $dnType, $dnName");
            if (!$this->gen->bindDomain($dnType, $dnName)) return false;
            if (parent::bindDomain($dnType, $dnName)) return true;
            return false;
        }

        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xarMod_getBaseInfo -> xarDisplayableName -> xarMod_getFileInfo -> MLS
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
        $modBaseInfo = xarMod_getBaseInfo($dnName);
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
            //xarLogMessage("Bind gen. directory: $dnType, $dnName");
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
            // $msg = xarML("Context type: #(1) and file name: #(2)", $ctxType, $ctxName);
            // xarErrorSet(XAR_SYSTEM_EXCEPTION, 'CONTEXT_NOT_EXIST', new SystemException($msg));
            // return;
            return true;
        }
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

class PHPBackendGenerator 
{

    var $locale;
    var $outCharset;
    var $fp;
    var $baseDir;
    var $baseXMLDir;

    function PHPBackendGenerator($locale)
    {
        $this->locale = $locale;
        $l = xarLocaleGetInfo($locale);
        $this->outCharset = $l['charset'];
        $this->isUTF8 = ($l['charset'] == 'utf-8');

        $varDir = xarCoreGetVarDirPath();
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

        $canWrite = 1;
        if (file_exists($locales_dir)) {
            if (file_exists($php_locale_dir)) {
                if (file_exists($php_dir)) {
                    if (file_exists($modules_dir) && file_exists($themes_dir) &&
                        file_exists($core_dir)) {
                        if (!is_writeable($modules_dir)) $canWrite = 0;
                        if (!is_writeable($themes_dir)) $canWrite = 0;
                        if (!is_writeable($core_dir)) $canWrite = 0;
                    } else {
                        if (is_writeable($php_dir)) {
                            if (file_exists($modules_dir)) {
                                if (!is_writeable($modules_dir)) $canWrite = 0;
                            } else {
                                mkdir($modules_dir, 0777);
                            }
                            if (file_exists($themes_dir)) {
                                if (!is_writeable($themes_dir)) $canWrite = 0;
                            } else {
                                mkdir($themes_dir, 0777);
                            }
                            if (file_exists($core_dir)) {
                                if (!is_writeable($core_dir)) $canWrite = 0;
                            } else {
                                mkdir($core_dir, 0777);
                            }
                        } else {
                            $canWrite = 0; // var/locales/LOCALE/php is unwriteable
                        }
                    }
                } else {
                    if (is_writeable($php_locale_dir)) {
                        mkdir($php_dir, 0777);
                        mkdir($modules_dir, 0777);
                        mkdir($themes_dir, 0777);
                        mkdir($core_dir, 0777);
                    } else {
                        $canWrite = 0; // var/locales/LOCALE is unwriteable
                    }
                }
            } else {
                if (is_writeable($locales_dir)) {
                    mkdir($php_locale_dir, 0777);
                    mkdir($php_dir, 0777);
                    mkdir($modules_dir, 0777);
                    mkdir($themes_dir, 0777);
                    mkdir($core_dir, 0777);
                } else {
                    $canWrite = 0; // var/locales is unwriteable
                }
            }
        } else {
            $canWrite = 0; // var/locales missed
        }

        if (!$canWrite) {
            $msg = xarML("The directories under #(1) must be writeable by PHP.", $locales_dir);
            xarErrorSet(XAR_USER_EXCEPTION, 'WrongPermissions', new DefaultUserException($msg));
            return;
        }
    }

    function bindDomain($dnType='core', $dnName='xaraya')
    {
        $varDir = xarCoreGetVarDirPath();
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
            if (!file_exists($this->baseDir)) mkdir($this->baseDir, 0777);
            break;
        case XARMLS_DNTYPE_THEME:
            $this->baseDir = "$themes_dir/$dnName/";
            $this->baseXMLDir = "$xml_themes_dir/$dnName/";
            if (!file_exists($this->baseDir)) mkdir($this->baseDir, 0777);
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
        if (!file_exists($dirForMkDir)) xarMLS__mkdirr($dirForMkDir, 0777);

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

        $fp2 = fopen ($this->fileName, "w" );
        fputs($fp2, '<?php'."\n");
        fputs($fp2, 'global $xarML_PHPBackend_entries;'."\n");
        fputs($fp2, 'global $xarML_PHPBackend_keyEntries;'."\n");
        if ($xmlFileExists) {
            foreach ($vals as $node) {
                if ($node['tag'] == 'STRING') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    fputs($fp2, '$xarML_PHPBackend_entries[\''.$node['value']."']");
                } elseif ($node['tag'] == 'KEY') {
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    fputs($fp2, '$xarML_PHPBackend_keyEntries[\''.$node['value']."']");
                } elseif ($node['tag'] == 'TRANSLATION') {
                    if (!array_key_exists('value',$node)) $node['value'] = '';
                    if ($this->outCharset != 'utf-8') {
                        $node['value'] = $GLOBALS['xarMLS_newEncoding']->convert($node['value'], 'utf-8', $this->outCharset, 0);
                    }
                    $node['value'] = str_replace('\'', '\\\'', $node['value']);
                    fputs($fp2, " = '".$node['value']."';\n");
                }
            }
        }
        fputs($fp2, "?>");
        fclose($fp2);

        return true;
    }
}

function xarMLS__mkdirr($path, $mode)
{
    // Check if directory already exists
    if (is_dir($path) || empty($path)) {
        return true;
    }
         
    // Crawl up the directory tree
    $next_path = substr($path, 0, strrpos($path, '/'));
    if (xarMLS__mkdirr($next_path, $mode)) {
        if (!file_exists($path)) {
            return mkdir($path, $mode);
        }
    }
    return false;
}

?>