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
class xarMLS__PHPTranslationsBackend extends xarMLS__ReferencesBackend implements ITranslationsBackend
{
    public static $PHPBackend_entries = array();
    public static $PHPBackend_keyEntries = array();

    function __construct($locales)
    {
        parent::__construct($locales);
        $this->backendtype = "php";
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

    function bindDomain($dnType=xarMLS::DNTYPE_CORE, $dnName='xaraya')
    {
        if (parent::bindDomain($dnType, $dnName)) return true;
        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xarMod::getBaseInfo -> xarDisplayableName -> xarMod::getFileInfo -> MLS
        // We don't use and don't translate KEYS files now,
        // but I will recheck this code in the menus clone
        //        if ($dnType == xarMLS::DNTYPE_MODULE) {
        //            $this->loadKEYS($dnName);
        //        }
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
    function loadContext($contextType, $contextName)
    {
        if (!$fileName = $this->findContext($contextType, $contextName)) {
//            $msg = xarML("Context type: #(1) and file name: #(2)", $ctxType, $ctxName);
//            throw new ContextNotFoundException?
//            return;
            return true;
        }
        include_once $fileName;

        return true;
    }

    function getContextNames($contextType)
    {
        $contextParts = xarMLSContext::getContextTypeComponents($contextType);
        
        // Complete the directory path if the context directory is not empty
        if (!empty($contextParts[1])) $this->contextlocation = $this->domainlocation . "/" . $contextParts[1];
        
        $contextNames = array();
        if (!file_exists($this->contextlocation)) {
            return $contextNames;
        }
        $dd = opendir($this->contextlocation);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.php$/', $fileName, $matches)) continue;
            $contextNames[] = $matches[1];
        }
        closedir($dd);
        return $contextNames;
    }
}

