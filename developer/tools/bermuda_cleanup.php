<?php

/**
 * parse_core_files:
 *     Find all global functions defined in lib/xaraya and save to core_functions.json
 *     Find all global constants defined in lib/xaraya and save to core_constants.json
 * search_module_files:
 *     Search all module files for global functions and constants, and optionally replace
 *
 * Requirements:
 *
 * composer require --dev phpdocumentor/reflection
 *
 * + comment out the if (!function_exists('xarML')) {...} part in html/lib/xaraya/mls.php
 * to avoid Reflection error finding the file for that function, when updating core files
 *
 */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
//use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
sys::init();

if (!function_exists('xarML')) {
    function xarML($rawstring, ...$args)
    {
        return xarMLS::translate($rawstring, ...$args);
    }
}

class XarayaCodeAnalyzer
{
    public const PHP_EXT = '/\.php$/';

    public $project = null;
    public $functions = [];
    public $constants = [];
    public $classes = [];
    public $totals = [];
    public $inDir = null;
    public $skipVendor = false;
    public $fileExt = null;
    public $verbose = false;
    public $refresh = false;

    public function __construct($inDir = null, $skipVendor = false, $fileExt = self::PHP_EXT)
    {
        $this->initialize($inDir, $skipVendor, $fileExt);
    }

    public function initialize($inDir, $skipVendor, $fileExt)
    {
        if (!empty($inDir)) {
            $this->inDir = $inDir;
        }
        if (!empty($skipVendor)) {
            $this->skipVendor = $skipVendor;
        }
        if (!empty($fileExt)) {
            $this->fileExt = $fileExt;
        }
        $this->functions = [];
        $this->constants = [];
        $this->classes = [];
        $this->totals = [
            'namespaces' => 0,
            'files' => 0,
            'includes' => 0,
            'constants' => 0,
            'functions' => 0,
            'classes' => 0,
            'interfaces' => 0,
            'traits' => 0,
            'class_const' => 0,
            'methods' => 0,
        ];
    }

    public function log($message, $always = false)
    {
        if ($always || $this->verbose) {
            echo $message . "\n";
        }
    }

    public function to_json($var)
    {
        return json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }

    // See https://github.com/nikic/PHP-Parser/blob/master/doc/2_Usage_of_basic_components.markdown
    public function load_project($inDir = null, $extraFiles = [], $skipVendor = false, $fileExt = self::PHP_EXT)
    {
        $this->initialize($inDir, $skipVendor, $fileExt);

        // iterate over all .php files in the directory
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->inDir));
        $files = new \RegexIterator($files, $this->fileExt);

        $factory = \phpDocumentor\Reflection\Php\ProjectFactory::createInstance();
        $localFiles = [];
        foreach ($files as $file) {
            // skip var cache for symfony et al.
            $relativePath = substr($file->getPathName(), strlen($this->inDir) - 1);
            if (preg_match('#/(var|src|tests|app|database)/#', $relativePath)) {
                continue;
            }
            if ($this->skipVendor) {
                if (str_contains($relativePath, '/vendor/')) {
                    continue;
                }
            }
            try {
                //echo $file->getPathName() . "\n";
                $localFiles[] = new \phpDocumentor\Reflection\File\LocalFile($file->getPathName());
            } catch (Exception $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }
        foreach ($extraFiles as $filepath) {
            try {
                //echo $filepath . "\n";
                $localFiles[] = new \phpDocumentor\Reflection\File\LocalFile($filepath);
            } catch (Exception $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }
        $this->project = $factory->create('MyProject', $localFiles);
        return $this->project;
    }

    public function parse_project()
    {
        //var_dump(array_keys($this->project->getNamespaces()));
        $this->totals['namespaces'] = count($this->project->getNamespaces());
        //$root = $this->project->getRootNamespace();
        foreach ($this->project->getFiles() as $file) {
            $this->parse_file($file);
        }
        $this->log($this->to_json($this->totals));
    }

    public function parse_file($file, $totals = true)
    {
        if ($totals) {
            $this->totals['files'] += 1;
            $this->totals['includes'] += count($file->getIncludes());
            $this->totals['functions'] += count($file->getFunctions());
            $this->totals['constants'] += count($file->getConstants());
            $this->totals['classes'] += count($file->getClasses());
            $this->totals['interfaces'] += count($file->getInterfaces());
            $this->totals['traits'] += count($file->getTraits());
        }
        $fpath = $file->getPath();
        foreach ($file->getFunctions() as $function) {
            $this->add_function($function, $fpath);
        }
        foreach ($file->getConstants() as $constant) {
            $this->add_constant($constant, $fpath);
        }
        foreach ($file->getClasses() as $class) {
            $this->add_class($class, $fpath);
            if ($totals) {
                $this->totals['methods'] += count($class->getMethods());
                $this->totals['class_const'] += count($class->getConstants());
            }
        }
    }

    public function add_function($function, $fpath)
    {
        //$name = $function->getName();
        $name = substr((string) $function->getFqsen(), 1);
        $name = str_replace('()', '', $name);
        $lname = strtolower($name);
        if (array_key_exists($lname, $this->functions)) {
            $this->log('Function Conflict: ' . $name, true);
        }
        $args = $this->get_arguments($function);
        $this->functions[$lname] = ['file' => $fpath, 'name' => $name, 'args' => $args];
        $this->functions[$lname]['namespace'] = substr($name, 0, strlen($name) - strlen($function->getName()));
        $this->functions[$lname]['lines'] = $function->getLocation()->getLineNumber() . '-' . $function->getEndLocation()->getLineNumber();
        $this->functions[$lname]['docs'] = $function->getDocBlock();
        $uses = $this->get_docblock_uses($function);
        if (!empty($uses)) {
            $this->functions[$lname]['uses'] = $uses;
        }
    }

    public function get_arguments($func_or_meth)
    {
        $args = [];
        foreach ($func_or_meth->getArguments() as $arg) {
            $args[] = ($arg->getType() != 'mixed' ? $arg->getType() . ' ' : '') . ($arg->isVariadic() ? '...' : '') . ($arg->isByReference() ? '&' : '') . '$' . $arg->getName() . ($arg->getDefault() !== null ? ' = ' . $arg->getDefault() : '');
        }
        return $args;
    }

    public function get_docblock_uses($func_or_meth)
    {
        $docblock = $func_or_meth->getDocBlock();
        if (!empty($docblock) && $docblock->hasTag('uses')) {
            $tags = array_map(function ($tag) {
                return substr($tag, 1);
            }, $docblock->getTagsByName('uses'));
            return implode(', ', $tags);
        }
        return null;
    }

    public function add_constant($constant, $fpath)
    {
        //$name = $constant->getName();
        $name = substr((string) $constant->getFqsen(), 1);
        $lname = strtolower($name);
        if (array_key_exists(strtolower($lname), $this->constants)) {
            $this->log('Constant Conflict: ' . $name, true);
        }
        $this->constants[$lname] = ['file' => $fpath, 'name' => $name, 'value' => $constant->getValue()];
        $this->constants[$lname]['namespace'] = substr($name, 0, strlen($name) - strlen($constant->getName()));
    }

    public function add_class($class, $fpath)
    {
        //$name = $class->getName();
        $name = substr((string) $class->getFqsen(), 1);
        $lname = strtolower($name);
        if (array_key_exists(strtolower($lname), $this->classes)) {
            $this->log('Class Conflict: ' . $name, true);
        }
        $this->classes[$lname] = ['file' => $fpath, 'name' => $name, 'methods' => [], 'const' => []];
        $this->classes[$lname]['namespace'] = substr($name, 0, strlen($name) - strlen($class->getName()));
        $this->classes[$lname]['parent'] = (string) $class->getParent();
        $this->classes[$lname]['lines'] = $class->getLocation()->getLineNumber() . '-' . $class->getEndLocation()->getLineNumber();
        foreach ($class->getMethods() as $method) {
            $mname = $method->getName();
            $args = $this->get_arguments($method);
            $this->classes[$lname]['methods'][strtolower($mname)] = ['name' => $mname, 'args' => $args];
            $this->classes[$lname]['methods'][strtolower($mname)]['lines'] = $method->getLocation()->getLineNumber() . '-' . $method->getEndLocation()->getLineNumber();
            $uses = $this->get_docblock_uses($method);
            if (!empty($uses)) {
                $this->classes[$lname]['methods'][strtolower($mname)]['uses'] = $uses;
            }
        }
        foreach ($class->getConstants() as $constant) {
            $cname = $constant->getName();
            $this->classes[$lname]['const'][strtolower($cname)] = ['name' => $cname, 'value' => $constant->getValue()];
        }
    }

    public function get_next_return($fpath, $lines)
    {
        $lines = $this->get_file_lines($fpath, $lines);
        //echo implode("\n", $lines);
        foreach ($lines as $line) {
            if (str_contains($line, ' return ')) {
                return $line;
            }
        }
        return null;
    }

    public function get_file_lines($fpath, $lines)
    {
        [$start, $stop] = explode('-', $lines);
        $file = $this->project->getFiles()[$fpath];
        return array_slice(explode("\n", $file->getSource()), $start - 1, $stop > 0 ? ($stop - $start + 1) : -1);
    }
}

class xarNode implements JsonSerializable
{
    public static $analyzer;
    //public static $formatter;
    public $name;
    public $children;
    public $parent;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->children = [];
        $this->parent = null;
    }

    public function add($child)
    {
        if (!($child instanceof self)) {
            $child = new self($child);
        }
        $this->children[$child->name] = $child;
        $child->parent = $this;
    }

    public function get($name)
    {
        if (!array_key_exists($name, $this->children)) {
            $this->add($name);
        }
        return $this->children[$name];
    }

    public function jsonSerialize(): mixed
    {
        if ($this->name == 'root') {
            return array_values($this->children);
        }
        //if (!empty(self::$formatter) && is_callable(self::$formatter)) {
        //    return self::$formatter($this);
        //}
        if (!empty(self::$analyzer)) {
            $lname = strtolower($this->name);
            if (array_key_exists($lname, self::$analyzer->classes)) {
                if (empty($this->children)) {
                    return [$this->name => ['methods' => array_keys(self::$analyzer->classes[$lname]['methods'])]];
                }
                return [$this->name => ['methods' => array_keys(self::$analyzer->classes[$lname]['methods']), 'extended' => array_values($this->children)]];
            }
            return [$this->name => array_values($this->children)];
        }
        return [$this->name => array_values($this->children)];
    }
}

class XarayaCoreAnalyzer extends XarayaCodeAnalyzer
{
    public $replaced = [];
    public $missing = [];
    public $classroot = null;

    public function load_core_files()
    {
        $inDir = dirname(__DIR__, 2) . '/html/lib/xaraya';
        $extraFiles = [dirname(__DIR__, 2) . '/html/bootstrap.php'];
        $this->load_project($inDir, $extraFiles);
        $this->parse_project();
    }

    public function find_core_classes()
    {
        if (empty($this->classes)) {
            $this->load_core_files();
        }
        $found = 0;
        foreach (array_keys($this->classes) as $lname) {
            $found += $this->match_core_class($lname);
        }
        $this->save_core_classes();
        $this->log('Found Classes: ' . $found, true);
    }

    public function load_core_classes()
    {
        if ($this->refresh || !file_exists('core_classes.json')) {
            $this->find_core_classes();
        }
        $contents = file_get_contents('core_classes.json');
        $this->classes = json_decode($contents, true);
        $this->log('Load Classes: ' . count($this->classes), true);
    }

    public function save_core_classes()
    {
        ksort($this->classes);
        file_put_contents('core_classes.json', $this->to_json($this->classes));
    }

    public function match_core_class($lname)
    {
        $class = $this->classes[$lname];
        // nothing interesting to do here for now...
        if (preg_match('/^(xar[A-Z]\w+)$/', $class['name'], $matches)) {
            return 1;
        }
        return 0;
    }

    public function find_core_functions()
    {
        if (empty($this->classes)) {
            $this->load_core_classes();
        }
        $found = 0;
        foreach (array_keys($this->functions) as $lname) {
            $found += $this->match_core_function($lname);
        }
        $this->save_core_functions();
        $this->log('Found Functions: ' . $found, true);
    }

    public function load_core_functions()
    {
        if ($this->refresh || !file_exists('core_functions.json')) {
            $this->find_core_functions();
        }
        $contents = file_get_contents('core_functions.json');
        $this->functions = json_decode($contents, true);
        $this->log('Load Functions: ' . count($this->functions), true);
    }

    public function save_core_functions()
    {
        ksort($this->functions);
        file_put_contents('core_functions.json', $this->to_json($this->functions));
    }

    public function match_core_function($lname)
    {
        $function = $this->functions[$lname];
        if (!empty($function['uses']) && preg_match('/^(xar[A-Z]\w+)::(\w+)\(\)$/', $function['uses'], $matches)) {
            $this->functions[$lname]['class'] = $matches[1];
            $this->functions[$lname]['check'] = $matches[2];
            //$function = array_replace($function, $this->functions[$lname]);
            $this->log($function['name'] . ' USES ' . $matches[1] . '::' . $matches[2]);
            $this->functions[$lname]['method'] = $matches[2];
            return 1;
        } elseif (preg_match('/^(xar[A-Z][a-z]+?)([A-Z].+|_(.+))$/', $function['name'], $matches) || preg_match('/^(xar[A-Z]+)([A-Z].+|_(.+))$/', $function['name'], $matches)) {
            $this->functions[$lname]['class'] = $matches[1];
            if (!empty($matches[3])) {
                $this->functions[$lname]['check'] = $matches[3];
            } else {
                $this->functions[$lname]['check'] = $matches[2];
            }
            $function = array_replace($function, $this->functions[$lname]);
            $this->log($function['name'] . ' CHECK');
        }
        if (!isset($function['class'])) {
            $this->log($function['name'] . ' SKIP ');
            return 0;
        }
        $cname = strtolower($function['class']);
        if (isset($this->classes[$cname])) {
            $class = $this->classes[$cname];
            $mname = strtolower($function['check']);
            if (isset($class['methods'][$mname])) {
                $method = $class['methods'][$mname];
                $this->log($function['name'] . ' ' . $class['name'] . '::' . $method['name']);
                $this->functions[$lname]['class'] = $class['name'];
                $this->functions[$lname]['method'] = $method['name'];
                $this->functions[$lname]['margs'] = $method['args'];
                return 1;
            }
        }
        $this->log($function['name'] . ' TODO: ' . $function['lines'] . ' ' . $function['file']);
        $line = $this->get_next_return($function['file'], $function['lines']);
        if (!empty($line) && preg_match('/ return (\w+)::(\w+)\(([^\)]*)/', $line, $matches) && $matches[1] != 'self') {
            $this->log($function['name'] . ' FOUND ' . $matches[1] . '::' . $matches[2] . ' ' . $function['file']);
            $this->functions[$lname]['class'] = $matches[1];
            $this->functions[$lname]['method'] = $matches[2];
            $this->functions[$lname]['rargs'] = $matches[3];
            //$this->functions[$lname]['uses'] = $matches[1] . '::' . $matches[2] . '()';
            return 1;
        }
        //$this->functions[$lname]['return'] = trim($line);
        $this->log($function['name'] . ' LOST ');
        return 0;
    }

    public function find_core_constants()
    {
        if (empty($this->classes)) {
            $this->load_core_classes();
        }
        $found = 0;
        foreach (array_keys($this->constants) as $lname) {
            $found += $this->match_core_constant($lname);
        }
        $this->save_core_constants();
        $this->log('Found Constants: ' . $found, true);
    }

    public function load_core_constants()
    {
        if ($this->refresh || !file_exists('core_constants.json')) {
            $this->find_core_constants();
        }
        $contents = file_get_contents('core_constants.json');
        $this->constants = json_decode($contents, true);
        $this->log('Load Constants: ' . count($this->constants), true);
    }

    public function save_core_constants()
    {
        ksort($this->constants);
        file_put_contents('core_constants.json', $this->to_json($this->constants));
    }

    public function match_core_constant($lname)
    {
        $constant = $this->constants[$lname];
        if (preg_match('/^([A-Za-z]+)_(.+)$/', $constant['name'], $matches)) {
            $this->constants[$lname]['class'] = $matches[1];
            $this->constants[$lname]['check'] = $matches[2];
            $constant = array_replace($constant, $this->constants[$lname]);
        }
        if (!isset($constant['class'])) {
            $this->log($constant['name'] . ' SKIP ');
            return 0;
        }
        $cname = strtolower($constant['class']);
        if (isset($this->classes[$cname])) {
            $class = $this->classes[$cname];
            $cname = strtolower($constant['check']);
            if (isset($class['const'][$cname])) {
                $const = $class['const'][$cname];
                if ($const['value'] !== $constant['value']) {
                    echo $constant['name'] . ' (' . $constant['value'] . ') != ' . $class['name'] . '::' . $const['name'] . ' (' . $const['value'] . ")\n";
                    exit;
                }
                $this->log($constant['name'] . ' ' . $class['name'] . '::' . $const['name']);
                $this->constants[$lname]['class'] = $class['name'];
                $this->constants[$lname]['const'] = $const['name'];
                return 1;
            }
        }
        return 0;
    }

    public function find_core_replaced()
    {
        $this->replaced = [];
        $this->missing = [];
        if (empty($this->functions)) {
            $this->load_core_functions();
        }
        foreach ($this->functions as $lname => $function) {
            if (empty($function['class'])) {
                continue;
            }
            if (empty($function['method'])) {
                if (!array_key_exists($function['file'], $this->missing)) {
                    $this->missing[$function['file']] = [];
                }
                $this->missing[$function['file']][$function['name']] = $function['class'] . ':: ? = ' . $function['check'];
                continue;
            }
            /**
            // @checkme security.php is still messed up
            if (strpos($function['file'], 'security.php') !== false && strpos($lname, 'authkey') === false) {
                if (!array_key_exists($function['file'], $this->missing)) {
                    $this->missing[$function['file']] = array();
                }
                $this->missing[$function['file']][$function['name']] = $function['class'] . '::' . $function['method'] . ' = ' . $function['check'] . ' ?';
                continue;
            }
             */
            $this->replaced[$function['name']] = $function['class'] . '::' . $function['method'];
        }
        if (empty($this->constants)) {
            $this->load_core_constants();
        }
        foreach ($this->constants as $lname => $constant) {
            if (empty($constant['class'])) {
                continue;
            }
            if (empty($constant['const'])) {
                if (!array_key_exists($constant['file'], $this->missing)) {
                    $this->missing[$constant['file']] = [];
                }
                $this->missing[$constant['file']][$constant['name']] = $constant['class'] . ':: ? = ' . $constant['check'];
                continue;
            }
            /**
            // @checkme security.php is still messed up
            if (strpos($constant['file'], 'security.php') !== false) {
                if (!array_key_exists($constant['file'], $this->missing)) {
                    $this->missing[$constant['file']] = array();
                }
                $this->missing[$constant['file']][$constant['name']] = $constant['class'] . '::' . $constant['const'] . '= ' . $constant['check'] . ' ?';
                continue;
            }
             */
            $this->replaced[$constant['name']] = $constant['class'] . '::' . $constant['const'];
        }
        $this->save_core_replaced();
        $this->log('Found Replaced: ' . count($this->replaced), true);
        $this->log('Found Missing: ' . count($this->missing), true);
        $this->log($this->to_json($this->missing));
    }

    public function load_core_replaced()
    {
        if ($this->refresh || !file_exists('core_replace.json')) {
            $this->find_core_replaced();
        }
        $contents = file_get_contents('core_replace.json');
        $this->replaced = json_decode($contents, true);
        $this->log('Load Replaced: ' . count($this->replaced), true);
    }

    public function save_core_replaced()
    {
        ksort($this->replaced);
        file_put_contents('core_replace.json', $this->to_json($this->replaced));
    }

    public function parse_core_files($inDir = null, $extraFiles = [])
    {
        $this->load_project($inDir, $extraFiles);
        $this->parse_project();
        $this->find_core_classes();
        $this->find_core_functions();
        $this->find_core_constants();
        //$this->find_core_replaced();
    }

    public function get_class_tree()
    {
        if (empty($this->classes)) {
            $this->load_core_classes();
        }
        $this->classroot = new xarNode('root');
        foreach (array_keys($this->classes) as $lname) {
            if (!array_key_exists('node', $this->classes[$lname])) {
                $this->classes[$lname]['node'] = new xarNode($this->classes[$lname]['name']);
            }
            $class = $this->classes[$lname];
            // base class
            if (strlen($class['parent']) < 2) {
                $this->log('Root: ' . $class['name'] . ' - ' . $class['parent'] . ' ' . $class['file']);
                $parent = 'None';
                $node = $this->classroot->get($parent);
                $node->add($class['node']);
                continue;
            }
            // actual namespace class
            if (str_contains(substr($class['parent'], 1), '\\')) {
                $this->log('Other: ' . $class['name'] . ' - ' . $class['parent'] . ' ' . $class['file'], true);
                $node = $this->classroot->get($class['parent']);
                $node->add($class['node']);
                continue;
            }
            $parent = substr($class['parent'], 1);
            $lparent = strtolower($parent);
            if (!array_key_exists($lparent, $this->classes)) {
                // class is predefined in PHP - don't autoload here
                if (class_exists($parent, false)) {
                    $this->log('Defined: ' . $class['name'] . ' - ' . $class['parent'] . ' ' . $class['file']);
                    $node = $this->classroot->get($parent);
                    $node->add($class['node']);
                    continue;
                }
                // class is defined elsewhere, e.g. in code/modules
                $this->log('Orphan: ' . $class['name'] . ' - ' . $class['parent'] . ' ' . $class['file']);
                $node = $this->classroot->get($class['parent']);
                $node->add($class['node']);
                continue;
            }
            $this->log('Found: ' . $class['name'] . ' - ' . $class['parent'] . ' ' . $class['file']);
            if (!array_key_exists('node', $this->classes[$lparent])) {
                $this->classes[$lparent]['node'] = new xarNode($this->classes[$lparent]['name']);
            }
            $this->classes[$lparent]['node']->add($class['node']);
        }
        $this->log($this->to_json($this->classroot));
        return $this->classroot;
    }

    public function show_class_tree($name = null)
    {
        if (empty($this->classroot)) {
            $this->get_class_tree();
        }
        if (empty($name)) {
            $this->log($this->to_json($this->classroot), true);
            return;
        }
        $lname = strtolower($name);
        if (!array_key_exists($lname, $this->classes)) {
            $this->log('Invalid class name ' . $name, true);
            return;
        }
        // show methods overridden in extended classes
        xarNode::$analyzer = $this;
        $this->log($this->to_json($this->classes[$lname]['node']), true);
    }
}

class XarayaModuleAnalyzer extends XarayaCoreAnalyzer
{
    public const ALL_EXT = '/\.(php|inc|xt|xml|xsl)$/';

    public function check_module_files($inDir, $fixMe = false)
    {
        $this->load_core_replaced();
        $search = [];
        $replace = [];
        foreach ($this->replaced as $old => $new) {
            // @checkme leave xarML() alone for now...
            if ($old === 'xarML') {
                continue;
            }
            $search[] = $old;
            $replace[] = $new;
        }
        $pattern = '/' . implode('|', $search) . '/i';
        // iterate over all .php, .inc, .xt, .xml and .xsl files in the directory
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($inDir));
        $files = new \RegexIterator($files, self::ALL_EXT);

        $todo = [];
        $found = 0;
        foreach ($files as $file) {
            // skip var cache for symfony et al.
            $relativePath = substr($file->getPathName(), strlen($this->inDir) - 1);
            if (preg_match('#/(lib|var|src|tests|app|database)/#', $relativePath)) {
                continue;
            }
            if ($this->skipVendor) {
                if (str_contains($relativePath, '/vendor/')) {
                    continue;
                }
            }
            try {
                $found += 1;
                $contents = file_get_contents($file->getPathName());
                if (!preg_match_all($pattern, $contents, $matches)) {
                    //$this->log($file->getPathName() . ' - ' . count($matches[0]) . ' DONE');
                    continue;
                }
                if (str_contains($file->getPathName(), '/legacy/')) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (str_ends_with($file->getPathName(), '/xaraya/locales.php')) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (str_ends_with($file->getPathName(), '/xaraya/tableddl.php')) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (str_ends_with($file->getPathName(), '/xaraya/variables.php')) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (str_contains($file->getPathName(), 'xarayatesting/tests/core/')) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (str_contains($file->getPathName(), '/vendor/composer/')) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' matches: ' . implode(', ', array_unique($matches[0])));
                $todo[] = $file->getPathName();
            } catch (Exception $e) {
                echo 'Parse Error: ', $e->getMessage();
                exit;
            }
        }
        $this->log('Found ' . count($todo) . ' out of ' . $found . ' files to fix', true);
        if (!$fixMe) {
            $this->log('Set $fixMe = true; to fix', true);
            return;
        }
        if (str_contains($inDir, '/lib/xaraya/')) {
            $this->log('Sorry, this cannot be used to clean lib/xaraya', true);
            return;
        }
        foreach ($todo as $filepath) {
            $this->log('Fixing ' . $filepath);
            $contents = file_get_contents($filepath);
            $contents = str_ireplace($search, $replace, $contents);
            file_put_contents($filepath, $contents);
        }
    }

    public function find_module_functions($type = '')
    {
        $found = [];
        foreach (array_keys($this->functions) as $lname) {
            $match = $this->match_module_function($lname, $type);
            if (empty($match)) {
                continue;
            }
            $found[] = $match;
        }
        $this->log('Found Functions: ' . count($found) . ' for type "' . $type . '"', true);
        return $found;
    }

    public function match_module_function($lname, $type)
    {
        $function = $this->functions[$lname];
        if (!empty($type) && !str_contains($function['file'], '/xar' . $type . '/') && !str_ends_with($function['file'], '/xar' . $type . '.php')) {
            // skip this for now until it needs to be migrated
            return null;
        }
        if (!preg_match('/^([a-z]+)_([a-z]+)_(\w+)$/', $function['name'], $matches)) {
            $this->log($function['name'] . ' SKIP format ' . $function['file']);
            // skip this for now until it needs to be migrated
            return null;
        }
        if (!str_contains($function['file'], '/' . $matches[1] . '/xar' . $matches[2])) {
            $this->log($function['name'] . ' SKIP module ' . $function['file']);
            // skip this for now until it needs to be migrated
            return null;
        }
        $this->functions[$lname]['module'] = $matches[1];
        $this->functions[$lname]['type'] = $matches[2];
        $this->functions[$lname]['func'] = $matches[3];
        $function = array_replace($function, $this->functions[$lname]);
        if (!empty($function['uses']) && preg_match('/^(xar[A-Z]\w+)::(\w+)\(\)$/', $function['uses'], $matches)) {
            $this->functions[$lname]['class'] = $matches[1];
            $this->functions[$lname]['method'] = $matches[2];
            $this->log($function['name'] . ' USES ' . $matches[1] . '::' . $matches[2] . '()');
            return $lname;
        }
        /**
        $this->log($function['name'] . ' CHECK');
        $cname = strtolower($function['class']);
        if (isset($this->classes[$cname])) {
            $class = $this->classes[$cname];
            $mname = strtolower($function['check']);
            if (isset($class['methods'][$mname])) {
                $method = $class['methods'][$mname];
                $this->log($function['name'] . ' ' . $class['name'] . '::' . $method['name']);
                $this->functions[$lname]['class'] = $class['name'];
                $this->functions[$lname]['method'] = $method['name'];
                $this->functions[$lname]['margs'] = $method['args'];
                return $lname;
            }
        }
         */
        $this->log($function['name'] . ' TODO: ' . $function['lines'] . ' ' . $function['file']);
        $line = $this->get_next_return($function['file'], $function['lines']);
        if (!empty($line) && preg_match('/ return (\w+)::(\w+)\(([^\)]*)/', $line, $matches)) {
            $this->log($function['name'] . ' FOUND ' . $matches[1] . '::' . $matches[2] . ' ' . $function['file']);
            $this->functions[$lname]['class'] = $matches[1];
            $this->functions[$lname]['method'] = $matches[2];
            $this->functions[$lname]['rargs'] = $matches[3];
            //$this->functions[$lname]['uses'] = $matches[1] . '::' . $matches[2] . '()';
            return $lname;
        }
        $this->log(trim($line));
        //$this->functions[$lname]['return'] = trim($line);
        $this->log($function['name'] . ' LOST ');
        return $lname;
    }

    public function find_module_classes()
    {
        $found = [];
        $total = 0;
        foreach (array_keys($this->classes) as $lname) {
            $match = $this->match_module_class($lname);
            if (empty($match)) {
                continue;
            }
            $found[] = $match;
            $total += count($this->classes[$match]['methods']);
        }
        $this->log('Found Class Methods: ' . $total, true);
        return $found;
    }

    public function match_module_class($lname)
    {
        $class = $this->classes[$lname];
        $count = 0;
        foreach (array_keys($class['methods']) as $mname) {
            $method = $class['methods'][$mname];
            if (!empty($method['uses']) && preg_match('/^(\w+)_(\w+)_(\w+)\(\)$/', $method['uses'], $matches)) {
                $this->classes[$lname]['methods'][$mname]['module'] = $matches[1];
                $this->classes[$lname]['methods'][$mname]['type'] = $matches[2];
                $this->classes[$lname]['methods'][$mname]['func'] = $matches[3];
                $this->log($class['name'] . '::' . $method['name'] . ' USES ' . $matches[1] . '_' . $matches[2] . '_' . $matches[3] . '()');
                $count += 1;
                continue;
            }
            $this->log($class['name'] . '::' . $method['name'] . ' TODO: ' . $method['lines'] . ' ' . $class['file']);
            $line = $this->get_next_return($class['file'], $method['lines']);
            if (!empty($line) && preg_match('/ return (\w+)::(\w+)\(([^\)]*)/', $line, $matches)) {
                $this->log($class['name'] . '::' . $method['name'] . ' FOUND ' . $matches[1] . '::' . $matches[2] . '(' . $matches[3] . ') ' . $class['file']);
                $this->classes[$lname]['methods'][$mname]['class'] = $matches[1];
                $this->classes[$lname]['methods'][$mname]['method'] = $matches[2];
                $this->classes[$lname]['methods'][$mname]['rargs'] = $matches[3];
                //$this->functions[$lname]['uses'] = $matches[1] . '::' . $matches[2] . '()';
                $count += 1;
                continue;
            }
            $this->log(trim($line));
            //$this->functions[$lname]['return'] = trim($line);
            $this->log($class['name'] . '::' . $method['name'] . ' LOST ');
        }
        return $lname;
    }

    public function find_installer_functions()
    {
        $found = [];
        foreach (array_keys($this->functions) as $lname) {
            $match = $this->match_installer_function($lname);
            if (empty($match)) {
                continue;
            }
            $found[] = $match;
        }
        $this->log('Found Functions: ' . count($found), true);
        return $found;
    }

    public function match_installer_function($lname)
    {
        $function = $this->functions[$lname];
        if (!str_ends_with($function['file'], 'xarinit.php')) {
            return null;
        }
        if (!preg_match('/(^|\\\\)([a-z]+)_([a-z]+)$/i', $function['name'], $matches)) {
            $this->log($function['name'] . ' SKIP ' . $function['file']);
            // return this because it also needs to be migrated
            return $lname;
        }
        $this->log($function['name'] . ' FOUND ' . $matches[2] . '_' . $matches[3] . ' ' . $function['file']);
        $this->functions[$lname]['module'] = $matches[2];
        $this->functions[$lname]['type'] = 'installer';
        $this->functions[$lname]['func'] = $matches[3];
        return $lname;
    }

    public function find_installer_classes()
    {
        $found = [];
        $total = 0;
        foreach (array_keys($this->classes) as $lname) {
            $match = $this->match_installer_class($lname);
            if (empty($match)) {
                continue;
            }
            $found[] = $match;
            $total += count($this->classes[$match]['methods']);
        }
        $this->log('Found Class Methods: ' . $total, true);
        return $found;
    }

    public function match_installer_class($lname)
    {
        $class = $this->classes[$lname];
        if (!str_ends_with($class['file'], 'installer.php')) {
            return null;
        }
        $count = 0;
        foreach (array_keys($class['methods']) as $mname) {
            $method = $class['methods'][$mname];
            $this->log($class['name'] . '::' . $method['name'] . ' FOUND ' . $class['file']);
            $count += 1;
        }
        return $lname;
    }

    public function find_class_filter($module = '', $type = '', $path = '', $suffix = '')
    {
        $found = [];
        foreach (array_keys($this->classes) as $lname) {
            $class = $this->classes[$lname];
            if (!empty($suffix) && !str_ends_with($class['name'], $suffix)) {
                continue;
            }
            if (!empty($module) && !str_contains($class['file'], '/' . $module . '/')) {
                continue;
            }
            if (!empty($type) && !str_contains($class['file'], '/' . $type)) {
                continue;
            }
            if (!empty($path) && !str_contains($class['file'], $path)) {
                continue;
            }
            $found[] = $lname;
        }
        return $found;
    }
}

class XarayaModuleMigrator extends XarayaModuleAnalyzer
{
    protected $todo = [];

    public function migrate_installer_functions($refresh = false)
    {
        $found = $this->find_installer_functions();
        $files = [];
        foreach ($found as $lname) {
            $fpath = $this->functions[$lname]['file'];
            $files[$fpath] ??= [];
            $files[$fpath][] = $lname;
        }
        $this->log('Files to migrate: ' . $this->to_json($files), true);
        foreach ($files as $fpath => $functions) {
            $installer = str_replace('/xarinit.php', '/installer.php', $fpath);
            $module = basename(dirname($fpath));
            $modulefile = str_replace('/installer.php', '/module.php', $installer);
            $this->check_module_class($modulefile);
            if (file_exists($installer) && !$refresh) {
                //$gitfile = '/home/mikespub/modules/' . $module . '/installer.php';
                //copy($installer, $gitfile);
                $this->log('Installer file for module ' . $module . ' exists - SKIP ' . $installer);
                continue;
            }
            $output = file_get_contents(__DIR__ . '/installer.txt');
            $output = str_replace('skeleton', $module, $output);
            $xarversion = str_replace('/xarinit.php', '/xarversion.php', $fpath);
            $namespace = $this->get_module_namespace($xarversion);
            $output = str_replace('Xaraya\Modules\Skeleton', $namespace, $output);
            $uses = $this->get_namespace_uses($fpath);
            if (!empty($uses)) {
                $output = str_replace('use sys;', $uses . 'use sys;', $output);
            }
            $output .= '    /** xarinit.php functions imported by bermuda_cleanup */';
            foreach ($functions as $lname) {
                $function = $this->functions[$lname];
                $search = [
                    '/function ' . $module . '_/',
                    '/\b' . $module . '_(\w+)\(/',
                ];
                $replace = [
                    'public function ',
                    '\$this->$1(',
                ];
                $output .= "\n";
                $output .= $this->output_function($function, $search, $replace);
            }
            $output .= "\n}\n\n";
            if (!is_dir(dirname($installer))) {
                mkdir(dirname($installer));
            }
            $this->log('Installer file for module ' . $module . ' exists - CREATE ' . $installer, true);
            file_put_contents($installer, $output);
        }
    }

    public function check_module_class($modulefile)
    {
        $module = basename(dirname($modulefile, 2));
        if (file_exists($modulefile)) {
            if (empty($this->todo[$modulefile])) {
                //$gitfile = '/home/mikespub/modules/' . $module . '/module.php';
                //copy($modulefile, $gitfile);
                $this->todo[$modulefile] = true;
            }
            $this->log('Module file for module ' . $module . ' exists - SKIP ' . $modulefile);
            return;
        }
        $output = file_get_contents(__DIR__ . '/module.txt');
        $output = str_replace('skeleton', $module, $output);
        $xarversion = str_replace('/module.php', '/xarversion.php', $modulefile);
        $namespace = $this->get_module_namespace($xarversion);
        $output = str_replace('Xaraya\Modules\Skeleton', $namespace, $output);
        $this->log('Module file for module ' . $module . ' exists - CREATE ' . $modulefile, true);
        file_put_contents($modulefile, $output);
    }

    public function migrate_module_functions($modType = 'userapi', $refresh = false)
    {
        $found = $this->find_module_functions($modType);
        if (count($found) < 1) {
            return;
        }
        if (in_array($modType, ['user', 'admin', 'hooks'])) {
            $classname = ucfirst($modType) . 'Gui';
            $classtype = $modType . 'gui';
        } else {
            $classname = str_replace('api', 'Api', ucfirst($modType));
            $classtype = $modType;
        }
        $files = [];
        foreach ($found as $lname) {
            $fpath = $this->functions[$lname]['file'];
            $files[$fpath] ??= [];
            $files[$fpath][] = $lname;
        }
        $this->log('Files to migrate: ' . $this->to_json($files), true);
        foreach ($files as $fpath => $functions) {
            if (str_contains($fpath, '/xar' . $modType . '/')) {
                $typefile = dirname($fpath, 2) . '/' . $classtype . '.php';
                $module = basename(dirname($fpath, 2));
                $split = false;
            } elseif (str_ends_with($fpath, '/xar' . $modType . '.php')) {
                $typefile = dirname($fpath) . '/' . $classtype . '.php';
                $module = basename(dirname($fpath));
                $split = true;
            } else {
                $this->log('Invalid file for module ' . $module . ' - SKIP ' . $fpath, true);
                continue;
            }
            if ($split) {
                // @todo each function will have its own method file
                $this->log('Combined file for module ' . $module . ' - TODO ' . $fpath, true);
                continue;
            }
            $this->check_type_class($typefile);
            // primary function will be replaced by __invoke and helper functions renamed
            $funcName = str_replace('.php', '', basename($fpath));
            if (str_starts_with($funcName, '_')) {
                $this->log('Invalid file for module ' . $module . ' - SKIP ' . $fpath, true);
                continue;
            }
            $methodfile = str_replace('.php', '/', $typefile) . $funcName . '.php';
            if (file_exists($methodfile) && !$refresh) {
                //$gitfile = '/home/mikespub/modules/' . $module . '/' . $classtype . '/' . $funcName . '.php';
                //copy($installer, $gitfile);
                $this->log('Method file for module ' . $module . ' exists - SKIP ' . $methodfile);
                continue;
            }
            $output = file_get_contents(__DIR__ . '/method.txt');
            $output = str_replace('skeleton', $module, $output);
            $xarversion = str_replace('/' . $classtype . '.php', '/xarversion.php', $typefile);
            $namespace = $this->get_module_namespace($xarversion);
            $output = str_replace('Xaraya\Modules\Skeleton\UserApi', $namespace . '\\' . $classname, $output);
            $output = str_replace('<UserApi>', '<' . $classname . '>', $output);
            $output = str_replace(' userapi get ', " $modType $funcName ", $output);
            // see MethodTrait::getClassName()
            $methodName = str_replace('_', '', ucwords($funcName, '_'));
            $output = str_replace('GetMethod', ' ' . $methodName . 'Method', $output);
            $uses = $this->get_namespace_uses($fpath);
            if (!empty($uses)) {
                $output = str_replace('use sys;', $uses . 'use sys;', $output);
            }
            $output .= '    /** functions imported by bermuda_cleanup */';
            foreach ($functions as $lname) {
                $function = $this->functions[$lname];
                // @todo add replacement of core services
                $search = [
                    '/function \&/',
                    '/function ' . $module . '_' . $modType . '_' . $funcName . '\(/i',
                    '/function ' . $module . '_' . $modType . '_(\w+)\(/i',
                    '/\b' . $module . '_' . $modType . '_' . $funcName . '\(/i',
                    '/\b' . $module . '_' . $modType . '_(\w+)\(/i',
                    '/, \$context = null/',
                    '/\$context/',
                ];
                $replace = [
                    'function ',
                    'public function __invoke(',
                    'public function $1(',
                    // @todo for files with recursive function calls, use __invoke instead?
                    '\$this->__invoke(',
                    // @todo for files with only 1 function, use $this->parent->$1 instead?
                    (count($functions) > 1) ? '\$this->$1(' : '\$this->parent->$1(',
                    '',
                    '\$this->getContext()',
                ];
                $output .= "\n";
                $output .= $this->output_function($function, $search, $replace);
            }
            $output .= "\n}\n\n";
            if (!is_dir(dirname($methodfile))) {
                mkdir(dirname($methodfile));
            }
            $this->log('Method file for module ' . $module . ' exists - CREATE ' . $methodfile, true);
            file_put_contents($methodfile, $output);
        }
    }

    public function check_type_class($typefile)
    {
        $module = basename(dirname($typefile, 2));
        $typename = basename($typefile);
        if (file_exists($typefile)) {
            if (empty($this->todo[$typefile])) {
                //$gitfile = '/home/mikespub/modules/' . $module . '/' . $typename;
                //if (!file_exists($gitfile)) {
                //    copy($typefile, $gitfile);
                //}
                $this->todo[$typefile] = true;
            }
            $this->log('Class file for module ' . $module . ' exists - SKIP ' . $typefile);
            return;
        }
        $filename = __DIR__ . '/' . str_replace('.php', '.txt', $typename);
        if (!file_exists($filename)) {
            if (str_ends_with($typename, 'api.php')) {
                $apiname = substr($typename, 0, -7);
                $classname = ucfirst($apiname) . 'Api';
                $input = file_get_contents(__DIR__ . '/dataapi.txt');
                $input = str_replace('DataApi', $classname, $input);
                $input = str_replace(' data ', " $apiname ", $input);
                file_put_contents($filename, $input);
            } else {
                $guiname = substr($typename, 0, -4);
                $classname = ucfirst($guiname) . 'Gui';
                $input = file_get_contents(__DIR__ . '/usergui.txt');
                $input = str_replace(' UserGui ', " $classname ", $input);
                $input = str_replace(' user ', " $guiname ", $input);
                file_put_contents($filename, $input);
            }
        }
        $output = file_get_contents($filename);
        $output = str_replace('skeleton', $module, $output);
        $xarversion = str_replace('/' . $typename, '/xarversion.php', $typefile);
        $namespace = $this->get_module_namespace($xarversion);
        $output = str_replace('Xaraya\Modules\Skeleton', $namespace, $output);
        $this->log('Class file for module ' . $module . ' exists - CREATE ' . $typefile, true);
        file_put_contents($typefile, $output);
    }

    public function get_module_namespace($xarversion)
    {
        $module = basename(dirname($xarversion));
        include $xarversion;
        return $modversion['namespace'] ?? 'Xaraya\\Modules\\' . ucfirst($module);
    }

    public function get_namespace_uses($fpath)
    {
        $uses = '';
        $todo = [];
        $contents = file_get_contents($fpath);
        $pattern = '/((xar|Data|Property|Creole|PDO)[A-Z]\w+)::(\$?\w+)/';
        $matches = [];
        if (preg_match_all($pattern, $contents, $matches)) {
            foreach ($matches[1] as $use) {
                $todo[$use] = true;
            }
        }
        $pattern = '/new (\w+)\(/';
        $matches = [];
        if (preg_match_all($pattern, $contents, $matches)) {
            foreach ($matches[1] as $use) {
                $todo[$use] = true;
            }
        }
        $pattern = '/(\w*Exception)/';
        $matches = [];
        if (preg_match_all($pattern, $contents, $matches)) {
            foreach ($matches[1] as $use) {
                $todo[$use] = true;
            }
        }
        ksort($todo);
        foreach (array_keys($todo) as $use) {
            $uses .= "use $use;\n";
        }
        return $uses;
    }

    public function output_function($function, $search = [], $replace = [])
    {
        $output = "\n";
        if (!empty($function['docs'])) {
            $output .= $this->output_docblock($function['docs']);
        }
        $output .= implode("\n", $this->get_file_lines($function['file'], $function['lines']));
        if (!empty($search)) {
            $output = preg_replace($search, $replace, $output);
        }
        return str_replace("\n", "\n    ", $output);
    }

    public function output_docblock($docblock)
    {
        $output = "/**\n";
        $summary = (string) $docblock->getSummary();
        $output .= ' * ' . str_replace("\n", "\n * ", $summary) . "\n";
        $description = (string) $docblock->getDescription();
        if (!empty($description)) {
            $output .= ' * ' . str_replace("\n", "\n * ", $description) . "\n";
        }
        foreach ($docblock->getTags() as $tag) {
            $output .=  ' * @' . $tag->getName() . ' ' . str_replace("\n", "\n * ", $tag) . "\n";
        }
        $output .= " */\n";
        return $output;
    }

    public function find_class_dependencies($module = '', $type = '', $path = '', $suffix = '')
    {
        $found = [];
        $total = 0;
        $classList = $this->find_class_filter($module, $type, $path, $suffix);
        foreach ($classList as $lname) {
            $class = $this->classes[$lname];
            $methods = $this->find_method_dependencies($lname);
            if (empty($methods)) {
                continue;
            }
            $found[$class['file']] ??= [];
            $found[$class['file']][$lname] = $methods;
            $total += count($methods);
        }
        $this->log('Found Class Methods: ' . $total, true);
        return $found;
    }

    public function find_method_dependencies($lname)
    {
        $class = $this->classes[$lname];
        $methods = [];
        $count = 0;
        foreach (array_keys($class['methods']) as $mname) {
            $method = $class['methods'][$mname];
            $calls = $this->get_coreclass_calls($class['file'], $method['lines']);
            if (empty($calls)) {
                continue;
            }
            $methods[$mname] = $calls;
            $count += count($methods[$mname]);
        }
        return $methods;
    }

    public function get_coreclass_calls($fpath, $lines)
    {
        $calls = [];
        $pattern = '/((xar|Data|Property|Creole|PDO)[A-Z]\w+)::(\w+)\(([^\[\)]*)/';
        $contents = implode("\n", $this->get_file_lines($fpath, $lines));
        $matches = [];
        if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
            $this->log($fpath . ' ' . strlen($contents) . " NONE");
            return $calls;
        }
        $this->log($fpath . ' ' . strlen($contents) . " FOUND " . count($matches));
        foreach ($matches as $match) {
            $calls[$match[1]] ??= [];
            $calls[$match[1]][$match[3]] ??= [];
            $calls[$match[1]][$match[3]][] = $match[4];
        }
        return $calls;
    }

    public function find_called_dependencies($module = '', $type = '', $path = '', $suffix = '')
    {
        $found = $this->find_class_dependencies($module, $type, $path, $suffix);
        $called = [];
        $summary = [];
        foreach ($found as $fpath => $classes) {
            foreach ($classes as $cname => $methods) {
                foreach ($methods as $mname => $calls) {
                    foreach ($calls as $class => $call) {
                        $called[$class] ??= [];
                        $summary[$class] ??= [];
                        foreach ($call as $method => $args) {
                            $called[$class][$method] ??= [];
                            $summary[$class][$method] ??= ['total' => 0, 'classes' => []];
                            foreach ($args as $params) {
                                $this->log($class . '::' . $method . ' [' . $params . ']');
                                $called[$class][$method][] = ['class' => $cname, 'method' => $mname, 'params' => $params];
                                $summary[$class][$method]['total'] += 1;
                                $summary[$class][$method]['classes'][$cname] = true;
                            }
                        }
                    }
                }
            }
        }
        foreach ($summary as $class => $methods) {
            foreach ($methods as $method => $calls) {
                $summary[$class][$method]['classes'] = count($summary[$class][$method]['classes']);
            }
        }
        return [$called, $summary];
    }

    public function find_called_modules($module = '', $type = '', $path = '')
    {
        [$called, $summary] = $this->find_called_dependencies($module, $type, $path);
        $called['xarMod'] ??= [];
        $modules = [];
        $internal = 0;
        $inmodule = 0;
        $external = 0;
        $called['xarMod']['apiFunc'] ??= [];
        foreach ($called['xarMod']['apiFunc'] as $call) {
            $params = array_map(function ($param) {
                return trim($param, " \n'");
            }, explode(',', $call['params']));
            if (count($params) < 3) {
                $this->log(implode(' - ', $params) . ' - MISSING', true);
                continue;
            }
            [$modName, $modType, $funcName] = $params;
            $call['params'] = implode(',', array_slice($params, 3)) ?: '[...]';
            if ($modName == 'dynamicdata') {
                $modClass = 'xaraya\\dataobject';
            } else {
                $modClass = 'xaraya\\modules\\' . $modName;
            }
            if (str_contains($call['class'], $modClass . '\\' . $modType . 'api\\')) {
                $call['internal'] = true;
                $internal += 1;
            } elseif (str_contains($call['class'], $modClass . '\\')) {
                $call['inmodule'] = true;
                $inmodule += 1;
            } else {
                $call['external'] = true;
                $external += 1;
            }
            $modules[$modName] ??= [];
            $modules[$modName][$modType . 'api'] ??= [];
            $modules[$modName][$modType . 'api'][$funcName] ??= [];
            $modules[$modName][$modType . 'api'][$funcName][] = $call;
        }
        $called['xarMod']['guiFunc'] ??= [];
        foreach ($called['xarMod']['guiFunc'] as $call) {
            $params = array_map(function ($param) {
                return trim($param, " \n'");
            }, explode(',', $call['params']));
            if (count($params) < 3) {
                $this->log(implode(' - ', $params) . ' - MISSING', true);
                continue;
            }
            [$modName, $modType, $funcName] = $params;
            $call['params'] = implode(',', array_slice($params, 3)) ?: '[...]';
            if ($modName == 'dynamicdata') {
                $modClass = 'xaraya\\dataobject';
            } else {
                $modClass = 'xaraya\\modules\\' . $modName;
            }
            if (str_contains($call['class'], $modClass . '\\' . $modType . 'gui\\')) {
                $call['internal'] = true;
                $internal += 1;
            } elseif (str_contains($call['class'], $modClass . '\\')) {
                $call['inmodule'] = true;
                $inmodule += 1;
            } else {
                $call['external'] = true;
                $external += 1;
                //continue;
            }
            $modules[$modName] ??= [];
            $modules[$modName][$modType] ??= [];
            $modules[$modName][$modType][$funcName] ??= [];
            $modules[$modName][$modType][$funcName][] = $call;
        }
        $this->log('Internal/in-module/external module calls found: ' . $internal . ' / ' . $inmodule . ' / ' . $external, true);
        return $modules;
    }

    public function draw_mermaid_graph($called)
    {
        $lines = [];
        $lines[] = '## Call Dependencies';
        $lines[] = '';
        ksort($called);
        foreach ($called as $class => $methods) {
            $fromto = [];
            $lines[] = '### ' . $class;
            $lines[] = '';
            $lines[] = '```mermaid';
            $lines[] = 'flowchart LR';
            $lines[] = '  subgraph ' . $class;
            ksort($methods);
            foreach ($methods as $method => $callers) {
                $to = $class . '_' . $method;
                $lines[] = '    ' . $to;
                foreach ($callers as $caller) {
                    $from = $caller['class'] . '_' . $caller['method'];
                    $fromto[$from] ??= [];
                    $fromto[$from][$to] ??= 0;
                    $fromto[$from][$to] += 1;
                }
            }
            ksort($fromto);
            $lines[] = '  end';
            foreach ($fromto as $from => $links) {
                ksort($links);
                foreach ($links as $to => $count) {
                    $lines[] = '  ' . $from . ' --|' . $count . '|--> ' . $to;
                }
            }
            $lines[] = '```';
            $lines[] = '';
        }
        $output = implode("\n", $lines);
        return $output;
    }

    public function check_method_casing($module = '', $type = '')
    {
        $modules = $this->find_called_modules($module, $type);
        // @todo check for duplicates when lower-casing - only in calendar so far
        echo $this->to_json($modules);
    }

    public function find_module_methods($module = '', $type = '', $path = '', $suffix = 'Method')
    {
        $found = [];
        $total = 0;
        $classList = $this->find_class_filter($module, $type, $path, $suffix);
        foreach ($classList as $lname) {
            $class = $this->classes[$lname];
            $pieces = explode('\\', $class['name']);
            $methodName = array_pop($pieces);
            $methodName = lcfirst(substr($methodName, 0, strlen($methodName) - strlen('Method')));
            $this->log('Class method ' . $class['name'] . ' - FOUND: ' . $methodName);
            //if ($methodName == 'main') {
            //    continue;
            //}
            $namespace = implode('\\', $pieces);
            $found[$namespace] ??= [];
            if (array_key_exists($methodName, $found[$namespace])) {
                $this->log('Duplicate Class method ' . $class['name'] . ' - FATAL: ' . $methodName, true);
                exit;
            }
            $found[$namespace][$methodName] = $lname;
            $total += 1;
        }
        $this->log('Found Class Methods: ' . $total . ' in ' . count($found) . ' classes', true);
        ksort($found);
        foreach (array_keys($found) as $namespace) {
            ksort($found[$namespace]);
        }
        return $found;
    }

    public function get_invoke_method($fpath)
    {
        $file = $this->project->getFiles()[$fpath];
        foreach ($file->getClasses() as $class) {
            foreach ($class->getMethods() as $method) {
                if ($method->getName() == '__invoke') {
                    return $method;
                }
            }
        }
        return null;
    }

    public function get_docblock_vars($func_or_meth)
    {
        $docblock = $func_or_meth->getDocBlock();
        if (!empty($docblock) && $docblock->hasTag('var')) {
            //$tags = array_map(function ($tag) {
            //    return str_replace(['$args', '[', ']', "'"], [], $tag);
            //}, $docblock->getTagsByName('var'));
            //return implode(', ', $tags);
            return $docblock->getTagsByName('var');
        }
        return null;
    }

    public function document_module_methods($module = '', $type = '', $replace = false)
    {
        $found = [];
        $total = 0;
        $todo = $this->find_module_methods($module, $type);
        foreach ($todo as $namespace => $methods) {
            $pieces = explode('\\', $namespace);
            $className = array_pop($pieces);
            foreach ($methods as $methodName => $lname) {
                $class = $this->classes[$lname];
                $this->log('Class Method in ' . $namespace . ': ' . $methodName . ' - FOUND ' . $class['file']);
                //if ($methodName == 'main') {
                //    continue;
                //}
                $classFile = dirname($class['file']) . '.php';
                $found[$classFile] ??= [];
                $summary = '';
                $vars = [];
                $method = $this->get_invoke_method($class['file']);
                if (!empty($method)) {
                    $summary = $method->getDocBlock()?->getSummary() ?? '';
                    $vars = $this->get_docblock_vars($method);
                }
                if (str_starts_with($summary, 'Summary of ')) {
                    $text = $methodName . '(array $args)';
                } else {
                    $text = $methodName . '(array $args) ' . str_replace("\n", ' - ', $summary);
                }
                $shape = '';
                $count = 0;
                if (!empty($vars)) {
                    $args = [];
                    foreach ($vars as $var) {
                        $vname = (string) $var->getVariableName();
                        $vtype = (string) $var->getType();
                        $vdesc = (string) $var->getDescription();
                        if (str_contains($vname, 'args[')) {
                            $vname = str_replace(['args[', ']', "'"], [], $vname);
                        }
                        if (str_contains(strtolower($vdesc), 'optional')) {
                            $args[] = $vname . '?: ' . $vtype;
                        } else {
                            $args[] = $vname . ': ' . $vtype;
                            $count += 1;
                        }
                    }
                    $shape = "\n *  array{" . implode(', ', $args) . "}";
                }
                if ($count > 0) {
                    $text = $methodName . '(array $args)';
                } else {
                    $text = $methodName . '(array $args = [])';
                }
                if (!str_starts_with($summary, 'Summary of ') && !str_starts_with($summary, 'functions imported by')) {
                    $text .= ' ' . str_replace("\n", ' - ', $summary);
                }
                $found[$classFile][] = $text . $shape;
                $total += 1;
            }
        }
        $this->log('Found Class Methods: ' . $total, true);
        ksort($found);
        $prefix = "\n * @method mixed ";
        //$postfix = "(array \$args)";
        $postfix = "";
        foreach (array_keys($found) as $classFile) {
            sort($found[$classFile]);
            $contents = file_get_contents($classFile);
            //$this->log('Class methods for ' . $classFile . ': ' . implode(', ', $found[$classFile]));
            $extra = implode($postfix . $prefix, $found[$classFile]);
            $contents = str_replace(" * @extends", " *" . $prefix . $extra . $postfix . "\n * @extends", $contents);
            if (!str_contains($contents, '@extends')) {
                $this->log('Missing @extends in class file ' . $classFile, true);
                $this->log(" *" . $prefix . $extra . $postfix);
                continue;
            }
            if ($replace) {
                file_put_contents($classFile, $contents);
            } else {
                $this->log('Classfile ' . $classFile);
                $this->log(" *" . $prefix . $extra . $postfix);
                $this->log('');
            }
        }
        return $found;
    }

    public function load_core_services($module)
    {
        // @todo add replacement of core services
        $mapping = [
            '/xarML\(/' => '\$this->ml(',
            '/xarMLS::translate\(/' => '\$this->ml(',
            '/xarMLS::loadTranslations\(/' => '\$this->mls()->loadTranslations(',
            '/xarMLS::getCurrentLocale\(/' => '\$this->mls()->getCurrentLocale(',
            '/xarMLS::getCharsetFromLocale\(/' => '\$this->mls()->getCharsetFromLocale(',
            '/xarLocale::loadData\(/' => '\$this->mls()->loadLocale(',
            '/xarLocale::formatDate\(/' => '\$this->mls()->formatDate(',
            '/xarLocale::getFormattedDate\(/' => '\$this->mls()->getFormattedDate(',
            '/xarLocale::getFormattedTime\(/' => '\$this->mls()->getFormattedTime(',
            // @todo differentiate based on xarLog::* level
            '/xarLog::message\(/' => '\$this->log()->message(',
            '/xarLog::variable\(/' => '\$this->log()->variable(',
            // @todo differentiate based on xarVar::* flags
            '/xarVar::fetch\(/' => '\$this->var()->fetch(',
            '/xarVar::prepForDisplay\(/' => '\$this->var()->prep(',
            '/xarVar::prepHTMLDisplay\(/' => '\$this->var()->prepHTML(',
            '/xarVar::prepForOS\(/' => '\$this->var()->prepPath(',
            '/xarVar::isCached\(/' => '\$this->var()->isCached(',
            '/xarVar::getCached\(/' => '\$this->var()->getCached(',
            '/xarVar::setCached\(/' => '\$this->var()->setCached(',
            '/xarVar::delCached\(/' => '\$this->var()->delCached(',
            '/xarVar::loadCached\(/' => '\$this->var()->loadCached(',
            '/xarVar::saveCached\(/' => '\$this->var()->saveCached(',
            '/xarCoreCache::isCached\(/' => '\$this->var()->isCached(',
            '/xarCoreCache::getCached\(/' => '\$this->var()->getCached(',
            '/xarCoreCache::setCached\(/' => '\$this->var()->setCached(',
            '/xarCoreCache::delCached\(/' => '\$this->var()->delCached(',
            '/xarCoreCache::hasPreload\(/' => '\$this->var()->hasPreload(',
            '/xarCoreCache::loadCached\(/' => '\$this->var()->loadCached(',
            '/xarCoreCache::saveCached\(/' => '\$this->var()->saveCached(',
            // @todo handle xarSecurity::check() with component & instance
            '/xarSec::genAuthKey\(/' => '\$this->sec()->genAuthKey(',
            '/xarSec::confirmAuthKey\(/' => '\$this->sec()->confirmAuthKey(',
            '/xarSecurity::check\(([^,)]+)\)/' => '\$this->sec()->checkAccess($1)',
            '/xarSecurity::check\(([^,)]+),\s*(\d+)\s*\)/' => '\$this->sec()->checkAccess($1, $2)',
            // @todo handle xarController::URL()
            '/xarController::redirect\(/' => '\$this->ctl()->redirect(',
            '/xarController::forbidden\(/' => '\$this->ctl()->forbidden(',
            '/xarController::badRequest\(/' => '\$this->ctl()->badRequest(',
            '/xarController::notFound\(/' => '\$this->ctl()->notFound(',
            '/xarController::URL\(\s*\n*\s*\'' . $module . '\', */' => '\$this->mod()->getURL(',
            '/xarController::URL\(/' => '\$this->ctl()->getModuleURL(',
            // @todo we need to drop extra , null, $this->getContext() here
            '/,\s*null,\s*\$this->getContext\(\)\s*\)/s' => ')',
            '/xarServer::getModuleURL\(/' => '\$this->ctl()->getModuleURL(',
            '/xarServer::getCurrentURL\(/' => '\$this->ctl()->getCurrentURL(',
            // @todo or use $this->data()->getURL()
            '/xarServer::getObjectURL\(/' => '\$this->ctl()->getObjectURL(',
            '/xarServer::getBaseURL\(/' => '\$this->ctl()->getBaseURL(',
            '/xarServer::getBaseURI\(/' => '\$this->ctl()->getBaseURI(',
            '/xarServer::getVar\(/' => '\$this->ctl()->getServerVar(',
            '/xarController::getRequest\(\)/' => '\$this->ctl()->getRequest()',
            '/xarController::getVar\(/' => '\$this->ctl()->getRequestVar(',
            // @todo check xarTpl::module() against current modName modType for mod()->template()
            '/xarTpl::module\(/' => '\$this->tpl()->module(',
            // @todo check xarTpl::block() against current modName blockType for block()->template()
            '/xarTpl::block\(/' => '\$this->tpl()->block(',
            // @todo check xarTpl::object() against current objectName for data()->template()
            '/xarTpl::object\(/' => '\$this->tpl()->object(',
            // @todo check xarTpl::property() against current propertyName for prop()->template()
            '/xarTpl::property\(/' => '\$this->tpl()->property(',
            '/xarTpl::setPageTitle\(/' => '\$this->tpl()->setPageTitle(',
            '/xarTpl::setPageTemplateName\(/' => '\$this->tpl()->setPageTemplateName(',
            '/xarTpl::getImage\(/' => '\$this->tpl()->getImage(',
            '/xarTpl::getFile\(/' => '\$this->tpl()->getFile(',
            '/xarTplPager::getPager\(/' => '\$this->tpl()->getPager(',
            // @todo handle xarMod*::* - note: this assumes you set $module !
            '/xarModVars::get\(\s*\'' . $module . '\',\s*/s' => '\$this->mod()->getVar(',
            '/xarModVars::set\(\s*\'' . $module . '\',\s*/s' => '\$this->mod()->setVar(',
            '/xarModVars::delete\(\s*\'' . $module . '\',\s*/s' => '\$this->mod()->delVar(',
            '/xarMod::apiFunc\((\s*\'' . $module . '\',\s*)/s' => '\$this->mod()->apiMethod($1',
            '/xarMod::guiFunc\((\s*\'' . $module . '\',\s*)/s' => '\$this->mod()->guiMethod($1',
            // @todo those can probably be removed - note: this assumes you set $module !
            '/xarMod::apiLoad\((\s*\'' . $module . '\',\s*)/s' => '\$this->mod()->apiLoad($1',
            '/xarMod::load\((\s*\'' . $module . '\',\s*)/s' => '\$this->mod()->load($1',
            // @todo replace other module calls - note: this assumes you set $module !
            '/xarMod::apiFunc\(/' => '\$this->mod()->apiFunc(',
            '/xarMod::guiFunc\(/' => '\$this->mod()->guiFunc(',
            '/xarMod::apiLoad\(/' => '\$this->mod()->apiLoad(',
            '/xarMod::load\(/' => '\$this->mod()->load(',
            '/xarMod::getName\(/' => '\$this->mod()->getName(',
            '/xarMod::getID\(/' => '\$this->mod()->getID(',
            '/xarMod::getRegID\(/' => '\$this->mod()->getRegID(',
            '/xarMod::getDisplayName\(/' => '\$this->mod()->getDisplayName(',
            '/xarMod::getFileInfo\(/' => '\$this->mod()->getFileInfo(',
            '/xarMod::getBaseInfo\(/' => '\$this->mod()->getBaseInfo(',
            '/xarMod::getInfo\(/' => '\$this->mod()->getInfo(',
            '/xarMod::isAvailable\(/' => '\$this->mod()->isAvailable(',
            '/xarMod::loadDbInfo\(/' => '\$this->mod()->loadDbInfo(',
            '/xarModAlias::resolve\(/' => '\$this->mod()->resolveAlias(',
            '/xarModHooks::isHooked\(/' => '\$this->mod()->isHooked(',
            '/xarHooks::isAttached\(/' => '\$this->mod()->isHooked(',
            '/xarModHooks::call\(/' => '\$this->mod()->callHooks(',
            '/xarHooks::notify\(/' => '\$this->mod()->notifyHooks(',
            // xar*Cache::* only for variable caching here
            '/xarCache::getVariableKey\(/' => '\$this->cache()->getVariableKey(',
            '/xarVariableCache::isCached\(/' => '\$this->cache()->hasVariable(',
            '/xarVariableCache::getCached\(/' => '\$this->cache()->getVariable(',
            '/xarVariableCache::setCached\(/' => '\$this->cache()->setVariable(',
            '/xarVariableCache::delCached\(/' => '\$this->cache()->delVariable(',
            // xarConfigVars $scope = null not used
            '/xarConfigVars::get\([^,]+,\s*/s' => '\$this->config()->getVar(',
            '/xarConfigVars::set\([^,]+,\s*/s' => '\$this->config()->setVar(',
            '/xarConfigVars::delete\([^,]+,\s*/s' => '\$this->config()->delVar(',
            '/xarConfigVars::cache\([^)]*\)/s' => '\$this->config()->cache()',
            // xarSession replace getVar('role_id') first
            '/xarSession::getVar\(\'role_id\'\)/' => '\$this->session()->getUserId()',
            '/xarSession::getVar\(/' => '\$this->session()->getVar(',
            '/xarSession::setVar\(/' => '\$this->session()->setVar(',
            '/xarSession::delVar\(/' => '\$this->session()->delVar(',
            '/xarSession::getUserId\(/' => '\$this->session()->getUserId(',
            '/xarSession::getAnonId\(/' => '\$this->session()->getAnonId(',
            // @todo handle xarDB*::* - note: excl. meta and newConn
            '/xarDB::getConn\(/' => '\$this->db()->getConn(',
            '/xarDB::getName\(/' => '\$this->db()->getName(',
            '/xarDB::getPrefix\(/' => '\$this->db()->getPrefix(',
            '/xarDB::getType\(/' => '\$this->db()->getType(',
            '/xarDB::getTables\(/' => '\$this->db()->getTables(',
            '/xarDB::importTables\(/' => '\$this->db()->importTables(',
            '/xarDB::FETCHMODE_ASSOC/' => '\$this->db()->getFetchAssoc()',
            '/xarDB::FETCHMODE_NUM/' => '\$this->db()->getFetchNum()',
            // @todo do we want/need this in all api/gui functions
            '/DataObjectDescriptor::getObjectID\(/' => '\$this->data()->getObjectID(',
            '/DataObjectFactory::getObject\(/' => '\$this->data()->getObject(',
            '/DataObjectFactory::getObjectList\(/' => '\$this->data()->getObjectList(',
            '/DataObjectFactory::getObjectLoader\(/' => '\$this->data()->getObjectLoader(',
            '/DataObjectFactory::getObjectInfo\(/' => '\$this->data()->getObjectInfo(',
            '/DataObjectFactory::getObjects\(/' => '\$this->data()->getObjects(',
            '/DataPropertyMaster::getPropertyTypes\(/' => '\$this->prop()->getPropertyTypes(',
            '/DataPropertyMaster::getProperties\(/' => '\$this->prop()->getProperties(',
            '/DataPropertyMaster::getProperty\(/' => '\$this->prop()->getProperty(',
            '/ exit;/' => ' \$this->exit();',
            '/ exit\(/' => ' \$this->exit(',
            '/ die\(/' => ' \$this->exit(',
            '/xarCore::exit\(/' => ' \$this->exit(',
        ];
        //file_put_contents('core_services.json', $this->to_json($mapping));
        return $mapping;
    }

    public function replace_core_services($module, $type = '', $path = '', $suffix = '', $update = false)
    {
        $mapping = $this->load_core_services($module);
        $search = array_keys($mapping);
        $replace = array_values($mapping);
        $files = 0;
        $total = 0;
        $classList = $this->find_class_filter($module, $type, $path, $suffix);
        foreach ($classList as $lname) {
            $class = $this->classes[$lname];
            $this->log('Class method ' . $class['name'] . ' - FOUND');
            $contents = file_get_contents($class['file']);
            $count = 0;
            $contents = preg_replace($search, $replace, $contents, -1, $count);
            if ($update && $count > 0 && !empty($contents)) {
                file_put_contents($class['file'], $contents);
            } elseif ($count > 0) {
                $this->log('Still replacements in ' . $class['file'], true);
            }
            $files += 1;
            $total += $count;
        }
        $this->log('Found Class Methods: ' . $files . ' with ' . $total . ' replacements', true);
        $this->log('Check for un-needed $this->getContext() in replaced calls ' . ' - TODO', true);
        return $classList;
    }

    public function replace_method_services($module, $type = '', $replace = false)
    {
        return $this->replace_core_services($module, $type, '', 'Method', $replace);
    }

    public function replace_property_services($module, $replace = false)
    {
        return $this->replace_core_services($module, '', '/xarproperties/', '', $replace);
    }

    public function replace_block_services($module, $replace = false)
    {
        return $this->replace_core_services($module, '', '/xarblocks/', '', $replace);
    }

    public function replace_installer_services($module, $replace = false)
    {
        return $this->replace_core_services($module, '', 'installer.php', '', $replace);
    }

    public function replace_internal_methods($module, $type = '', $update = false)
    {
        $found = $this->find_module_methods($module, $type);
        $files = 0;
        $total = 0;
        foreach ($found as $namespace => $methods) {
            $pieces = explode('\\', $namespace);
            $className = array_pop($pieces);
            foreach ($methods as $methodName => $lname) {
                $class = $this->classes[$lname];
                $this->log('Class Method in ' . $namespace . ': ' . $methodName . ' - FOUND ' . $class['file']);
                $contents = file_get_contents($class['file']);
                $replaced = 0;
                $todo = [];
                $count = 0;
                $search = '/xarMod::apiFunc\(\s*\'' . $module . '\',\s*\'(\w+)\',\s*\'(\w+)\'(,\s*(.*?)(,\s*\$this->getContext\(\)\s*|)|)\)/s';
                $matches = [];
                if (preg_match_all($search, $contents, $matches, PREG_PATTERN_ORDER)) {
                    $unique = array_unique($matches[1]);
                    array_walk($unique, function (&$value) {
                        $value .= 'api';
                    });
                    $todo = array_merge($todo, $unique);
                    $replace = '\$$1api->$2($4)';
                    $contents = preg_replace($search, $replace, $contents, -1, $count);
                    $replaced += $count;
                }
                $count = 0;
                $search = '/xarMod::guiFunc\(\s*\'' . $module . '\',\s*\'(\w+)\',\s*\'(\w+)\'(,\s*(.*?)(,\s*\$this->getContext\(\)\s*|)|)\)/s';
                $matches = [];
                if (preg_match_all($search, $contents, $matches, PREG_PATTERN_ORDER)) {
                    $unique = array_unique($matches[1]);
                    array_walk($unique, function (&$value) {
                        $value .= 'gui';
                    });
                    $todo = array_merge($todo, $unique);
                    $replace = '\$$1gui->$2($4)';
                    $contents = preg_replace($search, $replace, $contents, -1, $count);
                    $replaced += $count;
                }
                if (count($todo) > 0) {
                    $extra = '';
                    $uses = '';
                    foreach ($todo as $api) {
                        $name = ucfirst(str_replace(['api', 'gui'], ['Api', 'Gui'], $api));
                        if ($name != $className) {
                            $uses .= 'use ' . implode('\\', $pieces) . '\\' . $name . ";\n";
                        }
                        $extra .= "\n" . '        /** @var ' . $name . ' $' . $api . ' */';
                        $extra .= "\n" . '        $' . $api . ' = $this->' . $api . '();';
                    }
                    $contents = str_replace("use $namespace;\n", "use $namespace;\n" . $uses, $contents);
                    $contents = preg_replace('/(public function __invoke.*\n\s+{(\n\s+extract\(\$args\);|))/', '$1' . $extra, $contents);
                }
                if (!str_contains($contents, ' * @see ')) {
                    $see = '* @see ' . $className . '::' . $methodName . "()\n     ";
                    $contents = preg_replace('/(\*\/\n\s+public function __invoke)/', $see . '$1', $contents);
                    $replaced += 1;
                }
                if ($update && $replaced > 0 && !empty($contents)) {
                    file_put_contents($class['file'], $contents);
                    $this->log($namespace . ' ' . $class['file'] . ': ' . implode(', ', $todo), true);
                } elseif ($replaced > 0) {
                    $this->log($namespace . ' ' . $class['file'] . ': ' . implode(', ', $todo), true);
                    $this->log($contents);
                }
                $files += 1;
                $total += $replaced;
            }
        }
        $this->log('Found Class Methods: ' . $files . ' with ' . $total . ' replacements', true);
        return $found;
    }
}

$refresh = false;
if ($refresh || !file_exists('core_functions.json') || !file_exists('core_constants.json') || !file_exists('core_classes.json')) {
    $inDir = dirname(__DIR__, 2) . '/html/lib/xaraya';
    $extraFiles = [dirname(__DIR__, 2) . '/html/bootstrap.php'];
    $analyzer = new XarayaCoreAnalyzer();
    $analyzer->verbose = true;
    $analyzer->parse_core_files($inDir, $extraFiles);
}
//$analyzer = new XarayaCoreAnalyzer();
//$analyzer->show_class_tree();

/**
$fixMe = false;
//$inDir = dirname(__DIR__, 2) . '/html/lib/';  // don't fixMe this - use only for verification
$inDir = dirname(__DIR__, 2) . '/html/code/modules/';
//$inDir = dirname(__DIR__, 2) . '/html/code/';
//$inDir = dirname(__DIR__, 2).'/html/themes/';
//$inDir = dirname(__DIR__, 2).'/vendor/xaraya/';
$analyzer = new XarayaModuleAnalyzer();
$analyzer->verbose = true;
$analyzer->check_module_files($inDir, $fixMe);
 */

/**
//$modName = 'dynamicdata';
//$inDir = dirname(__DIR__, 2) . '/html/code/modules/' . $modName . '/';
//$inDir = dirname(__DIR__, 2).'/vendor/xaraya/modules/cachemanager/';
//$inDir = dirname(__DIR__, 2) . '/html/code/modules/';
$inDir = dirname(__DIR__, 2) . '/vendor/xaraya/library';
$analyzer = new XarayaModuleAnalyzer($inDir, true);
$analyzer->verbose = true;
$analyzer->load_project();
$analyzer->parse_project();
//echo $analyzer->to_json($analyzer->totals);
//echo $analyzer->to_json(array_keys($analyzer->functions));
// @todo
//$analyzer->find_module_functions();
//$analyzer->find_module_classes();
$analyzer->find_installer_functions();
//$analyzer->find_installer_classes();
//ksort($analyzer->classes);
//echo $analyzer->to_json($analyzer->classes);
//file_put_contents('module_classes.json', $analyzer->to_json($analyzer->classes));
//$analyzer->get_class_tree();
//$analyzer->show_class_tree();
//$analyzer->show_class_tree('dataproperty');
/**
 */

/**
 */
$inDir = dirname(__DIR__, 2) . '/vendor/xaraya/';
$inDir = dirname(__DIR__, 2) . '/html/code/modules/dynamicdata/';
$migrator = new XarayaModuleMigrator($inDir, true);
$migrator->verbose = false;
$migrator->load_project();
$migrator->parse_project();
$refresh = false;
//$migrator->migrate_installer_functions($refresh);
//$migrator->find_installer_classes();
//$types = ['user', 'userapi', 'admin', 'adminapi', 'utilapi', 'restapi', 'dataapi'];
//$types = ['hooksapi', 'indexapi', 'wordsapi', 'hooks'];
//foreach ($types as $type) {
//    $migrator->migrate_module_functions($type, $refresh);
//}
//$migrator->check_method_casing();
$replace = false;
//$migrator->document_module_methods('dynamicdata', '', $replace);
//$found = $migrator->replace_method_services('dynamicdata', '', $replace);
//$found = $migrator->replace_property_services('dynamicdata', $replace);
//$found = $migrator->replace_block_services('dynamicdata', $replace);
//$migrator->replace_internal_methods('dynamicdata', '', $replace);
[$called, $summary] = $migrator->find_called_dependencies('dynamicdata', '', '');
file_put_contents('call_dependencies.json', $migrator->to_json($called));
$output = $migrator->draw_mermaid_graph($called);
file_put_contents('call_dependencies.md', $output);
/**
$modules = [
    'apischemas', 'cachemanager', 'calendar', 'changelog', 'ckeditor', 'comments',
    'hitcount', 'images', 'keywords', 'library', 'logconfig', 'messages', 'mime',
    'publications', 'ratings', 'scheduler', 'skeleton', 'uploads', 'webhooks', 'workflow',
];
$modules = [
    'authsystem', 'base', 'blocks', 'categories', 'dynamicdata', 'installer',
    'mail', 'modules', 'privileges', 'roles', 'themes',
];
$replace = false;
foreach ($modules as $module) {
    echo "\nModule $module\n";
    $inDir = dirname(__DIR__, 2) . '/html/code/modules/' . $module . '/';
    $migrator = new XarayaModuleMigrator($inDir, true);
    $migrator->verbose = false;
    try {
        $migrator->load_project();
    } catch (Exception $e) {
        echo $e->getMessage() . ': ' . $e->getTraceAsString() .  "\n";
        echo "for module $module\n";
        continue;
    }
    $migrator->parse_project();
    //$migrator->replace_internal_methods($module, '', $replace);
    //$found = $migrator->replace_method_services($module, '', $replace);
    //$found = $migrator->replace_property_services($module, $replace);
    //$found = $migrator->replace_block_services($module, $replace);
}
 */
