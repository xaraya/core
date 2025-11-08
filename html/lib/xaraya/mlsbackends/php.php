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
    public $PHPBackend_entries = [];
    public $PHPBackend_keyEntries = [];

    public function __construct($locales)
    {
        parent::__construct($locales);
        $this->backendtype = "php";
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

    public function bindDomain($dnType = ixarMLS::DNTYPE_CORE, $dnName = 'xaraya')
    {
        if (parent::bindDomain($dnType, $dnName)) {
            return true;
        }
        // FIXME: I should comment it because it creates infinite loop
        // MLS -> xar::mod()->getBaseInfo -> xarDisplayableName -> xar::mod()->getFileInfo -> MLS
        // We don't use and don't translate KEYS files now,
        // but I will recheck this code in the menus clone
        //        if ($dnType == ixarMLS::DNTYPE_MODULE) {
        //            $this->loadKEYS($dnName);
        //        }
        return false;
    }

    public function loadContext($contextType, $contextName)
    {
        if (!$fileName = $this->findContext($contextType, $contextName)) {
            //            $msg = xar::mls()->translate("Context type: #(1) and file name: #(2)", $ctxType, $ctxName);
            //            throw new ContextNotFoundException?
            //            return;
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

    public function getContextNames($contextType)
    {
        $contextParts = xarMLSContext::getContextTypeComponents($contextType);

        // Complete the directory path if the context directory is not empty
        if (!empty($contextParts[1])) {
            $this->contextlocation = $this->domainlocation . "/" . $contextParts[1];
        }

        $contextNames = [];
        if (!file_exists($this->contextlocation)) {
            return $contextNames;
        }
        $dd = opendir($this->contextlocation);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.php$/', $fileName, $matches)) {
                continue;
            }
            $contextNames[] = $matches[1];
        }
        closedir($dd);
        return $contextNames;
    }
}
