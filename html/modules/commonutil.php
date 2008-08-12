<?php
//Psspl:Added file for debugging. 
/**
 * TracePrint function act as debbugger.
 * 
 * @param  $Trace
 * @param  $varName string
 * @param  $tracelevel int
 *
 */

function TracePrint($Trace=null,$varName=null,$tracelevel = 0)
{
	if(SystemTraceLevel() > $tracelevel)
	{	
		echo"<b>$varName</b>-----------------<br>";
		echo"<pre>";
		print_r($Trace);
		echo"</pre>";
		echo"<br>-----------------";
	}
}
/**
 * System Trace set the level for showing debug message.
 *
 * @return unknown
 */
function SystemTraceLevel()
{
	return 0; // for desebulling message return 0;
}

?>