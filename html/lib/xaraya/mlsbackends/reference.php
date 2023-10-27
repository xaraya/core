<?php
/**
 * @package core\multilanguage
 * @subpackage multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Translations backend interface
 *.
 * It defines a simple interface used by the Multi Language System to fetch both
 * string and key based translations. Each MLS backend must implement this interface.
 *
 * @todo    interface once php5 is there - yep, it's here :-)
 */
interface ITranslationsBackend {
    // Get the string based translation associated to the string param.
    function translate($string);

    // Get the key based translation associated to the key param.
    function translateByKey($key);

    // Unload loaded translations.
    function clear();

    // Bind the backend to the specified domain.
    function bindDomain($dnType, $dnName='xaraya');

    // Check if this backend supports a specified translation context.
    function hasContext($ctxType, $ctxName);

    // Load a set of translations into the backend.
    function loadContext($ctxType, $ctxName);

    // Get available context names for the specified context type
    function getContextNames($ctxType);
}

/**
 * @package core\multilanguage
 * @subpackage multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Base class for the translation backends
 *
 * A translation entry is an array that contains not only the translation,
 * but also the a list of references where it appears in the source by
 * reporting the file name and the line number.
 *
 * @throws Exception, BadParameterException
 */
abstract class xarMLS__ReferencesBackend  extends xarObject implements ITranslationsBackend
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

    function bindDomain($dnType=xarMLS::DNTYPE_CORE, $dnName='xaraya')
    {
        // only bind each domain once (?)
        //if (isset($this->domaincache["$dnType.$dnName"])) {
        // CHECKME: make sure we can cache this (e.g. set $this->domainlocation here first ?)
        //    return $this->domaincache["$dnType.$dnName"];
        //}

        foreach ($this->locales as $locale) {
            $this->domainlocation = xarMLSContext::getDomainPath($dnType, $locale, $this->backendtype, $dnName);

            if (file_exists($this->domainlocation)) {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            } elseif (xarMLS::$backendName == 'xml2php') {
                $this->locale = $locale;
                // CHECKME: save $this->domainlocation here instead ?
                //$this->domaincache["$dnType.$dnName"] = true;
                return true;
            }
        }

        //$this->domaincache["$dnType.$dnName"] = false;
        return false;
    }
    /**
     * @return string|null
     */
    function getDomainLocation()
    { return $this->domainlocation; }

    function getContextLocation()
    { return $this->contextlocation; }

    function hasContext($ctxType, $ctxName)
    {
        return $this->findContext($ctxType, $ctxName) != false;
    }

    function findContext($contextType, $contextName)
    {
        // Start with the domain location
        $fileName = $this->getDomainLocation();
        
        // Add in a context directory if it is not empty
        $contextParts = xarMLSContext::getContextTypeComponents($contextType);
        if (!empty($contextParts[1])) $fileName .= "/" . $contextParts[1];
        
        // Add the file name and its extension
        $fileName .= "/" . $contextName . "." . $this->backendtype;
        
        // Remove any stray slashes
        $fileName = str_replace('//','/',$fileName);
        if (!file_exists($fileName)) {
            return false;
        }
        return $fileName;
    }

}
