<?php
/*
 *  $Id: PgSQLTableInfo.php,v 1.27 2005/03/09 19:15:47 hlellelid Exp $
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

require_once 'creole/metadata/TableInfo.php';

/**
 * PgSQL implementation of TableInfo.
 *
 * See this Python code by David M. Cook for some good reference on Pgsql metadata
 * functions:
 * @link http://www.sandpyt.org/pipermail/sandpyt/2003-March/000008.html
 *
 * Here's some more information from postgresql:
 * @link http://developer.postgresql.org/docs/pgsql/src/backend/catalog/information_schema.sql
 *
 * @todo -c Eventually move to supporting only Postgres >= 7.4, which has the information_schema
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.27 $
 * @package   creole.drivers.pgsql.metadata
 */
class PgSQLTableInfo extends TableInfo {

    /** Load the columns for this table */
    protected function initColumns() {

        include_once 'creole/metadata/ColumnInfo.php';
        include_once 'creole/drivers/pgsql/PgSQLTypes.php';

        // Get any default values for columns
        $result = pg_query($this->dblink, "SELECT d.adnum as num, d.adsrc as def from pg_attrdef d, pg_class c where d.adrelid=c.oid and c.relname='".$this->name."' order by d.adnum");

        if (!$result) {
            throw new SQLException("Could not get defaults for columns in table: " . $this->name, pg_last_error($this->dblink));
        }

        $defaults = array();
        while($row = pg_fetch_assoc($result)) {
            // [HL] for now I am going to not add default
            // values that are nextval(...) sequence values.
            // We need to resolve on a larger level whether these should
            // be returned.  Maybe instead indicating that these columns are
            // sequences would be appropriate...
            if (!preg_match('/^nextval\(/', $row['def'])) {
                $defaults[ $row['num'] ] = $row['def'];
            }
        }

        // Get the columns, types, etc.
        // based on SQL from ADOdb
        $result = pg_query($this->dblink, "SELECT    a.attname,
                                    t.typname,
                                    a.attlen,
                                    a.atttypmod,
                                    a.attnotnull,
                                    a.atthasdef,
                                    a.attnum,
                                    CAST(
                                         CASE WHEN t.typtype = 'd' THEN
                                           CASE WHEN t.typbasetype IN (21, 23, 20) THEN 0
                                                WHEN t.typbasetype IN (1700) THEN (t.typtypmod - 4) & 65535
                                                ELSE null END
                                         ELSE
                                           CASE WHEN a.atttypid IN (21, 23, 20) THEN 0
                                                WHEN a.atttypid IN (1700) THEN (a.atttypmod - 4) & 65535
                                                ELSE null END
                                         END
                                         AS int) AS numeric_scale
                            FROM     pg_class c,
                                    pg_attribute a,
                                    pg_type t
                            WHERE    relkind = 'r' AND
                                    c.relname='".$this->name."' AND
                                    a.attnum > 0 AND
                                    a.atttypid = t.oid AND
                                    a.attrelid = c.oid
                            ORDER BY a.attnum");

        if (!$result) {
            throw new SQLException("Could not list fields for table: " . $this->name, pg_last_error($this->dblink));
        }

        while($row = pg_fetch_assoc($result)) {
            $name = $row['attname'];
            $type = $row['typname'];
            $size = $row['attlen'];
            $scale = $row['numeric_scale'];
            if ($size <= 0) {
                // maxlen for varchar is 4 larger than actual max length
                $size = $row['atttypmod'] - 4;
                if ($size <= 0) {
                    $size = null;
                }
            }

            $is_nullable = ($row['attnotnull'] == 't' ? true : false);
            $default = ($row['atthasdef'] == 't' && isset( $defaults[ $row['attnum'] ]) ? $defaults[ $row['attnum'] ] : null);
            $this->columns[$name] = new ColumnInfo($this, $name, PgSQLTypes::getType($type), $type, $size, $scale, $is_nullable, $default);
        }

        $this->colsLoaded = true;
    }

    /** Load foreign keys for this table. */
    protected function initForeignKeys()
    {
        include_once 'creole/metadata/ForeignKeyInfo.php';

        // condef is for reference.
        $result = pg_query($this->dblink, "SELECT c.relname,
                        r.conname,
                        c1.relname AS tablelocal,
                        a1.attname AS collocal,
                        c2.relname AS tableforeign,
                        a2.attname AS colforeign,
                        r.confupdtype AS onupdate,
                        r.confdeltype AS ondelete,
                        pg_catalog.pg_get_constraintdef(r.oid) as condef
                  FROM  pg_catalog.pg_class c
              LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
              LEFT JOIN pg_catalog.pg_constraint r ON r.conrelid = c.oid
              LEFT JOIN pg_catalog.pg_attribute a1 ON a1.attrelid = r.conrelid
              LEFT JOIN pg_catalog.pg_attribute a2 ON a2.attrelid = r.confrelid
              LEFT JOIN pg_catalog.pg_class c1 ON c1.oid = r.conrelid
              LEFT JOIN pg_catalog.pg_class c2 ON c2.oid = r.confrelid
                  WHERE pg_catalog.pg_table_is_visible(c.oid)
                    AND c.relname ~ '^".$this->name."$'
                    AND  r.contype = 'f'
                    AND a1.attnum > 0
                    AND NOT a1.attisdropped
                    AND a1.attnum = r.conkey[1]
                    AND NOT a2.attisdropped
                    AND a2.attnum = r.confkey[1]
                    AND c1.relkind IN  ('r','')
                    AND pg_catalog.pg_table_is_visible(c1.oid)
                    AND c2.relkind IN  ('r','')
                    AND pg_catalog.pg_table_is_visible(c2.oid)
                    ORDER BY 2");
        if (!$result) {
            throw new SQLException("Could not list foreign keys for table: " . $this->name, pg_last_error($this->dblink));
        }

        while($row = pg_fetch_row($result)) {

            $name = $row[1];
            $local_table = $row[2];
            $local_column = $row[3];
            $foreign_table = $row[4];
            $foreign_column = $row[5];

            switch ($row[6]) {
              case 'c':
                $onupdate = ForeignKeyInfo::CASCADE; break;
              case 'd':
                $onupdate = ForeignKeyInfo::SETDEFAULT; break;
              case 'n':
                $onupdate = ForeignKeyInfo::SETNULL; break;
              case 'r':
                $onupdate = ForeignKeyInfo::RESTRICT; break;
              default:
              case 'a':
                //NOACTION is the postgresql default
                $onupdate = ForeignKeyInfo::NONE; break;
            }
            switch ($row[7]) {
              case 'c':
                $ondelete = ForeignKeyInfo::CASCADE; break;
              case 'd':
                $ondelete = ForeignKeyInfo::SETDEFAULT; break;
              case 'n':
                $ondelete = ForeignKeyInfo::SETNULL; break;
              case 'r':
                $ondelete = ForeignKeyInfo::RESTRICT; break;
              default:
              case 'a':
                //NOACTION is the postgresql default
                $ondelete = ForeignKeyInfo::NONE; break;
            }


            $foreignTable = $this->database->getTable($foreign_table);
            $foreignColumn = $foreignTable->getColumn($foreign_column);

            $localTable   = $this->database->getTable($local_table);
            $localColumn   = $localTable->getColumn($local_column);

            if (!isset($this->foreignKeys[$name])) {
                $this->foreignKeys[$name] = new ForeignKeyInfo($name);
            }
            $this->foreignKeys[$name]->addReference($localColumn, $foreignColumn, $onupdate, $ondelete);
        }

        $this->fksLoaded = true;
    }

    /** Load indexes for this table */
    protected function initIndexes()
    {
        include_once 'creole/metadata/IndexInfo.php';

        // columns have to be loaded first
        if (!$this->colsLoaded) $this->initColumns();

        // FIXME -- try this out!
        // then figure out if we need to add any information
        // to our index object to accommodate more complex backends

        $result = pg_query($this->dblink, "SELECT c.relname as tablename, c.oid, c2.relname as indexname,

                            i.indisprimary, i.indisunique, pg_catalog.pg_get_indexdef(i.indexrelid) FROM

                            pg_catalog.pg_class c,

                            pg_catalog.pg_class c2, pg_catalog.pg_index i WHERE c.oid = i.indrelid AND

                            i.indexrelid = c2.oid AND c.relname = '".$this->name."' ORDER BY i.indisprimary DESC, i.indisunique DESC,

                            c2.relname");


        if (!$result) {
            throw new SQLException("Could not list indexes keys for table: " . $this->name, pg_last_error($this->dblink));
        }

        while($row = pg_fetch_assoc($result)) {
            $name = $row["indexname"];
            if (!isset($this->indexes[$name])) {
                $this->indexes[$name] = new IndexInfo($name);
            }
            $this->indexes[$name]->addColumn($this->columns[ $name ]);
        }

        $this->indexesLoaded = true;
    }

    /** Loads the primary keys for this table. */
    protected function initPrimaryKey() {

        include_once 'creole/metadata/PrimaryKeyInfo.php';


        // columns have to be loaded first
        if (!$this->colsLoaded) $this->initColumns();

        // Primary Keys
        $result = pg_query($this->dblink, "select ta.attname, ia.attnum
                                            from pg_attribute ta, pg_attribute ia, pg_class c, pg_index i
                                            where c.relname = '".$this->name."_pkey'
                                                AND c.oid = i.indexrelid
                                                AND ia.attrelid = i.indexrelid
                                                AND ta.attrelid = i.indrelid
                                                AND ta.attnum = i.indkey[ia.attnum-1]
                                            ORDER BY ia.attnum");

        if (!$result) {
            throw new SQLException("Could not list primary keys for table: " . $this->name, pg_last_error($this->dblink));
        }

        // Loop through the returned results, grouping the same key_name together
        // adding each column for that key.

        while($row = pg_fetch_assoc($result)) {
            $name = $row["attname"];
            if (!isset($this->primaryKey)) {
                $this->primaryKey = new PrimaryKeyInfo($name);
            }
            $this->primaryKey->addColumn($this->columns[ $name ]);
        }

        $this->pkLoaded = true;
    }

}
