<?php

/**
 * Locale Formatter for MultiLanguage System
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.6
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Tools\Legacy;
use XarDateTime;
use xarLocale;

class LocaleFormatter
{
    /** @var array<mixed> */
    protected array $localeData = [];
    protected string $locale = '';
    protected string $timezone = '';

    public function __construct(string $locale, string $timezone)
    {
        $this->locale = $locale ?: 'en_US.utf-8';
        $this->localeData = & xarLocale::loadData($locale);
        $this->timezone = $timezone;
    }

    /**
     * Parses a string as a currency amount according to specified locale data
     */
    public function parseCurrency(string $currency): string
    {
        $currencySym = $this->localeData['/monetary/currencySymbol'];
        $currency = str_replace($currencySym, '', $currency);
        $currency = $this->parseNumber($currency, true);
        return trim($currency);
    }

    /**
     * Parses a string as a number according to specified locale data
     */
    public function parseNumber(string $number, bool $isCurrency = false): string
    {
        if ($isCurrency) {
            $bp = 'monetary';
        } else {
            $bp = 'numeric';
        }

        $groupSep = $this->localeData["/$bp/groupingSeparator"];
        $number = str_replace($groupSep, '', $number);
        return trim($number);
    }

    /**
     * Formats a currency according to specified locale data
     */
    public function formatCurrency(mixed $currency): string
    {
        $currencySym = $this->localeData['/monetary/currencySymbol'];
        return $currencySym . ' ' . $this->formatNumber($currency, true);
    }

    /**
     * Formats a number according to specified locale data
     */
    public function formatNumber(mixed $number, bool $isCurrency = false): string
    {
        if (!is_numeric($number)) {
            $number = (float) $number;
        }

        if ($isCurrency) {
            $bp = 'monetary';
        } else {
            $bp = 'numeric';
        }

        $groupSize = $this->localeData["/$bp/groupingSize"];
        $groupSep = $this->localeData["/$bp/groupingSeparator"];
        $decSep = $this->localeData["/$bp/decimalSeparator"];
        $decSepShown = $this->localeData["/$bp/isDecimalSeparatorAlwaysShown"];
        $maxFractDigits = $this->localeData["/$bp/fractionDigits/maximum"];
        $minFractDigits = $this->localeData["/$bp/fractionDigits/minimum"];

        $zeroDigit = $this->localeData['/decimalSymbols/zeroDigit'];
        $minusSign = $this->localeData['/decimalSymbols/minusSign'];

        if ($number < 0) {
            $number = -1 * $number;
            $minus = true;
        }

        $str_num = (string) $number; // Convert to string

        if (($dsep_pos = strpos($str_num, '.')) !== false) {
            $int_part = substr($str_num, 0, $dsep_pos);
            $dec_part = substr($str_num, $dsep_pos + 1);
        } else {
            $int_part = $str_num;
        }
        // FIXME: <marco> Do we really need the maximum integer digits?
        $int_part_len = strlen($int_part);
        if ($groupSize > 0) {
            $sepNum = (int) ($int_part_len / $groupSize);
            $firstSkip = $int_part_len - ($sepNum * $groupSize);

            $str_num = '';

            $pos = $firstSkip;
            while ($pos < $int_part_len) {
                $str_num .= $groupSep . substr($int_part, $pos, $groupSize);
                $pos += $groupSize;
            }
            if ($firstSkip > 0) {
                $str_num = substr($int_part, 0, $firstSkip) . $str_num;
            } else {
                $str_num = substr($str_num, 1);
            }
        } else {
            $str_num = $int_part;
        }

        if (isset($dec_part) || $decSepShown) {
            $str_num .= $decSep;
            if (!isset($dec_part)) {
                for ($i = 0; $i < $minFractDigits; $i++) {
                    $str_num .= '0';
                }
            } else {
                $dec_part_len = strlen($dec_part);
                if ($dec_part_len < $minFractDigits) {
                    for ($i = 0; $i < $minFractDigits - $dec_part_len; $i++) {
                        $dec_part .= '0';
                    }
                } elseif ($dec_part_len > $maxFractDigits) {
                    // FIXME: <marco> Do we need round here?
                    $dec_part = substr($dec_part, 0, $maxFractDigits - $dec_part_len); // Note negative length
                }
                $str_num .= $dec_part;
            }
        }

        if (isset($minus)) {
            $str_num = $minusSign . $str_num;
        }

        if ($zeroDigit != '0') {
            $str_num = str_replace('0', $zeroDigit, $str_num);
        }

        return $str_num;
    }

    /**
     * Wrapper to xarLocale::getFormattedDate without timezone offset
     */
    public function getFormattedUTCDate(string $length = 'short', ?int $timestamp = null, bool $addoffset = false): string
    {
        if (!isset($timestamp)) {
            // get UTC timestamp
            $timestamp = time();
        }

        // pass this to the regular function, but without using the timezone offset here
        return  $this->getFormattedDate($length, $timestamp, $addoffset);
    }

    /**
     *  Grab the formated date by the user's current locale settings
     *
     * @param string $length what date locale we want (short|medium|long)
     * @param int $timestamp optional unix timestamp in UTC to format
     * @param bool $addoffset add user timezone offset (default true)
     * @todo Check the exceptions when $length is not in the $validlengths (assert on it?)
     */
    public function getFormattedDate(string $length = 'short', ?int $timestamp = null, bool $addoffset = true): string
    {
        $length = strtolower($length);
        $validLengths = ['short','medium','long'];
        if (!in_array($length, $validLengths)) {
            //TODO: We should throw a USER exception here
            return '';
        }

        // @todo get rid of these double transformations
        // grab the right set of locale data
        $locale_format = $this->localeData["/dateFormats/$length"];
        // replace the locale formatting style with valid strftime() style
        $locale_format = str_replace('MMMM', '%B', $locale_format);
        $locale_format = str_replace('MMM', '%b', $locale_format);
        $locale_format = str_replace('M', '%m', $locale_format);
        $locale_format = str_replace('dddd', '%A', $locale_format);
        $locale_format = str_replace('ddd', '%a', $locale_format);
        $locale_format = str_replace('d', '%d', $locale_format);
        $locale_format = str_replace('yyyy', '%Y', $locale_format);
        $locale_format = str_replace('yy', '%y', $locale_format);

        return  $this->formatDate($locale_format, $timestamp, $addoffset);
    }

    /**
     * Wrapper to xarLocale::getFormattedTime without timezone offset
     */
    public function getFormattedUTCTime(string $length = 'short', ?int $timestamp = null, bool $addoffset = false): string
    {
        if (!isset($timestamp)) {
            // get UTC timestamp
            $timestamp = time();
        }

        // pass this to the regular function, but without using the timezone offset here
        return  $this->getFormattedTime($length, $timestamp, $addoffset);
    }

    /**
     * Grab the formated time by the user's current locale settings
     *
     * @param string $length what time locale we want (short|medium|long)
     * @param int $timestamp optional unix timestamp in UTC to format
     * @param bool $addoffset add user timezone offset (default true)
     * @todo MichelV: why are the formatting rules not the same as PHP rules for strftime?
     */
    public function getFormattedTime(string $length = 'short', ?int $timestamp = null, bool $addoffset = true): string
    {
        $length = strtolower($length);
        $validLengths = ['short','medium','long'];
        if (!in_array($length, $validLengths)) {
            return '';
        }

        if (empty($timestamp)) {
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if (isset($timestamp) && $timestamp === false) {
                return '';
            }
            if ($addoffset) {
                $timestamp = $this->userTime();
            } else {
                $timestamp = time();
            }
        } elseif ($timestamp >= 0) {
            if ($addoffset) {
                // adjust for the user's timezone offset
                $timestamp += $this->userOffset($timestamp) * 3600;
            }
        } else {
            // invalid dates < 0 (e.g. from strtotime) return an empty date string
            return '';
        }
        $addoffset = false;

        // @todo get rid of these double transformations
        // grab the right set of locale data
        $locale_format = $this->localeData["/timeFormats/$length"];
        // replace the locale formatting style with valid strftime() style

        $locale_format = str_replace('HH', '%H', $locale_format);
        $locale_format = str_replace('H', '%H', $locale_format); // Bug 5806
        $locale_format = str_replace('%%H', '%H', $locale_format); // Now put back the double replaced ones.
        $locale_format = str_replace('hh', '%I', $locale_format);
        $locale_format = str_replace('mm', '%M', $locale_format);
        $locale_format = str_replace('ss', '%S', $locale_format);
        $locale_format = str_replace('a', '%p', $locale_format);
        $locale_format = str_replace('z', '%Z', $locale_format);
        // format the single digit flags

        $datetime = date_create('@' . $timestamp);
        // H = %H = Two digit representation of the hour in 24-hour format
        if (strpos($locale_format, 'H') !== false) {
            $locale_format = str_replace('%H', sprintf('%1d', $datetime->format('H')), $locale_format);
        }
        // h = %I = Two digit representation of the hour in 12-hour format
        if (strpos($locale_format, 'h') !== false) {
            $locale_format = str_replace('h', sprintf('%1d', $datetime->format('h')), $locale_format);
        }
        // i = %M = Two digit representation of the minute
        if (strpos($locale_format, 'm') !== false) {
            $locale_format = str_replace('m', sprintf('%1d', $datetime->format('i')), $locale_format);
        }
        // s = %S = Two digit representation of the second
        if (strpos($locale_format, 's') !== false) {
            $locale_format = str_replace('s', sprintf('%1d', $datetime->format('s')), $locale_format);
        }

        return  $this->formatDate($locale_format, $timestamp, $addoffset);
    }

    /**
     * Wrapper to xarLocale::formatDate without timezone offset
     */
    public function formatUTCDate(?string $format = null, ?int $time = null, bool $addoffset = false): string
    {
        if (!isset($time)) {
            $time = time();
        }

        // pass this to the regular function, but without using the timezone offset here
        return  $this->formatDate($format, $time, $addoffset);
    }

    /**
     * Format a date/time according to the current locale (and/or user's preferences)
     *
     * @param string $format strftime() format to use (TODO: default locale-dependent or configurable ?)
     * @param mixed $timestamp or date string (default now)
     * @param bool $addoffset add user timezone offset (default true)
     */
    public function formatDate(?string $format = null, ?int $timestamp = null, bool $addoffset = true): string
    {
        // CHECKME: should we default to current time only when timestamp is not set at all ?
        //if (!isset($timestamp)) {
        if (empty($timestamp)) {
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if (isset($timestamp) && $timestamp === false) {
                return '';
            }
            if ($addoffset) {
                $timestamp = $this->userTime();
            } else {
                $timestamp = time();
            }
        } elseif ($timestamp >= 0) {
            if ($addoffset) {
                // adjust for the user's timezone offset
                $timestamp += $this->userOffset($timestamp) * 3600;
            }
        } else {
            // invalid dates < 0 (e.g. from strtotime) return an empty date string
            return '';
        }
        return $this->strftime($format, $timestamp);
    }

    public function userTime(?int $time = null, $flag = 1): int
    {
        // get the current UTC time
        if (!isset($time)) {
            $time = time();
        }
        // return the corrected timestamp
        if ($flag) {
            $time += $this->userOffset($time) * 3600;
        }
        return $time;
    }

    public function userOffset(?int $timestamp = null): int
    {
        $datetime = new XarDateTime();
        $datetime->setTimeStamp($timestamp);
        $useroffset = $datetime->getTZOffset($this->timezone);

        return intdiv($useroffset, 3600);
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
    public function strftime(?string $format = null, ?int $timestamp = null): string
    {
        // if we don't have a timestamp, get the user's current time
        if (!isset($timestamp)) {
            $timestamp = $this->userTime();
        } elseif ($timestamp < 0) {
            // invalid dates < 0 (e.g. from strtotime) return an empty date string
            return '';
        } elseif ($timestamp === false) {
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            return '';
        }

        // we need to get the correct timestamp format if we do not have one
        if (!isset($format)) {
            // check for user defined format
            /*
            if($user_defined) {
                $format =& $user_defined;
            } elseif ($admin_defined) {
                $format =& $admin_defined;
            } else {
            */
            $format = '%c';
            /*
            }
            */
        }

        return Legacy::strftime($format, $timestamp, $this->locale);
    }
}
