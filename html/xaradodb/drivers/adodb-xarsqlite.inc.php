<?php

include_once(ADODB_DIR . '/drivers/adodb-sqlite.inc.php');

class ADODB_xarsqlite extends ADODB_sqlite 
{
    var $dataProvider = 'sqlite';

    function _connect($argHostname, $argUsername, $argPassword, $argDatabasename)
    {
        if (!function_exists('sqlite_open')) return false;

        // It makes more sense to interpret host=directory and dbname=file
        $this->_connectionID = sqlite_open($argHostname . "/" . $argDatabasename);
        if ($this->_connectionID === false) return false;
        $this->_createFunctions();
        return true;
    }

    function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
    {
        if (!function_exists('sqlite_open')) return false;
        
        // It makes more sense to interpre host=directory and dbname=file
        $this->_connectionID = sqlite_popen($argHostname . "/" . $argDatabasename);
        // END XARAYA MODIFICATION
        if ($this->_connectionID === false) return false;
        $this->_createFunctions();
        return true;
    }

    function SelectDB($dbName)
    {
        return file_exists($this->host . '/' . $this->database);
    }

    function GenID($seq='adodbseq',$start=1)
    {    
        // If using bindvars, this is perfectly sufficient (and we should use bindvars ;-) )
        return null;
    }

    /*
     * SQLite doesn't have an ALTER TABLE statement and we do want to have it, 
     * try to detect it in the incoming $sql and translate this into SQLite 
     * equivalent statements
     * The alter table emulation was taken from: http://code.jenseng.com/db/sql.txt
     */
    function &Execute($sql,$inputarr=false)
    {
        // Sqlite doesnt like backquotes, get rid of em
        $sql = str_replace('`','',$sql);
        
        if(strtolower(substr(ltrim($sql),0,5))=='alter') {
            $queryparts = preg_split("/[\s]+/",$sql,4,PREG_SPLIT_NO_EMPTY);
            $tablename = $queryparts[2];
            $alterdefs = $queryparts[3];
            if(strtolower($queryparts[1]) != 'table' || $queryparts[2] == '' || $alterdefs =='') {
                if($fn = $this->raiseErrorFn) {
                    $fn('sqlite', 'EXECUTE', 666, "Syntax error in ALTER TABLE statement",$sql);
                    return false;
                }
            }
            $result = $this->_altertable($tablename,$alterdefs);
        } else {
          // Otherwise use the normal execute method
          $result = parent::Execute($sql,$inputarr);
        }
        return $result;
    }

    function _altertable($orgTableName, $alterdefs)
    {
        $fn = $this->raiseErrorFn;
        
        // First get the table definition
        $sql = "SELECT sql,name,type FROM sqlite_master WHERE tbl_name = ? ORDER BY type DESC";
        $result = $this->Execute($sql, array((string) $orgTableName));
        if(!$result) return $result;
        
        // Return if the table isnt there, nothing to alter
        if($result->RecordCount() <= 0 ) {
            $fn('sqlite', 'EXECUTE', 666,  "Table $orgTableName not found",$sql);
            return false;
        }
          
        // Fetch the sql for the original table
        $row = $result->GetRowAssoc();
        $origsql = trim(preg_replace("/[\s]+/"," ",str_replace(",",", ",preg_replace("/[\(]/","( ",$row['SQL'],1))));
        // Get the colums of the original table in an array so we can manipulate them easier
        // split them first into array elements based on position of first "(" and strip off the last ")"
        $oldcols = preg_split("/[,]+/",substr($origsql,strpos($origsql,'(') +1,-1), -1, PREG_SPLIT_NO_EMPTY);    
        
        // Determine whether they are really all columns
        $nrOfTableConstraints = 0; 
        $constraintparts = array(); $parts = $oldcols;
        $tblConstraints = array("PRIMARY", "UNIQUE", "CHECK");
        $column_map = array();
        for($i=sizeof($oldcols)-1;$i>=0;$i--){
            $colparts = preg_split("/[\s]+/",$oldcols[$i],-1,PREG_SPLIT_NO_EMPTY);
            
            if(in_array(strtoupper($colparts[0]), $tblConstraints)) {
                array_unshift($constraintparts, $oldcols[$i]);
                array_pop($parts);
            } else {
                $column_map[$colparts[0]] = $colparts[0];
            }
        }

        // Initialize some values
        $tmpname = 't'.time();  $newTableName = $orgTableName;

        // SQL to create the temporary table
        $createtemptableSQL = trim('CREATE '.substr(trim(preg_replace("'".$orgTableName."'",$tmpname,$origsql,1)),6));
        
        // SQL to copy the data from the original to the temporary table
        $copytotempsql = "INSERT INTO $tmpname SELECT * FROM $orgTableName";
        
        // SQL for the new table (essential part of the goal here)
        $defs = preg_split("/[,]+/",$alterdefs,-1,PREG_SPLIT_NO_EMPTY);
        foreach($defs as $def){
            $defparts = preg_split("/[\s]+/",$def,-1,PREG_SPLIT_NO_EMPTY);
            $action = strtolower(array_shift($defparts));
            switch($action){
                case 'add':
                    // We have to insert the column at the end
                    if(!(sizeof($defparts) >= 1)) { // field [spec]
                        $fn('sqlite', 'EXECUTE', 666,'Syntax error in add part of ALTER TABLE');
                        return false;
                    }
                    $parts[] = implode(' ',$defparts);
                    break;
                case 'change':
                    if(!(sizeof($defparts) >= 2)) { // oldfield newfield [spec]
                        $fn('sqlite', 'EXECUTE', 666,'Syntax error in change part of ALTER TABLE');
                        return false;
                    }
                    // Find the position of the column in the parts array, and replace it 
                    $repl_index = -1;
                    foreach($parts as $index => $olddef) {
                        $olddefparts = preg_split("/[\s]+/",$olddef,-1,PREG_SPLIT_NO_EMPTY);
                        if($olddefparts[0] != $defparts[0]) continue;
                        $repl_index = $index;
                    }
                    if($repl_index == -1) {
                        $fn('sqlite', 'EXECUTE', 666,'Unknown column "'.$defparts[0].'" in "'.$orgTableName.'"');
                        return false;
                    }
                    $column_map[$defparts[0]] = $defparts[1];
                    array_shift($defparts);
                    $parts[$repl_index] = implode(' ', $defparts);
                    
                    break;
                case 'drop':
                    if(sizeof($defparts) != 1){ // field
                        $fn('sqlite', 'EXECUTE', 666,'Syntax error in drop part of ALTER TABLE');
                        return false;
                    }
                    // Find the position of the column in the parts array, and replace it 
                    $repl_index = -1;
                    foreach($parts as $index => $olddef) {
                        $olddefparts = preg_split("/[\s]+/",$olddef,-1,PREG_SPLIT_NO_EMPTY);
                        if($olddefparts[0] != $defparts[0]) continue;
                        $repl_index = $index;
                    }
                    if($repl_index == -1) {
                        $fn('sqlite', 'EXECUTE', 666,'Unknown column "'.$defparts[0].'" in "'.$orgTableName.'"');
                        return false;
                    }
                    $column_map[$defparts[0]] = null;
                    array_splice ($parts, $repl_index,1);
                    break;
                case 'rename':
                    // Renaming the table too, rest stays the same (unfortunately)
                    if(sizeof($defparts) != 2 || strtoupper($defparts[0]) != "TO" ){ // TO new table name
                        $fn('sqlite', 'EXECUTE', 666,'Syntax error in rename part of ALTER TABLE');
                        return false;
                    }
                    $newTableName = $defparts[1];
                    break;
                default:
                    $fn('sqlite', 'EXECUTE', 666,'Syntax error in ALTER TABLE ('.$action.')');
                    return false;
            }
        }
        // Reconstruct the sql from the arrays
        // For the NEW table
        $createnewtableSQL = 'CREATE TABLE '.$newTableName .'('.  implode(',',array_merge($parts,$constraintparts)) . ')';
        $createtesttableSQL = 'CREATE TABLE '.$tmpname .'('.  implode(',',array_merge($parts,$constraintparts)) . ')';
        
        //this block of code generates a test table simply to verify that the columns specifed are valid in an sql statement
        //this ensures that no reserved words are used as columns, for example
        $result=$this->Execute($createtesttableSQL);
        if(!$result) return false;
        $droptempsql = 'DROP TABLE '.$tmpname;
        $result = $this->Execute($droptempsql);
        if(!$result) return false;
        //end block

        // SQL to drop the original table
        $dropoldsql = 'DROP TABLE '.$orgTableName;
        
        // SQL to copy the new data back into the new table
        $newcolumns = ''; $oldcolumns = '';
        foreach($column_map as $oldcolumn => $newcolumn) {
            if($newcolumn != null) {
                $oldcolumns .= (($oldcolumns) ? ',':'') . $oldcolumn;
                $newcolumns .= (($newcolumns) ? ',':'') . $newcolumn;
            }
        }
        $copytonewsql = 'INSERT INTO '.$newTableName.'('.$newcolumns.') SELECT '.$oldcolumns.' FROM '.$tmpname;

//        echo "<pre>";
//        echo $createtemptableSQL . "<br/>";
//        echo $copytotempsql . "<br/>";
//        echo $dropoldsql . "<br/>";
//        echo $createnewtableSQL . "<br/>";
//        echo $copytonewsql . "<br/>";
//        echo $droptempsql. "<br/>";
//        echo "</pre>";
//        return true;

        // FIXME: I can't get this to work in a transaction
        if(!$this->Execute($createtemptableSQL)) return false;   // create temp table
        if(!$this->Execute($copytotempsql)) return false;        // copy original data to temp table
        if(!$this->Execute($dropoldsql)) return false;           // drop original table
        if(!$this->Execute($createnewtableSQL)) return false;    // recreate original table
        if(!$this->Execute($copytonewsql)) return false;         // copy data back to original table
        if(!$this->Execute($droptempsql)) return false;          // drop the temp table
        return true;
    }
}

?>