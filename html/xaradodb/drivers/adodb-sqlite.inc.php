<?php
/*
V4.20 22 Feb 2004  (c) 2000-2004 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.

  Latest version is available at http://php.weblogs.com/
  
  SQLite info: http://www.hwaci.com/sw/sqlite/
    
  Install Instructions:
  ====================
  1. Place this in adodb/drivers
  2. Rename the file, remove the .txt prefix.
*/

class ADODB_sqlite extends ADOConnection {
	var $databaseType = "sqlite";
    // XARAYA MODIFICATION
    var $dataProvider = 'sqlite';
    // END XARAYA MODIFICATION
	var $replaceQuote = "''"; // string to use to replace quotes
	var $concat_operator='||';
	var $_errorNo = 0;
	var $hasLimit = true;	
	var $hasInsertID = true; 		/// supports autoincrement ID?
	var $hasAffectedRows = true; 	/// supports affected rows for update/delete?
	var $metaTablesSQL = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name";
	var $sysDate = "adodb_date('Y-m-d')";
	var $sysTimeStamp = "adodb_date('Y-m-d H:i:s')";
	var $fmtTimeStamp = "'Y-m-d H:i:s'";
	
	function ADODB_sqlite() 
	{
	}
	
/*
  function __get($name) 
  {
  	switch($name) {
	case 'sysDate': return "'".date($this->fmtDate)."'";
	case 'sysTimeStamp' : return "'".date($this->sysTimeStamp)."'";
	}
  }*/
	
	function ServerInfo()
	{
		$arr['version'] = sqlite_libversion();
		$arr['description'] = 'SQLite ';
		$arr['encoding'] = sqlite_libencoding();
		return $arr;
	}
	
	function BeginTrans()
	{	  
		 if ($this->transOff) return true; 
		 $ret = $this->Execute("BEGIN TRANSACTION");
		 $this->transCnt += 1;
		 return true;
	}
	
	function CommitTrans($ok=true) 
	{ 
		if ($this->transOff) return true; 
		if (!$ok) return $this->RollbackTrans();
		$ret = $this->Execute("COMMIT");
		if ($this->transCnt>0)$this->transCnt -= 1;
		return !empty($ret);
	}
	
	function RollbackTrans()
	{
		if ($this->transOff) return true; 
		$ret = $this->Execute("ROLLBACK");
		if ($this->transCnt>0)$this->transCnt -= 1;
		return !empty($ret);
	}

	function _insertid()
	{
		return sqlite_last_insert_rowid($this->_connectionID);
	}
	
	function _affectedrows()
	{
        return sqlite_changes($this->_connectionID);
    }
	
	function ErrorMsg() 
 	{
		if ($this->_logsql) return $this->_errorMsg;
		return ($this->_errorNo) ? sqlite_error_string($this->_errorNo) : '';
	}
 
	function ErrorNo() 
	{
		return $this->_errorNo;
	}
	
	function SQLDate($fmt, $col=false)
	{
		$fmt = $this->qstr($fmt);
		return ($col) ? "adodb_date2($fmt,$col)" : "adodb_date($fmt)";
	}
	
	function &MetaColumns($tab)
	{
	global $ADODB_FETCH_MODE;
	
		$rs = $this->Execute("select * from $tab limit 1");
		if (!$rs) return false;
		$arr = array();
		for ($i=0,$max=$rs->_numOfFields; $i < $max; $i++) {
			$fld =& $rs->FetchField($i);
			if ($ADODB_FETCH_MODE == ADODB_FETCH_NUM) $retarr[] =& $fld;	
			else $arr[strtoupper($fld->name)] =& $fld;
		}
		$rs->Close();
		return $arr;
	}
	
	function _createFunctions()
	{
		@sqlite_create_function($this->_connectionID, 'adodb_date', 'adodb_date', 1);
		@sqlite_create_function($this->_connectionID, 'adodb_date2', 'adodb_date2', 2);
	}
	

	// returns true or false
	function _connect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		if (!function_exists('sqlite_open')) return false;
		
        // XARAYA MODIFICATION
        // It makes more sense to interpret host=directory and dbname=file
        // $this->_connectionID = sqlite_open($argHostname);
        $this->_connectionID = sqlite_open($argHostname . "/" . $argDatabasename);
        // END XARYA MODIFICATION
		if ($this->_connectionID === false) return false;
		$this->_createFunctions();
		return true;
	}
	
	// returns true or false
	function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		if (!function_exists('sqlite_open')) return false;
		
        // XARAYA MODIFICATION
        // It makes more sense to interpre host=directory and dbname=file
        // $this->_connectionID = sqlite_popen($argHostname);
        $this->_connectionID = sqlite_popen($argHostname . "/" . $argDatabasename);
        // END XARAYA MODIFICATION
		if ($this->_connectionID === false) return false;
		$this->_createFunctions();
		return true;
	}

	// returns query ID if successful, otherwise false
	function _query($sql,$inputarr=false)
	{
		$rez = sqlite_query($sql,$this->_connectionID);
		if (!$rez) {
			$this->_errorNo = sqlite_last_error($this->_connectionID);
		}
		
		return $rez;
	}
    
    // XARAYA MODIFICATION
    // returns true or false
    function SelectDB($dbName)
    {
        return file_exists($this->host . '/' . $this->database);
    }
    // END XARAYA MODIFICATION
	
	function &SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0) 
	{
		$offsetStr = ($offset >= 0) ? " OFFSET $offset" : '';
		$limitStr  = ($nrows >= 0)  ? " LIMIT $nrows" : ($offset >= 0 ? ' LIMIT 999999999' : '');
	  	if ($secs2cache)
	   		$rs =& $this->CacheExecute($secs2cache,$sql."$limitStr$offsetStr",$inputarr);
	  	else
	   		$rs =& $this->Execute($sql."$limitStr$offsetStr",$inputarr);
			
		return $rs;
	}
	
	/*
		This algorithm is not very efficient, but works even if table locking
		is not available.
		
		Will return false if unable to generate an ID after $MAXLOOPS attempts.
	*/
	var $_genSeqSQL = "create table %s (id integer)";
	
	function GenID($seq='adodbseq',$start=1)
	{	
        // XARAYA MODIFICATION
        // If using bindvars, this is perfectly sufficient (and we should use bindvars ;-) )
        return null;
        // END XARAYA MODIFICATION
		// if you have to modify the parameter below, your database is overloaded,
		// or you need to implement generation of id's yourself!
		$MAXLOOPS = 100;
		//$this->debug=1;
		while (--$MAXLOOPS>=0) {
			$num = $this->GetOne("select id from $seq");
			if ($num === false) {
				$this->Execute(sprintf($this->_genSeqSQL ,$seq));	
				$start -= 1;
				$num = '0';
				$ok = $this->Execute("insert into $seq values($start)");
				if (!$ok) return false;
			} 
			$this->Execute("update $seq set id=id+1 where id=$num");
			
			if ($this->affected_rows() > 0) {
				$num += 1;
				$this->genID = $num;
				return $num;
			}
		}
		if ($fn = $this->raiseErrorFn) {
			$fn($this->databaseType,'GENID',-32000,"Unable to generate unique id after $MAXLOOPS attempts",$seq,$num);
		}
		return false;
	}

	function CreateSequence($seqname='adodbseq',$start=1)
	{
		if (empty($this->_genSeqSQL)) return false;
		$ok = $this->Execute(sprintf($this->_genSeqSQL,$seqname));
		if (!$ok) return false;
		$start -= 1;
		return $this->Execute("insert into $seqname values($start)");
	}
	
	var $_dropSeqSQL = 'drop table %s';
	function DropSequence($seqname)
	{
		if (empty($this->_dropSeqSQL)) return false;
		return $this->Execute(sprintf($this->_dropSeqSQL,$seqname));
	}
	
	// returns true or false
	function _close()
	{
		return @sqlite_close($this->_connectionID);
	}

    // XARAYA MODIFICATION
    /*
     * SQLite doesn't have an ALTER TABLE statement and we do want to have it, 
     * try to detect it in the incoming $sql and translate this into SQLite 
     * equivalent statements
     * The alter table emulation was taken from: http://code.jenseng.com/db/sql.txt
     */
    function &Execute($sql,$inputarr=false)
    {
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
            return $result;
        } 
        // Otherwise use the normal execute method
        return parent::Execute($sql,$inputarr);
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
    // END XARAYA MODIFICATION
}

/*--------------------------------------------------------------------------------------
		 Class Name: Recordset
--------------------------------------------------------------------------------------*/

class ADORecordset_sqlite extends ADORecordSet {

	var $databaseType = "sqlite";
	var $bind = false;

	function ADORecordset_sqlite($queryID,$mode=false)
	{
		
		if ($mode === false) { 
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		switch($mode) {
		case ADODB_FETCH_NUM: $this->fetchMode = SQLITE_NUM; break;
		case ADODB_FETCH_ASSOC: $this->fetchMode = SQLITE_ASSOC; break;
		default: $this->fetchMode = SQLITE_BOTH; break;
		}
		
		$this->_queryID = $queryID;
	
		$this->_inited = true;
		$this->fields = array();
		if ($queryID) {
			$this->_currentRow = 0;
			$this->EOF = !$this->_fetch();
			@$this->_initrs();
		} else {
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
			$this->EOF = true;
		}
		
		return $this->_queryID;
	}


	function &FetchField($fieldOffset = -1)
	{
		$fld = new ADOFieldObject;
		$fld->name = sqlite_field_name($this->_queryID, $fieldOffset);
		$fld->type = 'VARCHAR';
		$fld->max_length = -1;
		return $fld;
	}
	
   function _initrs()
   {
		$this->_numOfRows = @sqlite_num_rows($this->_queryID);
		$this->_numOfFields = @sqlite_num_fields($this->_queryID);
   }

	function Fields($colname)
	{
		if ($this->fetchMode != SQLITE_NUM) return $this->fields[$colname];
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		
		 return $this->fields[$this->bind[strtoupper($colname)]];
	}
	
   function _seek($row)
   {
   		return sqlite_seek($this->_queryID, $row);
   }

	function _fetch($ignore_fields=false) 
	{
		$this->fields = @sqlite_fetch_array($this->_queryID,$this->fetchMode);
		return !empty($this->fields);
	}
	
	function _close() 
	{
	}

}
?>