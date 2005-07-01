<?php
/*
 V4.60 24 Jan 2005  (c) 2000-2005 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license. 
  Whenever there is any discrepancy between the two licenses, 
  the BSD license will take precedence.
  Set tabs to 4.
  
  For Xaraya, we're not using IDENTITY here but GenID()
*/

// set some decent limits for textsize and textlimit if possible (otherwise it defaults to 4096 bytes)
@ini_set('mssql.textsize',2147483647);
@ini_set('mssql.textlimit',2147483647);

include_once(ADODB_DIR . '/drivers/adodb-mssql.inc.php');

class ADODB_xarmssql extends ADODB_mssql
{
    function _insertid()
    {
        // return the GenID value
        return $this->genID;
    }

    function GenID($seq='adodbseq',$start=1)
    {
        //$this->debug=1;
        $this->Execute('BEGIN TRANSACTION adodbseq');
        // skip raising errors on update here, since the sequence table might not exist yet
        $fn = $this->raiseErrorFn;
        $this->raiseErrorFn = null;
        $ok = $this->Execute("update seq$seq with (tablock,holdlock) set id = id + 1");
        $this->raiseErrorFn = $fn;
        if (!$ok) {
            $this->Execute("create table seq$seq (id float(53))");
            $ok = $this->Execute("insert into seq$seq with (tablock,holdlock) values($start)");
            if (!$ok) {
                $this->Execute('ROLLBACK TRANSACTION adodbseq');
                return false;
            }
            $this->Execute('COMMIT TRANSACTION adodbseq'); 
            $this->genID = $start;
            return $start;
        }
        $num = $this->GetOne("select id from seq$seq");
        $this->Execute('COMMIT TRANSACTION adodbseq'); 

        $this->genID = $num;
        return $num;
        
        // in old implementation, pre 1.90, we returned GUID...
        //return $this->GetOne("SELECT CONVERT(varchar(255), NEWID()) AS 'Char'");
    }

}

?>
