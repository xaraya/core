<?php
/*
 *  $Id: SQLStatementExtractor.php,v 1.5 2004/07/27 23:13:46 hlellelid Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://creole.phpdb.org>.
 */
 
/**
 * Static class for extracting SQL statements from a string or file.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.5 $
 * @package   creole.util.sql
 */
class SQLStatementExtractor {
    
    protected static $delimiter = ';';
    
    protected static $in_quote = false;
    
    // the level of block comments we are in
    protected static $bc_nest = 0;
    
    // the level of BEGIN/END blocks we are in
    protected static $iBegEndNest = 0;
    
    /**
     * Get SQL statements from file.
     * 
     * @param string $filename Path to file to read.
     * @return array SQL statements
     */
    public static function extractFile($filename) {
        $buffer = file_get_contents($filename);
        if ($buffer === false) {
           throw new Exception("Unable to read file: " . $filename);
        }
        return self::extractStatements(self::getLines($buffer));
    }
    
    /**
     * Extract statements from string.
     * 
     * @param string $txt
     * @return array
     */
    public static function extract($buffer) {
        return self::extractStatements(self::getLines($buffer));
    }
    
    /**
     * Extract SQL statements from array of lines.
     *
     * @param array $lines Lines of the read-in file.
     * @return string
     */
    protected static function extractStatements($lines) {
        
        $statements = array();
        $sql = "";
               
        foreach($lines as $line) {
        
                $line = trim($line);
                
                if (self::startsWith("//", $line) || 
                    self::startsWith("--", $line) ||
                    self::startsWith("#", $line)) {
                    continue;
                }
                
                if (strlen($line) > 4 && strtoupper(substr($line,0, 4)) == "REM ") {
                    continue;
                }

                // looking for the number of block comment patterns in this line
                // minus one b/c of the nature of explode
                $bc_start_num = count(explode('/*', $line)) - 1;
                $bc_end_num = count(explode('*/', $line)) - 1;
                self::$bc_nest = self::$bc_nest + ($bc_start_num - $bc_end_num);

                // now looking for the first bc open and the last bc close
                $bc_first = strpos($line, '/*');
                $bc_last = strrpos($line, '*/');
                
                // looking for the number of BEGIN/END blocks
                if( preg_match('/BEGIN$/', strtoupper($line)) ) {
                    self::$iBegEndNest++;
                }
                
                if( preg_match('/^END;/', strtoupper($line)) ) {
                    self::$iBegEndNest--;
                }

                // SQL defines "--" as a comment to EOL
                // and in Oracle it may contain a hint
                // so we cannot just remove it, instead we just remove it
                // for the purpose of testing the line
                $comment_position = strpos($line, "--");
                
                // make sure the above comment isn't in block comment
                if( self::$bc_nest > 0 || ($bc_first < $comment_position && $bc_last > $comment_position ) ) {
                    // it is in a block comment, so we will pretend it doesn't exist
                    $comment_position = false;
                }
    
                if ( $comment_position !== false) {
                    $comment = self::substring($line, $comment_position);
                    $line = self::substring($line, 0, $comment_position - 1);
                } else {
                    $comment = '';
                }
                
                $sql .= " " . $line;
                $sql = trim($sql);
                
                // count the number of times we found a non-escaped single quote
                $quote_count = preg_match_all("#^'|[^\\\\]'#", $line, $matches);
                
                // two single quotes (i.e. empty string), minus one b/c of the nature
                // of explode.  We have to look at the this b/c the above regex
                // matches two single quotes only once, we negate that below
                $es_count = count(explode("''", $line)) - 1;
                $quote_count = $quote_count - $es_count;
                
                $odd_quotes = $quote_count % 2 == 0 ? false : true;

                // if we are inside a quote, it doesn't matter what is in the line
                // we need to continue
                if ( self::$in_quote == true && $odd_quotes == false ) {
                    continue;
                } elseif ( self::$in_quote == true && $odd_quotes == true ) {
                    self::$in_quote = false;
                } elseif ( self::$in_quote == false && $odd_quotes == true ) {
                    self::$in_quote = true;
                }
    
                if (self::$iBegEndNest == 0 && self::endsWith(self::$delimiter, $sql)) {
                    $statements[] = self::substring($sql, 0, strlen($sql)-1 - strlen(self::$delimiter));
                    if( $comment != '' ) {
                       $statements[] = $comment;
                    }
                    $sql = "";
                }
            }
        return $statements;           
    }
    
    //
    // Some string helper methods
    // 
    
    /**
     * Tests if a string starts with a given string.
     * @param string $check The substring to check.
     * @param string $string The string to check in (haystack).
     * @return boolean True if $string starts with $check, or they are equal, or $check is empty.
     */
    protected static function startsWith($check, $string) {
        if ($check === "" || $check === $string) {
            return true;
        } else {
            return (strpos($string, $check) === 0) ? true : false;
        }
    }
    
    /**
     * Tests if a string ends with a given string.
     * @param string $check The substring to check.
     * @param string $string The string to check in (haystack).
     * @return boolean True if $string ends with $check, or they are equal, or $check is empty.
     */
    protected static function endsWith($check, $string) {
        if ($check === "" || $check === $string) {
            return true;
        } else {
            return (strpos(strrev($string), strrev($check)) === 0) ? true : false;
        }
    } 

    /**
     * a natural way of getting a subtring, php's circular string buffer and strange
     * return values suck if you want to program strict as of C or friends 
     */
    protected static function substring($string, $startpos, $endpos = -1) {
        $len    = strlen($string);
        $endpos = (int) (($endpos === -1) ? $len-1 : $endpos);
        if ($startpos > $len-1 || $startpos < 0) {
            trigger_error("substring(), Startindex out of bounds must be 0<n<$len", E_USER_ERROR);
        }
        if ($endpos > $len-1 || $endpos < $startpos) {
            trigger_error("substring(), Endindex out of bounds must be $startpos<n<".($len-1), E_USER_ERROR);
        }
        if ($startpos === $endpos) {
            return (string) $string{$startpos};
        } else {
            $len = $endpos-$startpos;
        }
        return substr($string, $startpos, $len+1);
    }
    
    /**
     * Convert string buffer into array of lines.
     * 
     * @param string $filename
     * @return array string[] lines of file.
     */
    protected static function getLines($buffer) {       
       $lines = preg_split("/\r?\n|\r/", $buffer);
       return $lines;
    }
    
}