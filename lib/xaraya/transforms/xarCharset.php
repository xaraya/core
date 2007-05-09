<?php
/**
 * Class for conversion between charsets
 *
 * @package multilanguage
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Mikolaj Jedrzejak <mikolajj@op.pl>
 * @author Vladimirs Metenchuks <voll@xaraya.com>
**/

define ("CONVERT_TABLES_DIR", sys::root() . '/lib/transforms/convtables/');

/**
 * Main FEATURES of this class:
 * - conversion between 1 byte charsets
 * - conversion from 1 byte to multi byte charset (utf-8)
 * - conversion from multibyte charset (utf-8) to 1 byte charset
 * - every conversion output can be save with numeric entities
 *   (browser charset independent - not a full truth)
 *
 * Convert Tables Directory
 *
 * This is a place where you store all files with charset encodings.
 * Filenames should have the same names as encodings.
 * My advise is to keep existing names, because they were taken
 * from unicode.org (www.unicode.org), and after update to unicode 3.0 or 4.0
 * the names of files will be the same.
 *
 * This is a list of charsets you can operate with, the basic rule is
 * that a char have to be in both charsets, otherwise you'll get an error.
 *
 * - WINDOWS
 * - windows-1250 - Central Europe
 * - windows-1251 - Cyrillic
 * - windows-1252 - Latin I
 * - windows-1253 - Greek
 * - windows-1254 - Turkish
 * - windows-1255 - Hebrew
 * - windows-1256 - Arabic
 * - windows-1257 - Baltic
 * - windows-1258 - Viet Nam
 * - cp874 - Thai - this file is also for DOS
 *
 * - DOS
 * - cp437 - Latin US
 * - cp737 - Greek
 * - cp775 - BaltRim
 * - cp850 - Latin1
 * - cp852 - Latin2
 * - cp855 - Cyrillic
 * - cp857 - Turkish
 * - cp860 - Portuguese
 * - cp861 - Iceland
 * - cp862 - Hebrew
 * - cp863 - Canada
 * - cp864 - Arabic
 * - cp865 - Nordic
 * - cp866 - Cyrillic Russian for DOS
 * - cp869 - Greek2
 *
 * - MAC (Apple)
 * - x-mac-cyrillic
 * - x-mac-greek
 * - x-mac-icelandic
 * - x-mac-ce
 * - x-mac-roman
 *
 * - ISO (Unix/Linux)
 * - iso-8859-1
 * - iso-8859-2
 * - iso-8859-3
 * - iso-8859-4
 * - iso-8859-5
 * - iso-8859-6
 * - iso-8859-7
 * - iso-8859-8
 * - iso-8859-9
 * - iso-8859-10
 * - iso-8859-11
 * - iso-8859-12
 * - iso-8859-13
 * - iso-8859-14
 * - iso-8859-15
 * - iso-8859-16
 *
 * - MISCELLANEOUS
 * - gsm0338 (ETSI GSM 03.38)
 * - cp037
 * - cp424
 * - cp500
 * - cp856
 * - cp875
 * - cp1006
 * - cp1026
 * - koi8-r (Cyrillic)
 * - koi8-u (Cyrillic Ukrainian)
 * - nextstep
 * - us-ascii
 * - us-ascii-quotes
 *
 * - DSP implementation for NeXT
 * - stdenc
 * - symbol
 * - zdingbat
 *
 *
 *
 * The file with encoding tables have to be save in "Format A"
 * of unicode.org charset table format!
 * This is usualy writen in a header of every charset file.
 *
 * The files with encoding tables have to be complete
 * (Non of chars can be missing, unless you are sure
 * you are not going to use it)
 *
 * "Format A" encoding file, if you have to build it by yourself
 * should aplly these rules:
 * - you can comment everything with #
 * - first column contains 1 byte chars in hex starting from 0x..
 * - second column contains unicode equivalent in hex starting from 0x....
 * - then every next column is optional, but in "Format A"
 *   it should contain unicode char name or/and your own comment
 * - the columns can be splited by "spaces", "tabs", "," or
 *   any combination of these
 * - below is an example
 *
 * <code>
 * #
 * #    The entries are in ANSI X3.4 order.
 * #
 * 0x00    0x0000    #    NULL end extra comment, if needed
 * 0x01    0x0001    #    START OF HEADING
 * # Oh, one more thing, you can make comments inside of a rows if you like.
 * 0x02    0x0002    #    START OF TEXT
 * 0x03    0x0003    #    END OF TEXT
 * next line, and so on...
 * </code>
 *
 * You can get full tables with encodings from http://www.unicode.org
**/
class xarCharset extends Object
{
    public $lastConversion = ''; // Last used conversion
    public $conversionTable;     // Last conversion table
    public $noCharByteVal = 63;  // ASCII value for chars with no equivalent

    /**
     * Converts unicode number to UTF-8 multibyte character
     *
     * @access private
     * @param  integer     Hexadecimal value of a unicode char.
     * @return string      Encoded hexadecimal value as a regular char.
     **/
    function unicodeNumberToUtf8Char($number)
    {
        $char = '';
        $number = hexdec($number);
        if ($number < 0x80) {
            $char .= chr($number);
        } else if ($number < 0x800) {
            $char .= chr(0xC0 | ($number>>6));
            $char .= chr(0x80 | ($number&0x3F));
        } else if ($number < 0x10000) {
            $char .= chr(0xE0 | ($number>>12));
            $char .= chr(0x80 | (($number>>6)&0x3F));
            $char .= chr(0x80 | ($number&0x3F));
        } else if ($number < 0x200000) {
            $char .= chr(0xF0 | ($number>>18));
            $char .= chr(0x80 | (($number>>12)&0x3F));
            $char .= chr(0x80 | ($number>>6)&0x3F);
            $char .= chr(0x80 | $number&0x3F);
        } else if ($number < 0x4000000) {
            $char .= chr(0xF8 | ($number >> 24));
            $char .= chr(0x80 | (($number >> 18) & 0x3F));
            $char .= chr(0x80 | (($number >> 12) & 0x3F));
            $char .= chr(0x80 | (($number >> 6) & 0x3F));
            $char .= chr(0x80 | ($number & 0x3F));
        } else if ($number < 0x80000000) {
            $char .= chr(0xFC | ($number >> 30));
            $char .= chr(0x80 | (($number >> 24) & 0x3F));
            $char .= chr(0x80 | (($number >> 18) & 0x3F));
            $char .= chr(0x80 | (($number >> 12) & 0x3F));
            $char .= chr(0x80 | (($number >> 6) & 0x3F));
            $char .= chr(0x80 | ($number & 0x3F));
        }
        return $char;
    }

    /**
     * Converts a UTF-8 multibyte character to a UNICODE number
     *
     * @param   string   UTF-8 multibyte character string
     * @param   boolean  If set, then a hex. number is returned.
     * @return  integer  UNICODE integer
     **/
    function utf8CharToUnicodeNumber($utf8char,$returnHex=0)
    {
        $ord = ord(substr($utf8char,0,1)); // First char

        if (($ord & 192) == 192) { // IS it a MB string
            $binBuf = '';
            for ($b=0;$b<8;$b++) { // for each byte in MB string...
                $ord = $ord << 1;  // Shift it left
                if ($ord & 128) {  // if 8th bit is set, there are still bytes in sequence.
                    $binBuf .= substr('00000000'.decbin(ord(substr($utf8char,$b+1,1))),-6);
                } else break;
            }
            $binBuf = substr('00000000'.decbin(ord(substr($utf8char,0,1))),-(6-$b)).$binBuf;
            $int = bindec($binBuf);
        } else {
            $int = $ord;
        }

        return $returnHex ? 'x'.dechex($int) : $int;
    }

    /**
     * Converts chars to numeric entities.
     *
     * @param    string    Input string
     * @return   string    Output string
     */
    function utf8ToEntities($inStr)
    {
        $strLen = strlen($inStr);
        $outStr = '';
        $buffer = '';
        for ($ptr=0; $ptr<$strLen; $ptr++) {
            $char = $inStr[$ptr];
            $asciiChar = ord($char);
            if ($asciiChar > 127) {
                // Multibyte found (first byte!)
                if ($asciiChar & 64) {
                    // The first byte must have the 7th bit set!
                    $buffer = $char; // Add first byte
                    for ($i=0; $i<8; $i++) { // For each byte in MB string
                        $asciiChar = $asciiChar << 1; // Shift char left
                        if ($asciiChar & 128) { // 8th bit
                            // There are still bytes in sequence
                            $ptr++;
                            $buffer .= $inStr[$ptr]; // Add the next char
                        } else break;
                    }
                    $outStr .= '&#'.$this->utf8CharToUnicodeNumber($buffer,1).';';
                } else {
                    $outStr .= chr($this->noCharByteVal);
                }
            } else {
                // ASCII 0-127 and one byte
                $outStr .= $char;
            }
        }

        return $outStr;
    }

    /**
     * Creates table with two SBCS (Single Byte Character Set).
     * Every conversion go through this table.
     *
     * The file with encoding tables have to be save in
     * "Format A" of unicode.org charset table format
     *
     * @param string First encoding name and filename.
     * @param string Second encoding name and filename. Optional for building a joined table.
     * @return array Table necessary to convert one encoding to another.
     **/
    function &initConvertTable ($firstEncoding, $secondEncoding = "")
    {
        if ($this->lastConversion == $firstEncoding.':'.$secondEncoding) {
            return $this->conversionTable;
        }

        $convertTable = array();
        for ($i = 0; $i < func_num_args(); $i++) {
            $fileName = CONVERT_TABLES_DIR . func_get_arg($i);
            $fp = fopen($fileName, "r");
            if ($fp === false) {
                xarLogMessage("xarCharset error, can NOT read file: " . $fileName);
                continue;
            }
            while (!feof($fp)) {
                $string = trim(fgets($fp, 1024));
                if (empty($string)) continue; // Skip empty lines
                if ($string[0] == "#") continue; // Skip comments
                // Separators: "space", "tab", ",", "\r", "\n" and "\f"
                $HexValue = preg_split ("/[\s,]+/", $string, 3);
                // Skip undefined or missing char
                if ($HexValue[1][0] == "#") continue;
                // Got char, load it
                $ArrayKey = strtoupper(str_replace(strtolower("0x"), "", $HexValue[1]));
                $ArrayValue = strtoupper(str_replace(strtolower("0x"), "", $HexValue[0]));
                $convertTable[func_get_arg($i)][$ArrayKey] = $ArrayValue;
            }
        }
        $this->lastConversion = $firstEncoding . ':' . $secondEncoding;
        $this->conversionTable =& $convertTable;
        return $convertTable;
    }


    /**
     *  Converts string from one charset to another
     *
     * @param string The input string you want to change.
     * @param string Source charset.
     * @param string Target charset.
     * @param boolean Set to true or 1 if you want to use numeric entities insted of regular chars.
     * @return string Converted string
     **/
    function convertByTable ($inString, $fromCharset = '', $toCharset = '', $turnOnEntities = false)
    {
        /**
         * Check are there all variables
         **/
        if ($inString == '') {
            return '';
        } else if ($fromCharset == '') {
            xarLogMessage("xarCharset error, empty variable \$fromCharset in convertByTable() function.");
            return $inString;
        } else if ($toCharset == '') {
            xarLogMessage("xarCharset error, empty variable \$toCharset in convertByTable() function.");
            return $inString;
        }

        $outString = "";

        // Convert charset encoding names to lowercase
        $fromCharset = strtolower($fromCharset);
        $toCharset   = strtolower($toCharset);

        if ($fromCharset == $toCharset) {
            xarLogMessage("xarCharset - you are trying to convert string from ". $fromCharset ." to ". $fromCharset);
            return $inString;
        }

        if ($fromCharset == "utf-8") {
            // Converts from multibyte char string.
            $CharsetTable =& $this->initConvertTable ($toCharset);
            foreach ($CharsetTable[$toCharset] as $unicodeHexChar => $hexChar) {
                if ($turnOnEntities == true) {
                    $replace = $this->utf8ToEntities($this->unicodeNumberToUtf8Char($unicodeHexChar));
                } else {
                    $replace = chr(hexdec($hexChar));
                }
                $search = $this->unicodeNumberToUtf8Char($unicodeHexChar);
                $inString = str_replace($search, $replace, $inString);
            }
            $outString = $inString;
        } else {
            // Converts from 1-byte char string.
            if ($toCharset == "utf-8") {
                $CharsetTable =& $this->initConvertTable ($fromCharset);
            } else {
                $CharsetTable =& $this->initConvertTable ($fromCharset, $toCharset);
            }
            $strLen = strlen($inString);
            for ($i = 0; $i < $strLen; $i++) {
                $hexChar = '';
                $unicodeHexChar = '';
                $ord = ord($inString[$i]);
                $hexChar = strtoupper(dechex($ord));
                if ($ord < 16) $hexChar = "0".$hexChar; // add leading zero
                if (($fromCharset == "gsm0338") && ($hexChar == '1B')) {
                    // quick fix of escape to extension table
                    $hexChar .= strtoupper(dechex(ord($inString[++$i])));
                }
                if (in_array($hexChar, $CharsetTable[$fromCharset])) {
                    $unicodeHexChar = array_search($hexChar, $CharsetTable[$fromCharset]);
                    if ($toCharset != "utf-8") {
                        if (isset($CharsetTable[$toCharset][$unicodeHexChar])) {
                            if ($turnOnEntities == true) {
                                $outString .= $this->utf8ToEntities($this->unicodeNumberToUtf8Char($unicodeHexChar));
                            } else {
                                $outString .= chr(hexdec($CharsetTable[$toCharset][$unicodeHexChar]));
                            }
                        } else {
                            $outString .= chr($this->noCharByteVal);
                            xarLogMessage("xarCharset error, can't find maching char \"". $inString[$i] ."\" in destination encoding table!");
                        }
                    } else {
                        $outChar = $this->unicodeNumberToUtf8Char($unicodeHexChar);
                        if ($turnOnEntities == true) {
                            $outString .= $this->utf8ToEntities($outChar);
                        } else {
                            $outString .= $outChar;
                        }
                    }
                } else {
                    $outString .= chr($this->noCharByteVal);
                    xarLogMessage("xarCharset error, can't find maching char \"". $inString[$i] ."\" in source encoding table!");
                }
            }
        }
        return $outString;
    }

    /**
     *  Converts string from one charset to another
     *
     * @param string The input string you want to change.
     * @param string Source charset.
     * @param string Target charset.
     * @param boolean Set to true or 1 if you want to use numeric entities insted of regular chars.
     * @return string Converted string
     **/
    function convert ($inString, $fromCharset, $toCharset, $turnOnEntities = false)
    {
        if (function_exists('iconv')) {
            $outString = @iconv($fromCharset, $toCharset.'//TRANSLIT', $inString);
            if ($outString === false) $outString = '';
        } else {
            $outString = $this->convertByTable($inString, $fromCharset, $toCharset, $turnOnEntities);
        }
        return $outString;
    }
}

?>
