<?php
/**
 * call me on the commandline like this:
 *
 * shell> php -n testgenerator.php xarMyFileToBeTested.php
 */

// define function
if (!function_exists('file_get_contents')) {
	// you are using php < 4.3.0 => you should consider an upgrade
	function file_get_contents($_file) {
		if (file_exists($_file)) {
			ob_start();
			$retval = readfile($_file);
			// no readfile error
			if (false !== $retval) {
				$retval = ob_get_contents();
				ob_end_clean();
				return($retval);
			}
		}
		return(false);
	}
}

function parse_php($_filecontent) {
	$functionPattern = '°function .*?\)°';
	$classesPattern  = '°class .*? °';
//	$classesPattern  = '°class .*?\\}\\}°';

	preg_match_all($classesPattern, $_filecontent, $classes);
	preg_match_all($functionPattern, $_filecontent, $functions);

	$returnval = array('classes' => $classes[0], 'functions' => $functions[0]);
	return($returnval);
}

// main

// no file specified
if (empty($argv[1])) {
	echo("usage: php -n {$argv[0]} file.php\r\n");
	exit();
}

$filecontent = file_get_contents($argv[1]);

if (false == $filecontent) {
	echo("file not found or not readable");
	exit();
}


if (empty($filecontent)) {
	echo("usage: php -n {$argv[0]} file.php\r\n");
	exit();
}

if(!$result = parse_php($filecontent)) {
	echo("parser failed\r\n");
	exit();
}

print_r($result);
echo("parser finished\r\n");


// get functions and names
/*
$testFileName = 'test'.substr($file,3);
$testClassName = 'test'.substr($file,3,-4);
$oldFuncs = get_defined_functions();
$oldClasses = get_declared_classes();
@include($file);
$newFuncs = get_defined_functions();
$newClasses = get_declared_classes();
$diffFuncs = array_diff($newFuncs['user'], $oldFuncs['user']);

print_r($oldClasses);
print_r($newClasses);

// initialise skeleton
$skel = array();

// build skeleton
$skel[] = '<?p'.'hp'; // split because editor doesnt like it otherwise
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
$skel[] = '    function teardown() {';
$skel[] = ' ';
$skel[] = '    }';
$skel[] = ' ';
$skel[] = '    // following function are proposals. please change according to your needs';
$skel[] = ' ';
foreach($diffFuncs as $func) {
	$skel[] = '    function test'.substr($func,3).'() {';
	$skel[] = '        return $this->assertTrue('.$func.'([enter arguments]),"[Test name]");';
	$skel[] = '    }';
	$skel[] = ' ';
}

// FIXME: We might consider generating a specific testsuite for this file
// otherwise all tests will fall under the default testcase which can get
// messy.
$skel[] = '}';
$skel[] = ' ';
$skel[] = '$suite->AddTestCase(\''.$testClassName.'\',\'Testing [enter your testcase name]\');';
$skel[] = ' ';
$skel[] = '?'.'>'; // split because editor doesnt like it otherwise

$content = implode("\r\n",$skel);

// write file
if (!$handle = fopen($testFileName, 'w+')) {
	echo("Cannot open file ($testFileName)");
    exit;
}

if (!fwrite($handle, $content)) {
	echo("Cannot write to file ($testFileName)");
	exit;
}

// echo message     
echo("File ($testFileName) successfully generated.\r\n");
    
fclose($handle);
*/
?>