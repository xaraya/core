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

sys::import('xaraya.mlsbackends.reference');
class xarMLS__PHPTranslationsBackend extends xarMLS__ReferencesBackend implements ITranslationsBackend
{
    function __construct($locales)
    {
        parent::__construct($locales);
        $this->backendtype = "php";
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
        if (parent::bindDomain($dnType, $dnName)) return true;
        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xarMod::getBaseInfo -> xarDisplayableName -> xarMod::getFileInfo -> MLS
        // We don't use and don't translate KEYS files now,
        // but I will recheck this code in the menus clone
        //        if ($dnType == XARMLS_DNTYPE_MODULE) {
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
                if ($line{0} == '#') continue;
                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $value = trim($value);
                $GLOBALS['xarML_PHPBackend_keyEntries'][$key] = $value;
            }
        }
    }
*/
    function loadContext($ctxType, $ctxName)
    {
        if (!$fileName = $this->findContext($ctxType, $ctxName)) {
//            $msg = xarML("Context type: #(1) and file name: #(2)", $ctxType, $ctxName);
//            throw new ContextNotFoundException?
//            return;
            return true;
        }
        // @todo do we need to wrap this into a try/catch construct?
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

?>
