<?php
/**
 * Translations backend interface
 *.
 * It defines a simple interface used by the Multi Language System to fetch both
 * string and key based translations. Each MLS backend must implement this interface.
 *
 * @package multilanguage
 * @todo    interface once php5 is there
 */
interface ITranslationsBackend {
    // Get the string based translation associated to the string param.
    function translate($string);

    // Get the key based translation associated to the key param.
    function translateByKey($key);

    // Unload loaded translations.
    function clear();

    // Bind the backend to the specified domain.
    function bindDomain($dnType, $dnName);

    // Check if this backend supports a scpecified translation context.
    function hasContext($ctxType, $ctxName);

    // Load a set of translations into the backend.
    function loadContext($ctxType, $ctxName);

    // Get available context names for the specified context type
    function getContextNames($ctxType);
}

/**
 * Base class for the translation backends
 *
 * A translation entry is an array that contains not only the translation,
 * but also the a list of references where it appears in the source by
 * reporting the file name and the line number.
 *
 * @package multilanguage
 * @throws Exception, BadParameterException
 */
abstract class xarMLS__ReferencesBackend  extends Object implements ITranslationsBackend
{
    public $locales;
    public $locale;
    public $domainlocation;
    public $contextlocation;
    public $backendtype;
    public $space;
    public $spacedir;
    public $domaincache;

    function __construct($locales)
    {
        $this->locales = $locales;
        $this->domaincache = array();
    }
    /**
     * Gets a translation entry for a string based translation.
     */
    function getEntry($string)
    { throw new Exception('method is abstract? (todo)'); }

    /**
     * Gets a translation entry for a key based translation.
     */
    function getEntryByKey($key)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Gets a transient identifier (integer) that is guaranteed to identify
     * the translation entry for the string based translation in the next HTTP request.
     */
    function getTransientId($string)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Gets the translation entry identified by the passed transient identifier.
     */
    function lookupTransientId($transientId)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Enumerates every string based translation, use the reset param to restart the enumeration.
     */
    function enumTranslations($reset = false)
    { throw new Exception('method is abstract? (todo)'); }
    /**
     * Enumerates every key based translation, use the reset param to restart the enumeration.
     */
    function enumKeyTranslations($reset = false)
    { throw new Exception('method is abstract? (todo)'); }

    // ITranslationsBackend Interface
    // These have no common part:
    //abstract function translate($string);
    //abstract function translateByKey($key);
    //abstract function clear();
    //abstract function loadContext($ctxType, $ctxName);
    //abstract function getContextNames($ctxType);

    function bindDomain($dnType, $dnName)
    {
        // only bind each domain once (?)
        //if (isset($this->domaincache["$dnType.$dnName"])) {
        // CHECKME: make sure we can cache this (e.g. set $this->domainlocation here first ?)
        //    return $this->domaincache["$dnType.$dnName"];
        //}

        switch ($dnType) {
        case XARMLS_DNTYPE_MODULE:
            $this->spacedir = "modules";
            break;
        case XARMLS_DNTYPE_THEME:
            $this->spacedir = "themes";
            break;
        case XARMLS_DNTYPE_CORE:
            $this->spacedir = "core";
            break;
        default:
            $this->spacedir = NULL;
            break;
        }

        foreach ($this->locales as $locale) {
            if($this->spacedir == "core" || $this->spacedir == "xaraya") {
                $this->domainlocation  = sys::varpath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir;
            } else {
                $this->domainlocation  = sys::varpath() . "/locales/"
                . $locale . "/" . $this->backendtype . "/" . $this->spacedir . "/" . $dnName;
            }

            if (file_exists($this->domainlocation)) {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            } elseif ($GLOBALS['xarMLS_backendName'] == 'xml2php') {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            }
        }

        //$this->domaincache["$dnType.$dnName"] = false;
        return false;
    }

    function getDomainLocation()
    { return $this->domainlocation; }

    function getContextLocation()
    { return $this->contextlocation; }

    function hasContext($ctxType, $ctxName)
    {
        return $this->findContext($ctxType, $ctxName) != false;
    }

    function findContext($ctxType, $ctxName)
    {
        if (strpos($ctxType, 'modules:') !== false) {
            list ($ctxPrefix,$ctxDir) = explode(":", $ctxType);
            $fileName = $this->getDomainLocation() . "/$ctxDir/$ctxName." . $this->backendtype;
        } elseif (strpos($ctxType, 'themes:') !== false) {
            list ($ctxPrefix,$ctxDir) = explode(":", $ctxType);
            $fileName = $this->getDomainLocation() . "/$ctxDir/$ctxName." . $this->backendtype;
        } elseif (strpos($ctxType, 'core:') !== false) {
            $fileName = $this->getDomainLocation() . "/". $ctxName . "." . $this->backendtype;
        } else {
            throw new BadParameterException(array('context',$ctxType));
        }
        $fileName = str_replace('//','/',$fileName);
        if (!file_exists($fileName)) {
//            throw new FileNotFoundException($fileName);
            return false;
        }
        return $fileName;
    }

}
?>