<?php

/**
 * File: $Id$
 *
 * Poor mans debugger for xmlrpc
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage tests
 * @link  link to where more info can be found
 * @author author name <author@email> (this tag means responsible person)
*/

include "../../html/modules/xmlrpcserver/xarincludes/xmlrpc.inc";
include "../../html/modules/xmlrpcserver/xarincludes/xmlrpcs.inc";
?>
<html>
<head>
  <title>XML-RPC debugger</title>
</head>
<body>
<!-- Basic setting to start querying a specific server -->
<form action="xmlrpcdebugger.php" method="post">
  <div style="border: thin solid black; margin: 20pt; padding: 5pt; ">
    Server:<br/>
    <input type="textbox" size="80" value="<?php echo $server;?>" name="server"/><br/>
    Path:<br/>
    <input type="textbox" size="80" value="<?php echo $path;?>" name="path"/><br/>
    Port:<br/>
    <input type="textbox" size="5" value="<?php echo $port;?>" name="port"/>
    Show payloads:
    <input type="checkbox" name="debug" value="1" /><br/>
    <input type="hidden" name="listmethods" value="1" />
    <input type="submit" value="Query server"/>
  </div>
</form>
<?php
          //    echo "Server: $server<br/>";
          //echo "Path  : $path<br/>";
          //echo "Port  : $port<br/>";
          //echo $listmethods;

// system.listmethods
if ($listmethods == 1) {
    $client = new xmlrpc_client($path, $server,$port);
    if($debug == 1) $client->setDebug(1);
    $request = new xmlrpcmsg('system.listMethods');
    $response = $client->send($request);
    //var_dump($response);
    $methodobjects = $response->xv->me['array'];
?>
    <form action="xmlrpcdebugger.php" metho="post">
      <input type="hidden" name="server" value="<?php echo $server;?>">
      <input type="hidden" name="path"   value="<?php echo $path;?>">
      <input type="hidden" name="port"   value="<?php echo $port;?>">
      <input type="hidden" name="listmethods" value="1"/>
      <input type="hidden" name="methodhelp" value="1">
      <div style="border: thin solid black; margin: 10pt; padding: 5pt;">
        Methods available on this server:<br/>
        <select name="method">
    
<?php
    foreach($methodobjects as $methodobject) {
        if ($method == $methodobject->me['string']) {
            echo '<option selected="selected">';
        } else {
            echo '<option>';
        }
        echo $methodobject->me['string'] . '</option>';
    }
?>
        </select>
        <input type="submit" value="Show method"/> 
        Show payloads: <input type="checkbox" name="payloads2" value="1"/>
      </div>
    </form>
<?php
}

// system.methodHelp
if ($methodhelp == 1) {
    $client = new xmlrpc_client($path, $server,$port);
    if($payloads2 ==1) $client->setDebug(1);
    // Get the helptext for the method
    $request = new xmlrpcmsg('system.methodHelp',array(new xmlrpcval($method)));
    $response = $client->send($request);
    $methodHelp = $response->xv->me['string'];

    // Get the signature
    $request = new xmlrpcmsg('system.methodSignature',array(new xmlrpcval($method)));
    $response = $client->send($request);
    $methodSignatures = $response->xv->me['array'];
    echo "<h1>" .$method . "</h1>";
    echo "<h2>Description:</h2>";
    echo "<p>$methodHelp</p>";
    echo "<h2>Signatures:</h2>";
    foreach($methodSignatures as $methodSignature) {
        $sig = $methodSignature->me['array'];
      
        // First element is the return type, rest are arguments
        $syntax = $sig[0]->me['string'] . ' <b>' . $method . "</b>(";
        for($i=1; $i<sizeof($sig);$i++) {
            $syntax .= $sig[$i]->me['string'] .", ";
        }
        $syntax = rtrim($syntax,", ");
        $syntax .= ')';
        echo $syntax .'<br/>';
    }

}
?>
</body>
</html>