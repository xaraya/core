<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Multi Language System - XML Translations Backend
// ----------------------------------------------------------------------

/**
 * Implements a concrete translations backend based on the XML language.
 * All xml files are encoded in UTF-8. This backend is useful only when
 * running Xaraya in the multi-language mode (UTF-8).
 */
class xarMLS__XMLTranslationsBackend extends xarMLS__ReferencesBackend
{
    var $curEntry;
    var $curData;

    var $parser;

    var $trans = array(); // where translations are kept
    var $transEntries = array(); // mapping for string-based translations
    var $transKeyEntries = array(); // mapping for key-based translations

    var $transInd = 0;
    var $transKeyInd = 0;

    var $locales;

    function xarMLS__XMLTranslationsBackend($locales)
    {
        $this->locales = $locales;
    }

    function translate($string)
    {
        if (!isset($this->transEntries[$string])) {
            return;
        }
        $ind = $this->transEntries[$string];
        return $this->trans[$ind]['translation'];
    }

    function translateByKey($key)
    {
        if (!isset($this->transKeyEntries[$key])) {
            return;
        }
        $ind = $this->transKeyEntries[$key];
        return $this->trans[$ind]['translation'];
    }

    function clear()
    {
        $this->trans = array();
        $this->transEntries = array();
        $this->transKeyEntries = array();
        $this->transInd = 0;
        $this->transKeyInd = 0;
    }

    function bindDomain($dnType, $dnName)
    {
        $varDir = xarCoreGetVarDirPath();
        switch ($dnType) {
            case XARMLS_DNTYPE_MODULE:
            $dirName = "modules/$dnName/";
            break;
            case XARMLS_DNTYPE_THEME:
            $dirName = "themes/$dnName/";
            break;
            case XARMLS_DNTYPE_CORE:
            $dirName = 'core/';
        }
        foreach ($this->locales as $locale) {
            $this->baseDir = "$varDir/locales/$locale/xml/$dirName";
            if (file_exists($this->baseDir)) return true;
        }
        return false;
    }

    function findContext($ctxType, $ctxName)
    {
        switch ($ctxType) {
            case XARMLS_CTXTYPE_FILE:
            $fileName = $ctxName;
            break;
            case XARMLS_CTXTYPE_TEMPLATE:
            $fileName = "templates/$ctxName";
            break;
            case XARMLS_CTXTYPE_BLOCK:
            $fileName = "blocks/$ctxName";
            break;
        }
        $fileName .= '.xml';
        if (!file_exists($this->baseDir.$fileName)) return false;
        return $this->baseDir.$fileName;
    }

    function hasContext($ctxType, $ctxName)
    {
        return $this->findContext($ctxType, $ctxName) != false;
    }

    function loadContext($ctxType, $ctxName)
    {
        $this->curData = '';

        $this->parser = xml_parser_create('UTF-8');
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        if (!$fileName = $this->findContext($ctxType, $ctxName)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'CONTEXT_NOT_FOUND', new SystemException($ctxType.': '.$ctxName));
            return;
        }
        $fp = fopen($fileName, 'r');

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                // NOTE: <marco> Of course don't use xarML here!
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'TRANSLATION_EXCEPTION',
                                new SystemException("XML parser error in $fileName: $errstr at line $line."));
                return;
            }
        }

        xml_parser_free($this->parser);

        return true;
    }

    function getContextNames($ctxType)
    {
        $dirName = $this->baseDir;
        switch ($ctxType) {
            case XARMLS_CTXTYPE_TEMPLATE:
            $dirName .= 'templates';
            break;
            case XARMLS_CTXTYPE_BLOCK:
            $dirName .= 'blocks';
            break;
        }
        $ctxNames = array();
        $dd = opendir($dirName);
        while ($fileName = readdir($dd)) {
            if (!preg_match('/^(.+)\.xml$/', $fileName, $matches)) continue;
            $ctxNames[] = $matches[1];
        }
        closedir($dd);
        return $ctxNames;
    }

    function getEntry($string)
    {
        if (!isset($this->transEntries[$string])) {
            return;
        }
        $ind = $this->transEntries[$string];
        return $this->trans[$ind];
    }

    function getEntryByKey($key)
    {
        if (!isset($this->transKeyEntries[$key])) {
            return;
        }
        $ind = $this->transKeyEntries[$key];
        return $this->trans[$ind];
    }

    function getTransientId($string)
    {
        if (!isset($this->transEntries[$string])) {
            return;
        }
        return $this->transEntries[$string];
    }

    function lookupTransientId($transientId)
    {
        if (!isset($this->trans[(int) $transientId])) {
            return;
        }
        return $this->trans[(int) $transientId];
    }

    function enumTranslations($reset = false)
    {
        if ($reset == true) {
            $this->transInd = 0;
        }
        $count = count($this->trans);
        if ($this->transInd == $count) {
            return false;
        }
        while ($this->transInd < $count) {
            if (isset($this->trans[$this->transInd]['string'])) {
                $res = array($this->trans[$this->transInd]['string'], $this->trans[$this->transInd]['translation']);
                $this->transInd++;
                return $res;
            }
            $this->transInd++;
        }
        return false;
    }

    function enumKeyTranslations($reset = false)
    {
        if ($reset == true) {
            $this->transKeyInd = 0;
        }
        $count = count($this->trans);
        if ($this->transKeyInd == $count) {
            return false;
        }
        while ($this->transKeyInd < $count) {
            if (isset($this->trans[$this->transKeyInd]['key'])) {
                $res = array($this->trans[$this->transKeyInd]['key'], $this->trans[$this->transKeyInd]['translation']);
                $this->transKeyInd++;
                return $res;
            }
            $this->transKeyInd++;
        }
        return false;
    }

    function beginElement($parser, $tag, $attribs)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        if ($tag == 'entry' || $tag == 'keyEntry') {
            $this->curEntry = array();
            $this->curEntry['references'] = array();
        } elseif ($tag == 'reference') {
            $reference['file'] = $attribs['file'];
            $reference['line'] = $attribs['line'];
            $this->curEntry['references'][] = $reference;
        }
        /*elseif ($tag == 'original') {
            $this->curEntry['original'] = array();
            $this->curEntry['original']['file'] = $attribs['file'];
            $this->curEntry['original']['xpath'] = $attribs['xpath'];
        }*/
    }

    function endElement($parser, $tag)
    {
        if (strpos($tag, ':') !== false) {
            list($ns, $tag) = explode(':', $tag);
        }
        if ($tag == 'entry') {
            $string = $this->curEntry['string'];
            $this->trans[] = $this->curEntry;
            $this->transEntries[$string] = count($this->trans) - 1;
        } elseif ($tag == 'keyEntry') {
            $key = $this->curEntry['key'];
            $this->trans[] = $this->curEntry;
            $this->transKeyEntries[$key] = count($this->trans) - 1;
        } elseif ($tag == 'string') {
            $this->curEntry['string'] = trim($this->curData);
            //$this->curEntry['string'] = utf8_decode(trim($this->curData));
        } elseif ($tag == 'key') {
            $this->curEntry['key'] = trim($this->curData);
        } elseif ($tag == 'translation') {
            $this->curEntry['translation'] = trim($this->curData);
            //$this->curEntry['translation'] = utf8_decode(trim($this->curData));
        }
        $this->curData = '';
    }

    function characterData($parser, $data)
    {
        // FIXME <marco> consider to replace \n,\r with ''
        $this->curData .= $data;
    }

}

?>
