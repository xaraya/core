<?php
/**
* @version V2.42 4 Oct 2002 (c) 2000-2002 John Lim (jlim@natsoft.com.my). All rights reserved.
* Released under both BSD license and Lesser GPL library license.
* Whenever there is any discrepancy between the two licenses,
* the BSD license will take precedence.
*
* Set tabs to 4 for best viewing.
*
* Latest version is available at http://php.weblogs.com
*
*  Portable MSSQL Driver that supports || instead of +
*
*/
include_once(ADODB_DIR.'/drivers/adodb-mssql.inc.php');

class ADODB_mssqlpo extends ADODB_mssql {
	var $databaseType = "mssqlpo";
	
	function _query($sql,$inputarr)
	{
		return ADODB_mssql::_query(str_replace('||','+',$sql),$inputarr);
	}
}

class ADORecordset_mssqlpo extends ADORecordset_mssql {
	var $databaseType = "mssqlpo";
	function ADORecordset_mssqlpo($id)
	{
		$this->ADORecordset_mssql($id);
	}
}
?>