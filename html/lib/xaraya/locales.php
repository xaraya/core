<?php

/**
 * Locales (Multi Language System)
 *
 * @package core\multilanguage
 * @subpackage multilanguage
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Roger Raymond <roger@asphyxia.com>
**/

use Xaraya\Services\MultiLanguageService;
use Xaraya\Services\xar;

/**
 * Exception raised by the multilanguage subsystem
**/
class LocaleNotFoundException extends NotFoundExceptions
{
    protected $message = 'The locale "#(1)" could not be found or is currently unavailable';
}

// MLS CLASSES

/**
 * This class loads a valid locale descriptor XML file and returns its content
 * in the form of a locale data array
 * @throws  XMLParseException
 */
class xarMLS__LocaleDataLoader extends xarObject
{
    public $curData;
    public $curPath;

    public $parser;

    public $localeData;

    public $attribsStack = [];

    public $tmpVars;

    public function load($locale)
    {
        $fileName = sys::varpath() . "/locales/$locale/locale.xml";
        if (!file_exists($fileName)) {
            return false;
        }

        if (filesize($fileName) == 0) {
            return false;
        }

        $this->tmpVars = [];

        $this->curData = '';
        $this->curPath = '';
        $this->localeData = [];

        // TRICK: <marco> Since this xml parser sucks, we obviously use utf-8 for utf-8 charset
        // and iso-8859-1 for other charsets, even if they're not single byte.
        // The only important thing here is to split utf-8 from other charsets.
        $charset = xarLocale::getCharsetFromLocale($locale);
        // FIXME: <marco> try, re-try and re-re-try this!
        if ($charset == 'utf-8') {
            $this->parser = xml_parser_create('utf-8');
        } else {
            $this->parser = xml_parser_create('iso-8859-1');
        }
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($this->parser, "beginElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        if (!($fp = fopen($fileName, 'r'))) {
            return false;
        }

        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                $errstr = xml_error_string(xml_get_error_code($this->parser));
                $line = xml_get_current_line_number($this->parser);
                throw new XMLParseException([$fileName,$line,$errstr]);
            }
        }

        xml_parser_free($this->parser);
        return true;
    }

    public function getLocaleData(): array
    {
        return $this->localeData;
    }

    public function beginElement($parser, $tag, $attribs)
    {
        if (strpos($tag, ':') !== false) {
            [$ns, $tag] = explode(':', $tag);
        }
        $this->attribsStack[] = $attribs;
        if (isset($this->tmpVars['calledOnce'])) {
            $this->curPath .= '/' . $tag;
        } else {
            // Avoid to get prefixed the /description to path
            $this->tmpVars['calledOnce'] = true;
        }
    }

    public function endElement($parser, $tag)
    {
        if (strpos($tag, ':') !== false) {
            [$ns, $tag] = explode(':', $tag);
        }
        $attribs = array_pop($this->attribsStack);
        $handler = $tag . 'TagHandler';
        if (method_exists($this, $handler)) {
            [$new_path, $value] = $this->$handler($this->curPath, $attribs, $this->curData);
        } else {
            $value = $this->curData;
            $new_path = $this->curPath;
        }
        if (is_array($value)) {
            foreach ($value as $add_path => $real_value) {
                $this->localeData[$new_path . '/' . $add_path] = $real_value;
            }
        } else {
            $this->localeData[$new_path] = $value;
        }
        $this->curPath = substr($this->curPath, 0, (-1 * strlen($tag)) - 1);

        $this->curData = '';
    }

    public function characterData($parser, $data)
    {
        // FIXME: <marco> consider to replace \n,\r with ''
        $this->curData .= trim($data);
    }

    public function maximumTagHandler($path, $attribs, $content)
    {
        return [$path, (int) $content];
    }

    public function minimumTagHandler($path, $attribs, $content)
    {
        return [$path, (int) $content];
    }
    /**
     * @return array<mixed>
     */
    public function groupingSizeTagHandler($path, $attribs, $content)
    {
        return [$path, (int) $content];
    }

    public function isDecimalSeparatorAlwaysShownTagHandler($path, $attribs, $content)
    {
        if ($content == 'true') {
            $value = true;
        } else {
            $value = false;
        }
        return [$path, $value];
    }
    /**
     * @return array<mixed>
     */
    public function monthTagHandler($path, $attribs, $content)
    {
        if (isset($this->tmpVars['monthNum'])) {
            $monthNum = $this->tmpVars['monthNum'];
        } else {
            $monthNum = 1;
        }
        $this->tmpVars['monthNum'] = $monthNum + 1;
        $path = substr($path, 0, -6); // Strip the /month at the end
        $value = [$monthNum . '/full' => $attribs['full'],
            $monthNum . '/short' => $attribs['short']];
        return [$path, $value];
    }
    /**
     * @return array<mixed>
     */
    public function weekdayTagHandler($path, $attribs, $content)
    {
        if (isset($this->tmpVars['weekdayNum'])) {
            $weekdayNum = $this->tmpVars['weekdayNum'];
        } else {
            $weekdayNum = 1;
        }
        $this->tmpVars['weekdayNum'] = $weekdayNum + 1;
        $path = substr($path, 0, -8); // Strip the /weekday at the end
        $value = [$weekdayNum . '/full' => $attribs['full'],
            $weekdayNum . '/short' => $attribs['short']];
        return [$path, $value];
    }

}

/**
 * xarLocale class
 * @todo move dependency on xar::mls()->getCurrentLocale() to caller?
**/
class xarLocale extends xarObject
{
    public static $dataLoader  = null;
    public static $dataCache   = [];
    public static $newEncoding = null;
    protected static ?MultiLanguageService $mlsService = null;

    protected static function mls(): MultiLanguageService
    {
        if (!isset(self::$mlsService)) {
            $xar = xar::getServicesClass();
            self::$mlsService = $xar->mls();
        }
        return self::$mlsService;
    }

    /**
     * Gets the locale data for a certain locale.
     * Locale data is an associative array, its keys are described at the top
     * of this file
     *
     * @return array<mixed>|bool|null locale data
     * @throws LocaleNotFoundException
     * @todo   figure out why we go through this function for xar::mod()->isAvailable
     */
    public static function &loadData($locale = null)
    {
        static $loaded = []; // keep track of files we have loaded
        if (!isset($locale)) {
            $locale = self::mls()->getCurrentLocale();
        }

        // rraymond : move the check for the loaded locale before processing as
        //          : all of this would have been taken care of the first time
        //          : the locale data was loaded - saves processing time
        if (isset(self::$dataCache[$locale])) {
            return  self::$dataCache[$locale];
        }

        // check for locale availability
        $siteLocales = self::mls()->listSiteLocales();

        $nullreturn = null;
        $falsereturn = false;
        if (!in_array($locale, $siteLocales)) {
            if (strstr($locale, 'ISO')) {
                $locale = str_replace('ISO', 'iso', $locale);
                if (!in_array($locale, $siteLocales)) {
                    throw new LocaleNotFoundException($locale);
                }
            } else {
                throw new LocaleNotFoundException($locale);
            }
        }

        // @todo get rid of invalid .php locale files
        $fileName = sys::varpath() . "/locales/$locale/locale.php";
        if (!$parsedLocale = self::parseLocaleString($locale)) {
            return false;
        }
        $siteCharset = $parsedLocale['charset'];
        $utf8locale = $parsedLocale['lang'] . '_' . $parsedLocale['country'] . '.utf-8';
        // @todo get rid of invalid .php locale files
        $utf8FileName = sys::varpath() . "/locales/$utf8locale/locale.php";
        if (file_exists($fileName) && !(isset($loaded[$fileName]))) {
            // @todo do we need to wrap this in a try/catch construct?
            include $fileName;
            $loaded[$fileName] = true;
            /** @phpstan-ignore-next-line */
            self::$dataCache[$locale] = $localeData;
        } elseif (file_exists($utf8FileName) && !isset($loaded[$utf8FileName])) {
            include $utf8FileName;
            $loaded[$utf8FileName] = true;
            if ($siteCharset != 'utf-8') {
                self::$newEncoding ??= new xarCharset();
                /** @phpstan-ignore-next-line */
                foreach ($localeData as $tempKey => $tempValue) {
                    $tempValue = self::$newEncoding->convert($tempValue, 'utf-8', $siteCharset, 0);
                    $localeData[$tempKey] = $tempValue;
                }
            }
            self::$dataCache[$locale] = $localeData;
        } else {
            if (!$parsedLocale = self::parseLocaleString($locale)) {
                return $falsereturn;
            }
            $utf8locale = $parsedLocale['lang'] . '_' . $parsedLocale['country'] . '.utf-8';
            $siteCharset = $parsedLocale['charset'];
            self::$dataLoader ??= new xarMLS__LocaleDataLoader();
            $res =  self::$dataLoader->load($utf8locale);
            if (isset($res) && $res == false) {
                throw new LocaleNotFoundException($utf8locale);
            }
            if (!isset($res)) {
                return $nullreturn;
            } // Throw back
            $tempArray =  self::$dataLoader->getLocaleData();
            if ($siteCharset != 'utf-8') {
                self::$newEncoding ??= new xarCharset();
                foreach ($tempArray as $tempKey => $tempValue) {
                    $tempValue = self::$newEncoding->convert($tempValue, 'utf-8', $siteCharset, 0);
                    $tempArray[$tempKey] = $tempValue;
                }
            }
            self::$dataCache[$locale] = $tempArray;
        }

        return  self::$dataCache[$locale];
    }

    /**
     * Parses a string as a currency amount according to specified locale data
     */
    public static function parseCurrency($currency, $localeData = null)
    {
        return self::mls()->getFormatter()->parseCurrency($currency);
    }

    /**
     * Parses a string as a number according to specified locale data
     */
    public static function parseNumber($number, $localeData = null, $isCurrency = false)
    {
        return self::mls()->getFormatter()->parseNumber($number, $isCurrency);
    }

    /**
     * Formats a currency according to specified locale data
     */
    public static function formatCurrency($currency, $localeData = null)
    {
        return self::mls()->getFormatter()->formatCurrency($currency);
    }

    /**
     * Formats a number according to specified locale data
     */
    public static function formatNumber($number, $localeData = null, $isCurrency = false)
    {
        return self::mls()->getFormatter()->formatNumber($number, $isCurrency);
    }

    /**
     * Wrapper to xarLocale::getFormattedDate without timezone offset
     */
    public static function getFormattedUTCDate($length = 'short', $timestamp = null, $addoffset = false)
    {
        return self::mls()->getFormatter()->getFormattedUTCDate($length, $timestamp, $addoffset);
    }

    /**
     *  Grab the formated date by the user's current locale settings
     *
     * @param string $length what date locale we want (short|medium|long)
     * @param int $timestamp optional unix timestamp in UTC to format
     * @param bool $addoffset add user timezone offset (default true)
     * @todo Check the exceptions when $length is not in the $validlengths (assert on it?)
     */
    public static function getFormattedDate($length = 'short', $timestamp = null, $addoffset = true)
    {
        return self::mls()->getFormatter()->getFormattedDate($length, $timestamp, $addoffset);
    }

    /**
     * Wrapper to xarLocale::getFormattedTime without timezone offset
     */
    public static function getFormattedUTCTime($length = 'short', $timestamp = null, $addoffset = false)
    {
        return self::mls()->getFormatter()->getFormattedUTCTime($length, $timestamp, $addoffset);
    }

    /**
     * Grab the formated time by the user's current locale settings
     *
     * @param string $length what time locale we want (short|medium|long)
     * @param int $timestamp optional unix timestamp in UTC to format
     * @param bool $addoffset add user timezone offset (default true)
     * @todo MichelV: why are the formatting rules not the same as PHP rules for strftime?
     */
    public static function getFormattedTime($length = 'short', $timestamp = null, $addoffset = true)
    {
        return self::mls()->getFormatter()->getFormattedTime($length, $timestamp, $addoffset);
    }

    /**
     * Wrapper to xarLocale::formatDate without timezone offset
     */
    public static function formatUTCDate($format = null, $time = null, $addoffset = false)
    {
        return self::mls()->getFormatter()->formatUTCDate($format, $time, $addoffset);
    }

    /**
     * Format a date/time according to the current locale (and/or user's preferences)
     *
     * @param string $format strftime() format to use (TODO: default locale-dependent or configurable ?)
     * @param mixed $timestamp or date string (default now)
     * @param bool $addoffset add user timezone offset (default true)
     */
    public static function formatDate($format = null, $timestamp = null, $addoffset = true)
    {
        return self::mls()->getFormatter()->formatDate($format, $timestamp, $addoffset);
    }

    /**
     *  Used in place of strftime() for locale translation.
     *  This function uses gmstrftime() so it should be passed
     *  a timestamp that has been modified for the user's current
     *  timezone setting.
     *
     *  // supported strftime() format rules
     *  %a - abbreviated weekday name according to the current locale
     *  %A - full weekday name according to the current locale
     *  %b - abbreviated month name according to the current locale
     *  %B - full month name according to the current locale
     *  %c - preferred date and time representation for the current locale
     *  %D - same as %m/%d/%y (abbreviated date according to locale)
     *  %h - same as %b
     *  %p - either `am' or `pm' according to the given time value, or the corresponding strings for the current locale
     *  %r - time in a.m. and p.m. notation
     *  %R - time in 24 hour notation (for windows compatibility)
     *  %T - current time, equal to %H:%M:%S (for windows compatibility)
     *  %x - preferred date representation for the current locale without the time (same at %D)
     *  %X - preferred time representation for the current locale without the date
     *  %e - day of the month as a decimal number, a single digit is preceded by a space (range ' 1' to '31')
     *
     *  @todo unsupported strftime() format rules
     *  %Z - time zone or name or abbreviation - we should use the user or site's info for this
     *  %z - time zone or name or abbreviation - we should use the user or site's info for this
     *  @param string $format valid format params from strftime() function\
     *  @param int $timestamp optional unix timestamp to translate
     *  @return string datetime string with locale translations
     */
    public static function strftime($format = null, $timestamp = null)
    {
        return self::mls()->getFormatter()->strftime($format, $timestamp);
    }

    /**
     * Parses a locale string into an associative array composed of
     * lang, country, specializer and charset keys
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> parsed locale
     */
    public static function parseLocaleString($locale)
    {
        $res = ['lang' => '', 'country' => '', 'specializer' => '', 'charset' => 'utf-8'];
        // Match the locales standard format  : en_US.iso-8859-1
        // Thus: language code lowercase(2), country code uppercase(2), encoding lowercase(1+)
        if (!preg_match('/([a-z][a-z])(_([A-Z][A-Z]))?(\.([0-9a-z\-]+))?(@([0-9a-zA-Z]+))?/', $locale, $matches)) {
            throw new BadParameterException('locale');
        }

        $res['lang'] = $matches[1];
        if (!empty($matches[3])) {
            $res['country'] = $matches[3];
        }
        if (!empty($matches[5])) {
            $res['charset'] = $matches[5];
        }
        if (!empty($matches[7])) {
            $res['specializer'] = $matches[7];
        }

        return $res;
    }

    /**
     * Gets the charset component from a locale
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string|void the charset name
     */
    public static function getCharsetFromLocale($locale)
    {
        if (!$parsedLocale = self::parseLocaleString($locale)) {
            return;
        } // throw back
        return $parsedLocale['charset'];
    }

    /**
     * Gets a list of alternatives for a certain locale.
     * The first alternative is the locale itself
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed>|void alternative locales
     */
    public static function getLocaleAlternatives($locale)
    {
        if (!$parsedLocale = self::parseLocaleString($locale)) {
            return;
        } // throw back
        extract($parsedLocale); // $lang, $country, $charset
        /** @var string $lang */
        /** @var string $country */
        /** @var string $charset */

        $alternatives = [$locale];
        if (!empty($country) && !empty($specializer)) {
            $alternatives[] = $lang . '_' . $country . '.' . $charset;
        }
        if (!empty($country) && empty($specializer)) {
            $alternatives[] = $lang . '.' . $charset;
        }

        return $alternatives;
    }

    /**
     * Gets the locale string for the specified locale info.
     * Info is an array composed by the 'lang', 'country', 'specializer' and 'charset' items.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @throws BadParameterException
     * @return string locale string
     */
    public static function getLocaleString($localeInfo)
    {
        if (!isset($localeInfo['lang'])
            || !isset($localeInfo['country'])
            || !isset($localeInfo['specializer'])
            || !isset($localeInfo['charset'])) {
            throw new BadParameterException('localeInfo');
        }
        if (strlen($localeInfo['lang']) != 2) {
            throw new BadParameterException('localeInfo');
        }

        $locale = strtolower($localeInfo['lang']);
        if (!empty($localeInfo['country'])) {
            if (strlen($localeInfo['country']) != 2) {
                throw new BadParameterException('localeInfo');
            }

            $locale .= '_' . strtoupper($localeInfo['country']);
        }
        if (!empty($localeInfo['charset'])) {
            $locale .= '.' . $localeInfo['charset'];
        } else {
            $locale .= '.utf-8';
        }
        if (!empty($localeInfo['specializer'])) {
            $locale .= '@' . $localeInfo['specializer'];
        }
        return $locale;
    }

    /**
     * Gets a list of locale string which met the specified filter criteria.
     * Filter criteria are set as item of $filter parameter, they can be one or more of the following:
     * lang, country, specializer, charset.
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return array<mixed> locale list
     */
    public static function filterLocaleList($locales = [], $filter = [])
    {
        $list = [];
        foreach ($locales as $locale) {
            $l = self::parseLocaleString($locale);
            if (isset($filter['lang']) && $filter['lang'] != $l['lang']) {
                continue;
            }
            if (isset($filter['country']) && $filter['country'] != $l['country']) {
                continue;
            }
            if (isset($filter['specializer']) && $filter['specializer'] != $l['specializer']) {
                continue;
            }
            if (isset($filter['charset']) && $filter['charset'] != $l['charset']) {
                continue;
            }
            $list[] = $locale;
        }
        return $list;
    }

    /**
     * Gets the single byte charset most typically used in the Web for the
     * requested language
     *
     * @author Marco Canini <marco@xaraya.com>
     * @return string the charset
     * @todo   Dont hardcode this
     * @deprecated 2.4.1 not used
     */
    // CHECKME: is this used anywhere?
    public static function getSingleByteCharset($langISO2Code)
    {
        static $charsets = [
            'af' => 'iso-8859-1', 'sq' => 'iso-8859-1',
            'ar' => 'iso-8859-6',  'eu' => 'iso-8859-1',  'bg' => 'iso-8859-5',
            'be' => 'iso-8859-5',  'ca' => 'iso-8859-1',  'hr' => 'iso-8859-2',
            'cs' => 'iso-8859-2',  'da' => 'iso-8859-1',  'nl' => 'iso-8859-1',
            'en' => 'iso-8859-1',  'eo' => 'iso-8859-3',  'et' => 'iso-8859-15',
            'fo' => 'iso-8859-1',  'fi' => 'iso-8859-1',  'fr' => 'iso-8859-1',
            'gl' => 'iso-8859-1',  'de' => 'iso-8859-1',  'el' => 'iso-8859-7',
            'iw' => 'iso-8859-8',  'hu' => 'iso-8859-2',  'is' => 'iso-8859-1',
            'ga' => 'iso-8859-1',  'it' => 'iso-8859-1',  //'ja' => '',
            'lv' => 'iso-8859-13', 'lt' => 'iso-8859-13', 'mk' => 'iso-8859-5',
            'mt' => 'iso-8859-3',  'no' => 'iso-8859-1',  'pl' => 'iso-8859-2',
            'pt' => 'iso-8859-1',  'ro' => 'iso-8859-2',  'ru' => 'windows-1251',
            'gd' => 'iso-8859-1',  'sr' => 'iso-8859-2',  'sk' => 'iso-8859-2',
            'sl' => 'iso-8859-2',  'es' => 'iso-8859-1',  'sv' => 'iso-8859-1',
            'tr' => 'iso-8859-9',  'uk' => 'iso-8859-5',
        ];

        return @$charsets[$langISO2Code];
    }
}
