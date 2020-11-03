<?php
/**
 * parse_core_files:
 *     Find all global functions defined in lib/xaraya and save to core_functions.json
 *     Find all global constants defined in lib/xaraya and save to core_constants.json
 * search_module_files:
 *     Search all module files for global functions and constants, and optionally replace
 */
require dirname(dirname(__DIR__)).'/vendor/autoload.php';
//use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

// See https://github.com/nikic/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown
function parse_core_files($inDir)
{
    // iterate over all .php files in the directory
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($inDir));
    $files = new \RegexIterator($files, '/\.php$/');

    $factory = \phpDocumentor\Reflection\Php\ProjectFactory::createInstance();
    $localFiles = array();
    foreach ($files as $file) {
        try {
            echo $file->getPathName() . "\n";
            $localFiles[] = new \phpDocumentor\Reflection\File\LocalFile($file->getPathName());
        } catch (Exception $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    }
    $project = $factory->create('MyProject', $localFiles);
    $totals = array('namespaces' => 0, 'files' => 0, 'includes' => 0, 'constants' => 0, 'functions' => 0, 'classes' => 0, 'interfaces' => 0, 'traits' => 0, 'class_const' => 0, 'methods' => 0);
    //var_dump(array_keys($project->getNamespaces()));
    $totals['namespaces'] = count($project->getNamespaces());
    //$root = $project->getRootNamespace();
    $functions = array();
    $constants = array();
    $classes = array();
    $missing = array();
    //$printer = new PrettyPrinter();
    foreach ($project->getFiles() as $file) {
        $fpath = $file->getPath();
        $totals['files'] += 1;
        $totals['includes'] += count($file->getIncludes());
        $totals['functions'] += count($file->getFunctions());
        foreach ($file->getFunctions() as $function) {
            $name = $function->getName();
            $lname = strtolower($name);
            if (array_key_exists($lname, $functions)) {
                echo 'Function Conflict: ' . $name . "\n";
            }
            $args = array();
            foreach ($function->getArguments() as $arg) {
                $args[] = ($arg->getType() != 'mixed' ? $arg->getType() . ' ' : '') . ($arg->isVariadic() ? '...' : '') . ($arg->isByReference() ? '&' : '') . '$' . $arg->getName() . ($arg->getDefault() !== null ? ' = ' . $arg->getDefault() : '');
            }
            $functions[$lname] = array('file' => $fpath, 'name' => $name, 'args' => $args);
            if (preg_match('/^(xar[A-Z][a-z]+?)([A-Z].+|_(.+))$/', $name, $matches) || preg_match('/^(xar[A-Z]+)([A-Z].+|_(.+))$/', $name, $matches)) {
                $functions[$lname]['class'] = $matches[1];
                if (!empty($matches[3])) {
                    $functions[$lname]['check'] = $matches[3];
                } else {
                    $functions[$lname]['check'] = $matches[2];
                }
            }
            $functions[$lname]['line'] = $function->getLocation()->getLineNumber();
        }
        $totals['constants'] += count($file->getConstants());
        foreach ($file->getConstants() as $constant) {
            $name = $constant->getName();
            $lname = strtolower($name);
            if (array_key_exists(strtolower($lname), $constants)) {
                echo 'Constant Conflict: ' . $name . "\n";
            }
            $constants[$lname] = array('file' => $fpath, 'name' => $name, 'value' => $constant->getValue());
            if (preg_match('/^([A-Za-z]+)_(.+)$/', $name, $matches)) {
                $constants[$lname]['class'] = $matches[1];
                $constants[$lname]['check'] = $matches[2];
            }
        }
        $totals['classes'] += count($file->getClasses());
        foreach ($file->getClasses() as $class) {
            $name = $class->getName();
            $lname = strtolower($name);
            if (array_key_exists(strtolower($lname), $classes)) {
                echo 'Class Conflict: ' . $name . "\n";
            }
            $classes[$lname] = array('file' => $fpath, 'name' => $name, 'methods' => array(), 'const' => array());
            $totals['methods'] += count($class->getMethods());
            foreach ($class->getMethods() as $method) {
                $mname = $method->getName();
                $args = array();
                foreach ($method->getArguments() as $arg) {
                    $args[] = ($arg->getType() != 'mixed' ? $arg->getType() . ' ' : '') . ($arg->isVariadic() ? '...' : '') . ($arg->isByReference() ? '&' : '') . '$' . $arg->getName() . ($arg->getDefault() !== null ? ' = ' . $arg->getDefault() : '');
                }
                $classes[$lname]['methods'][strtolower($mname)] = array('name' => $mname, 'args' => $args);
            }
            $totals['class_const'] += count($class->getConstants());
            foreach ($class->getConstants() as $constant) {
                $cname = $constant->getName();
                $classes[$lname]['const'][strtolower($cname)] = array('name' => $cname, 'value' => $constant->getValue());
            }
        }
        $totals['interfaces'] += count($file->getInterfaces());
        $totals['traits'] += count($file->getTraits());
    }
    echo json_encode($totals, JSON_PRETTY_PRINT);
    $found = 0;
    foreach (array_keys($functions) as $lname) {
        $function = $functions[$lname];
        if (!isset($function['class'])) {
            echo $function['name'] . ' SKIP ' . "\n";
            continue;
        }
        $cname = strtolower($function['class']);
        if (isset($classes[$cname])) {
            $class = $classes[$cname];
            $mname = strtolower($function['check']);
            if (isset($class['methods'][$mname])) {
                $method = $class['methods'][$mname];
                echo $function['name'] . ' ' . $class['name'] . '::' . $method['name'] . "\n";
                $functions[$lname]['class'] = $class['name'];
                $functions[$lname]['method'] = $method['name'];
                $functions[$lname]['margs'] = $method['args'];
                $found += 1;
                continue;
            }
        }
        echo $function['name'] . ' TODO: ' . $function['line'] . ' ' . $function['file'] . "\n";
        $file = $project->getFiles()[$function['file']];
        $lines = array_slice(explode("\n", $file->getSource()), $function['line'] - 1);
        //echo implode("\n", $lines);
        foreach ($lines as $line) {
            if (strpos($line, ' return ') !== false) {
                if (preg_match('/ return (\w+)::(\w+)\(([^\)]*)/', $line, $matches)) {
                    echo $function['name'] . ' FOUND ' . $matches[1] . '::' . $matches[2] . ' ' . $function['file'] . "\n";
                    $functions[$lname]['class'] = $matches[1];
                    $functions[$lname]['method'] = $matches[2];
                    $functions[$lname]['rargs'] = $matches[3];
                    $found += 1;
                } else {
                    echo $function['name'] . ' LOST ' . "\n";
                }
                break;
            }
        }
    }
    file_put_contents('core_functions.json', json_encode($functions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
    echo 'Found Functions: ' . $found . "\n";
    $found = 0;
    foreach (array_keys($constants) as $lname) {
        $constant = $constants[$lname];
        if (!isset($constant['class'])) {
            echo $constant['name'] . ' SKIP ' . "\n";
            continue;
        }
        $cname = strtolower($constant['class']);
        if (isset($classes[$cname])) {
            $class = $classes[$cname];
            $cname = strtolower($constant['check']);
            if (isset($class['const'][$cname])) {
                $const = $class['const'][$cname];
                if ($const['value'] !== $constant['value']) {
                    echo $constant['name'] . ' (' . $constant['value'] . ') != ' . $class['name'] . '::' . $const['name'] . ' (' . $const['value'] . ")\n";
                    exit;
                }
                echo $constant['name'] . ' ' . $class['name'] . '::' . $const['name'] . "\n";
                $constants[$lname]['class'] = $class['name'];
                $constants[$lname]['const'] = $const['name'];
                $found += 1;
                continue;
            }
        }
    }
    file_put_contents('core_constants.json', json_encode($constants, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
    echo 'Found Constants: ' . $found . "\n";
}

function search_module_files($inDir, $fixMe=false)
{
    $contents = file_get_contents('core_functions.json');
    $functions = json_decode($contents, true);
    $contents = file_get_contents('core_constants.json');
    $constants = json_decode($contents, true);
    $search = array();
    $replace = array();
    foreach ($functions as $lname => $function) {
        if (empty($function['class']) || empty($function['method'])) {
            continue;
        }
        // @checkme security.php is still messed up
        if (strpos($function['file'], 'security.php') !== false) {
            continue;
        }
        $search[] = $function['name'];
        $replace[] = $function['class'] . '::' . $function['method'];
    }
    foreach ($constants as $lname => $constant) {
        if (empty($constant['class']) || empty($constant['const'])) {
            continue;
        }
        // @checkme security.php is still messed up
        if (strpos($constant['file'], 'security.php') !== false) {
            continue;
        }
        $search[] = $constant['name'];
        $replace[] = $constant['class'] . '::' . $constant['const'];
    }
    echo 'Functions: ' . count($functions) . ' - Constants: ' . count($constants) . ' - Replace: ' . count($replace) . "\n";
    $pattern = '/' . implode('|', $search) . '/';
    // iterate over all .php, .inc, .xt, .xml and .xsl files in the directory
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($inDir));
    $files = new \RegexIterator($files, '/\.(php|inc|xt|xml|xsl)$/');

    $todo = array();
    foreach ($files as $file) {
        try {
            $contents = file_get_contents($file->getPathName());
            if (!preg_match_all($pattern, $contents, $matches)) {
                continue;
            }
            echo $file->getPathName() . ' - ' . count($matches[0]) . ' matches: ' . implode(', ', array_unique($matches[0])) . "\n";
            $todo[] = $file->getPathName();
        } catch (Exception $e) {
            echo 'Parse Error: ', $e->getMessage();
            exit;
        }
    }
    echo 'Found ' . count($todo) . ' files to fix' . "\n";
    if (!$fixMe) {
        echo 'Set $fixMe = true; to fix' . "\n";
        return;
    }
    foreach ($todo as $filepath) {
        echo 'Fixing ' . $filepath . "\n";
        $contents = file_get_contents($filepath);
        $contents = str_replace($search, $replace, $contents);
        file_put_contents($filepath, $contents);
    }
}

$refresh = false;
if ($refresh || !file_exists('core_functions.json') || !file_exists('core_constants.json')) {
    $inDir = dirname(dirname(__DIR__)).'/html/lib/xaraya';
    parse_core_files($inDir);
}

$fixMe = false;
$inDir = dirname(dirname(__DIR__)).'/html/code/modules/';
//$inDir = dirname(dirname(__DIR__)).'/html/themes/';
search_module_files($inDir, $fixMe);
