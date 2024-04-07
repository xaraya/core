<?php

/*
 *  $Id: MySQLTypes.php,v 1.8 2005/02/10 09:22:40 pachanga Exp $
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
	This is an intermin solution; see below.
 */
require_once 'lib/creole/CreoleTypes.php';

/**
 * MySQL types / type map.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.8 $
 * @package   creole.drivers.mysql
 */
class MySQLTypes extends CreoleTypes
{
    /** Map MySQL native types to Creole (JDBC) types. */
/*
    private static $typeMap = array(
                                'tinyint'    => CreoleTypes::TINYINT,
                                'smallint'   => CreoleTypes::SMALLINT,
                                'mediumint'  => CreoleTypes::SMALLINT,
                                'int'        => CreoleTypes::INTEGER,
                                'integer'    => CreoleTypes::INTEGER,
                                'bigint'     => CreoleTypes::BIGINT,
                                'int24'      => CreoleTypes::BIGINT,
                                'real'       => CreoleTypes::REAL,
                                'float'      => CreoleTypes::FLOAT,
                                'decimal'    => CreoleTypes::DECIMAL,
                                'numeric'    => CreoleTypes::NUMERIC,
                                'double'     => CreoleTypes::DOUBLE,
                                'char'       => CreoleTypes::CHAR,
                                'varchar'    => CreoleTypes::VARCHAR,
                                'date'       => CreoleTypes::DATE,
                                'time'       => CreoleTypes::TIME,
                                'year'       => CreoleTypes::YEAR,
                                'datetime'   => CreoleTypes::TIMESTAMP,
                                'timestamp'  => CreoleTypes::TIMESTAMP,
                                'tinyblob'   => CreoleTypes::BINARY,
                                'blob'       => CreoleTypes::BLOB,
                                'mediumblob' => CreoleTypes::VARBINARY,
                                'longblob'   => CreoleTypes::LONGVARBINARY,
                                'longtext'   => CreoleTypes::LONGVARCHAR,
                                'tinytext'   => CreoleTypes::VARCHAR,
                                'mediumtext' => CreoleTypes::LONGVARCHAR,
                                'text'       => CreoleTypes::TEXT,
                                'enum'       => CreoleTypes::CHAR,
                                'set'        => CreoleTypes::CHAR,
                                );
*/

    /** 
    	This database type uses 9 of the 24 Creole (JDBC) data types.
    	This is an intermin solution that maps each Creole (JDBC) type to exactly one native type.
    	This works well for the core and core modules tables, which need only a subset of all types.
    	1. Over time this map can be extended to include more that one native type per Creole type,
    	   but then a different approach will be needed.
    	2. This map should be moved out of Creole to lib/xaraya.
     */
    public static $typeMap = array(
                                'boolean'     => CreoleTypes::BOOLEAN,			// Undefined in this db

                                'tinyint'     => CreoleTypes::TINYINT,
                                'smallint'    => CreoleTypes::SMALLINT,			// Not currently used
                                'integer'     => CreoleTypes::INTEGER,
                                'bigint'      => CreoleTypes::BIGINT,		    // Not currently used

                                'numeric'     => CreoleTypes::NUMERIC,			// Not currently used
                                'decimal'     => CreoleTypes::DECIMAL,
                                'real'        => CreoleTypes::REAL,				// Not currently used
                                'float'       => CreoleTypes::FLOAT,
                                'double'      => CreoleTypes::DOUBLE,			// Not currently used

                                'char'        => CreoleTypes::CHAR,				// Undefined in this db
                                'varchar'     => CreoleTypes::VARCHAR,			
                                'text'        => CreoleTypes::TEXT,
                                'longtext'    => CreoleTypes::LONGVARCHAR,
                                'clob'        => CreoleTypes::CLOB,				// Undefined in this db

                                'tinyblob'    => CreoleTypes::BINARY,			// Not currently used
                                'varbinary'   => CreoleTypes::VARBINARY,		// Undefined in this db
                                'blob'        => CreoleTypes::BLOB,
                                'longblob'    => CreoleTypes::LONGVARBINARY,

                                'time'        => CreoleTypes::TIME,				// Not currently used
                                'timestamp'   => CreoleTypes::TIMESTAMP,		// Undefined in this db
                                'date'        => CreoleTypes::DATE,				// Not currently used
                                'year'        => CreoleTypes::YEAR,				// Not currently used

                                'array'       => CreoleTypes::ARR,				// Undefined in this db
                                );

    /** Reverse mapping, created on demand. */
    private static $reverseMap = null;

    /**
     * This method returns the generic Creole (JDBC-like) type
     * when given the native db type.
     * @param string $nativeType DB native type (e.g. 'TEXT', 'byetea', etc.).
     * @return int Creole native type (e.g. CreoleTypes::LONGVARCHAR, CreoleTypes::BINARY, etc.).
     */
    public static function getType($nativeType)
    {
        $t = strtolower($nativeType);
        if (isset(self::$typeMap[$t])) {
            return self::$typeMap[$t];
        } else {
            return CreoleTypes::OTHER;
        }
    }

    /**
     * This method will return a native type that corresponds to the specified
     * Creole (JDBC-like) type.
     * If there is more than one matching native type, then the LAST defined
     * native type will be returned.
     * @param int $creoleType
     * @return string Native type string.
     */
    public static function getNativeType($creoleType)
    {
        if (self::$reverseMap === null) {
            self::$reverseMap = array_flip(self::$typeMap);
        }
        return @self::$reverseMap[$creoleType];
    }

}
