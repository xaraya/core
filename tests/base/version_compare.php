<?php
/**
 * File: $Id: s.xaruser.php 1.16 03/04/07 04:30:01-04:00 johnny@falling.local.lan $
 *
 * Tests for base API function versions/compare
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */

// Script tests API function version compare in the base module.
// Run this script from the site root directory.

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

// Each test comprises:
// ver1 = first version to compare
// ver2 = second version to compare
// levels = number of levels to limit the comparison (null = default = 0 = unlimited)
// strict = flag to indicate strict numeric comparisons (null = default = true)
// sep = separator character
// notes = notes on the test
// result = expected result

$test_array = array(
   array('ver1'=>' .-1.2.sdf.3.  f6', 'ver2'=>'0.1.2.0.3.7', 'levels'=>6, 'strict'=>null, 'result'=>1, 'sep'=>null,
      'notes'=>'" .-1.2.sdf.3.  f6" is cleaned up to become 0.1.2.0.3.6'),
   array('ver1'=>' .-1.2.sdf.3.  f6', 'ver2'=>'0.1.2.0.3.7', 'levels'=>5, 'strict'=>null, 'result'=>0, 'sep'=>null,
      'notes'=>'" .-1.2.sdf.3.  f6" is cleaned up to become 0.1.2.0.3.6'),
   array('ver1'=>'1.2', 'ver2'=>'1.3', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>null),
   array('ver1'=>'1.3', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>null, 'result'=>-1, 'sep'=>null),
   array('ver1'=>'1.2', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>null, 'result'=>0, 'sep'=>null),
   array('ver1'=>'1.2.1', 'ver2'=>'1.3.2', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>null),
   array('ver1'=>'1.2', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>null, 'result'=>0, 'sep'=>null),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>null),
   array('ver1'=>'0.0.0.0.0.0.0.0.0.0.0.2', 'ver2'=>'0.0.0.0.0.0.0.0.0.0.0.1', 'levels'=>null, 'strict'=>null, 'result'=>-1, 'sep'=>null),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>0, 'strict'=>null, 'result'=>1, 'sep'=>null,
      'notes'=>'0 means all levels'),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>3, 'strict'=>null, 'result'=>1, 'sep'=>null),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>2, 'strict'=>null, 'result'=>0, 'sep'=>null,
      'notes'=>'At two levels or fewer these versions look identical'),
   array('ver1'=>'1.3.1', 'ver2'=>'1.3.2', 'levels'=>1, 'strict'=>null, 'result'=>0, 'sep'=>null),
   array('ver1'=>'', 'ver2'=>'1.3.2', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>null),
   array('ver1'=>'1.3.2', 'ver2'=>'   ', 'levels'=>null, 'strict'=>null, 'result'=>-1, 'sep'=>null),
   array('ver1'=>'', 'ver2'=>'   ', 'levels'=>null, 'strict'=>null, 'result'=>0, 'sep'=>null),
   array('ver1'=>'1.10', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>true, 'result'=>-1, 'sep'=>null),
   array('ver1'=>'1.10', 'ver2'=>'1.2', 'levels'=>null, 'strict'=>false, 'result'=>-1, 'sep'=>null),
   array('ver1'=>'1.10', 'ver2'=>'1.2g', 'levels'=>null, 'strict'=>true, 'result'=>-1, 'sep'=>null,
       'notes'=>'Number 10 is numerically greater than 2 (stripped of the "g")'),
   array('ver1'=>'1.10', 'ver2'=>'1.2g', 'levels'=>null, 'strict'=>false, 'result'=>1, 'sep'=>null,
      'notes'=>'String "2g" is greater than string "10"'),
   array('ver1'=>'1.10', 'ver2'=>array(1,'2g'), 'levels'=>null, 'strict'=>null, 'result'=>-1, 'sep'=>null),
   array('ver1'=>array(1,10), 'ver2'=>'1.11', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>null),
   array('ver1'=>array(1,10), 'ver2'=>'1.9', 'levels'=>null, 'strict'=>null, 'result'=>-1, 'sep'=>null),
   array('ver1'=>array(1,10), 'ver2'=>'1:11', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>':'),
   array('ver1'=>'4-5-6-7', 'ver2'=>'5-6-7-8-9-0', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>'-'),
   array('ver1'=>'5-6-7', 'ver2'=>'4-5-6-7-8-9-0', 'levels'=>null, 'strict'=>null, 'result'=>-1, 'sep'=>'-',
       'notes'=>'As a version 5.6.7 is greater than 4.5.6...'),
   array('ver1'=>'5-6-7', 'ver2'=>'4-5-6-7-8-9-0', 'levels'=>null, 'strict'=>null, 'result'=>1, 'sep'=>'x',
       'notes'=>'As a number 4567890 is greater than 567'),
   array('ver1'=>'5-6-7', 'ver2'=>'4-5-6-7-8-9-0', 'levels'=>null, 'strict'=>false, 'result'=>-1, 'sep'=>'x',
      'notes' => 'As a string "5-6-7" is alpha-numerically greater than "4-5-6..."')
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
      <th>Separator</th>
      <th>Expected Result</th>
      <th>Actual Result</th>
      <th>Pass/Fail</th>
      <th>Notes</th>
   </tr>
   <?
      foreach($test_array as $test) {
         $params = array('ver1'=>$test['ver1'], 'ver2'=>$test['ver2']);
         if (isset($test['levels'])) {$params['levels'] = $test['levels'];}
         if (isset($test['strict'])) {$params['strict'] = $test['strict'];}
         if (isset($test['sep'])) {$params['sep'] = $test['sep'];}
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
      <td><?echo $test['sep'];?></td>
      <td><?echo $test['result'];?></td>
      <td><?echo $actual;?></td>
      <td><?echo $status;?></td>
      <td><?echo $test['notes'];?></td>
      </tr>
   <?
       }
   ?>
</table>
</body>
</html>