<?php
// call me on the commandline like this:
//
// shell> php -n testgenerator.php xarMyFileToBeTested.php
//

// specify file
$file = $argv[1];
if (empty($file)) {
	$file = 'xarEvt.php';
}

// get functions and names
$testFileName = 'test'.substr($file,3);
$testClassName = 'test'.substr($file,3,-4);
$oldState = get_defined_functions();
include($file);
$newState = get_defined_functions();
$diff = array_diff($newState['user'], $oldState['user']);

// initialise skeleton
$skel = array();

// build skeleton
$skel[] = '<?php';
$skel[] = ' ';
$skel[] = 'class '.$testClassName.' extends xarTestCase {';
$skel[] = ' ';
$skel[] = '    function setup() {';
$skel[] = "        include('".$file."');";
$skel[] = '    }';
$skel[] = ' ';
$skel[] = '    function precondition() {';
$skel[] = '        return true;';
$skel[] = '    }';
$skel[] = ' ';
$skel[] = '    function teardown () {';
$skel[] = ' ';
$skel[] = '    }';
$skel[] = ' ';
$skel[] = '    // following function are proposals. please change according to your needs';
$skel[] = ' ';
foreach($diff as $func) {
	$skel[] = '    function test'.substr($func,3).' {';
	$skel[] = '        return $this->assertTrue('.$func.'([enter arguments]),"[Test name]");';
	$skel[] = '    }';
	$skel[] = ' ';
}

$skel[] = '}';
$skel[] = ' ';
$skel[] = '$suite->AddTestCase(\''.$testClassName.'\',\'Testing [enter your testcase name]\');';
$skel[] = ' ';
$skel[] = '?>';

// write file
if (!$handle = fopen($testFileName, 'w+')) {
	print "Cannot open file ($testFileName)";
    exit;
}
foreach($skel as $line) {
    if (!fwrite($handle, $line."\r\n")) {
        print "Cannot write to file ($testFileName)";
        exit;
    }
}

// echo message     
echo "File ($testFileName) successfully generated.\r\n";
    
fclose($handle);
?>