<?php
/**
 * Class for conversion between charsets
 *
 * @package multilanguage
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Mikolaj Jedrzejak <mikolajj@op.pl>
 * @author Vladimirs Metenchuks <voll@xaraya.com>
*/

define ("CONVERT_TABLES_DIR", sys::root() . '/lib/transforms/convtables/');

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
