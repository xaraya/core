<?php
// Script tests API function version compare in the base module.
// Run this script from the site root directory.
// Author: Jason Judge
// Created: 4 June 2003

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

// Each test comprises:
// ver1 = first version to compare
// ver2 = second version to compare
// levels = number of levels to limit the comparison (null = default = 0 = unlimited)
// strict = flag to indicate strict numeric comparisons (null = default = true)
// result = expected result

$test_array = array(
   array('ver1'=>' .-1.2.sdf.3.  f6', 'ver2'=>'4.5.6', 'levels'=>4, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.2', 'ver2'=>'1.3', 'levels'=>null, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.3', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>null, 'result'=>1),
   array('ver1'=>'1.2', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>null, 'result'=>0),
   array('ver1'=>'1.2.1', 'ver2'=>'1.3.2', 'levels'=>null, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.2', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>null, 'result'=>0),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>null, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>0, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>3, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>2, 'strict'=>null, 'result'=>0),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>1, 'strict'=>null, 'result'=>0),
   array('ver1'=>'', 'ver2'=>'1.3.2', 'levels'=>null, 'strict'=>null, 'result'=>2),
   array('ver1'=>'1.3.2', 'ver2'=>'   ', 'levels'=>null, 'strict'=>null, 'result'=>1),
   array('ver1'=>'', 'ver2'=>'   ', 'levels'=>null, 'strict'=>null, 'result'=>0),
   array('ver1'=>'1.10', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>true, 'result'=>1),
   array('ver1'=>'1.10', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>false, 'result'=>1),
   array('ver1'=>'1.10', 'ver2'=>'1.2g', 'levels'=>null, 'strict'=>true, 'result'=>1),
   array('ver1'=>'1.10', 'ver2'=>'1.2g', 'levels'=>null, 'strict'=>false, 'result'=>2),
   array('ver1'=>'1.10', 'ver2'=>array(1,'2g'), 'levels'=>null, 'strict'=>null, 'result'=>1),
   array('ver1'=>array(1,10), 'ver2'=>'1.11', 'levels'=>null, 'strict'=>null, 'result'=>2),
   array('ver1'=>array(1,10), 'ver2'=>'1.9', 'levels'=>null, 'strict'=>null, 'result'=>1)
);
?>

<html>
<head><title>Version Compare tests</title></head>
<body>
<p><b>xarModAPIFunc('base', 'versions', 'compare', array('ver1'=>version1, 'ver2'=>version2 [, 'levels'=>level][, 'strict'=>strict-flag]))</b></p>
<table border="1">
   <tr>
      <th>Version 1</th>
      <th>Version 2</th>
      <th>Levels</th>
      <th>Strict Numeric</th>
      <th>Expected Result</th>
      <th>Actual Result</th>
      <th>Pass/Fail</th>
   </tr>
   <?
      foreach($test_array as $test) {
         $params = array('ver1'=>$test['ver1'], 'ver2'=>$test['ver2']);
         if (isset($test['levels'])) {$params['levels'] = $test['levels'];}
         if (isset($test['strict'])) {$params['strict'] = $test['strict'];}
         $actual = xarModAPIFunc('base', 'versions', 'compare', $params);
         if ($actual == $test['result']) {$status = 'Pass';} else {$status = '<b>Fail</b>';}
         if (!is_array($test['ver1'])) {$ver1 = '&quot;'.$test['ver1'].'&quot;';}
         else {ob_start(); var_dump($test['ver1']); $ver1 = ob_get_contents(); ob_end_clean();}
         if (!is_array($test['ver2'])) {$ver2 = '&quot;'.$test['ver2'].'&quot;';}
         else {ob_start(); var_dump($test['ver2']); $ver2 = ob_get_contents(); ob_end_clean();}
   ?>
      <tr>
      <td><?echo $ver1;?></td>
      <td><?echo $ver2;?></td>
      <td><?echo $test['levels'];?></td>
      <td><?if (isset($test['strict'])) echo $test['strict'] ? 'True' : 'False';?></td>
      <td><?echo $test['result'];?></td>
      <td><?echo $actual;?></td>
      <td><?echo $status;?></td>
      </tr>
   <?
       }
   ?>
</table>
</body>
</html>