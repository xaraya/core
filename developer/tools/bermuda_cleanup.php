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
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
//use PhpParser\PrettyPrinter\Standard as PrettyPrinter;


class XarayaCodeAnalyzer
{
    public const PHP_EXT = '/\.php$/';

    public $project = null;
    public $functions = [];
    public $constants = [];
    public $classes = [];
    public $totals = [];
    public $inDir = null;
    public $fileExt = null;
    public $verbose = false;
    public $refresh = false;

    public function __construct($inDir = null, $fileExt = self::PHP_EXT)
    {
        $this->initialize($inDir, $fileExt);
    }

    public function initialize($inDir, $fileExt)
    {
        if (!empty($inDir)) {
            $this->inDir = $inDir;
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
    public function load_project($inDir = null, $extraFiles = [], $fileExt = self::PHP_EXT)
    {
        $this->initialize($inDir, $fileExt);

        // iterate over all .php files in the directory
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->inDir));
        $files = new \RegexIterator($files, $this->fileExt);

        $factory = \phpDocumentor\Reflection\Php\ProjectFactory::createInstance();
        $localFiles = [];
        foreach ($files as $file) {
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
        $name = $function->getName();
        $lname = strtolower($name);
        if (array_key_exists($lname, $this->functions)) {
            $this->log('Function Conflict: ' . $name, true);
        }
        $args = $this->get_arguments($function);
        $this->functions[$lname] = ['file' => $fpath, 'name' => $name, 'args' => $args];
        $this->functions[$lname]['line'] = $function->getLocation()->getLineNumber();
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
            $tags = $docblock->getTagsByName('uses');
            return str_replace('\\', '', implode(', ', $tags));
        }
        return null;
    }

    public function add_constant($constant, $fpath)
    {
        $name = $constant->getName();
        $lname = strtolower($name);
        if (array_key_exists(strtolower($lname), $this->constants)) {
            $this->log('Constant Conflict: ' . $name, true);
        }
        $this->constants[$lname] = ['file' => $fpath, 'name' => $name, 'value' => $constant->getValue()];
    }

    public function add_class($class, $fpath)
    {
        $name = $class->getName();
        $lname = strtolower($name);
        if (array_key_exists(strtolower($lname), $this->classes)) {
            $this->log('Class Conflict: ' . $name, true);
        }
        $this->classes[$lname] = ['file' => $fpath, 'name' => $name, 'methods' => [], 'const' => []];
        $fqsen = (string) $class->getFqsen();
        if ($fqsen !== '\\' . $name) {
            $this->classes[$lname]['namespace'] = substr($fqsen, 0, strlen($fqsen) - strlen('\\' . $name));
        }
        $this->classes[$lname]['parent'] = (string) $class->getParent();
        $this->classes[$lname]['line'] = $class->getLocation()->getLineNumber();
        foreach ($class->getMethods() as $method) {
            $mname = $method->getName();
            $args = $this->get_arguments($method);
            $this->classes[$lname]['methods'][strtolower($mname)] = ['name' => $mname, 'args' => $args];
            $this->classes[$lname]['methods'][strtolower($mname)]['line'] = $method->getLocation()->getLineNumber();
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

    public function get_next_return($fpath, $line)
    {
        $file = $this->project->getFiles()[$fpath];
        $lines = array_slice(explode("\n", $file->getSource()), $line - 1);
        //echo implode("\n", $lines);
        foreach ($lines as $line) {
            if (strpos($line, ' return ') !== false) {
                return $line;
            }
        }
        return null;
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
        $inDir = dirname(dirname(__DIR__)) . '/html/lib/xaraya';
        $extraFiles = [dirname(dirname(__DIR__)) . '/html/bootstrap.php'];
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
        $this->log($function['name'] . ' TODO: ' . $function['line'] . ' ' . $function['file']);
        $line = $this->get_next_return($function['file'], $function['line']);
        if (!empty($line) && preg_match('/ return (\w+)::(\w+)\(([^\)]*)/', $line, $matches)) {
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
        $this->find_core_replaced();
    }

    public function get_class_tree()
    {
        if (empty($this->classes)) {
            $this->load_core_classes();
        }
        $this->classroot = new xarNode('root');
        foreach (array_keys($this->classes) as $lname) {
            if (!array_key_exists('node', $this->classes[$lname])) {
                if (!empty($this->classes[$lname]['namespace'])) {
                    $this->classes[$lname]['node'] = new xarNode($this->classes[$lname]['namespace'] . '\\' . $this->classes[$lname]['name']);
                } else {
                    $this->classes[$lname]['node'] = new xarNode($this->classes[$lname]['name']);
                }
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
            if (strpos($class['parent'], '\\', 1) !== false) {
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
            try {
                $found += 1;
                $contents = file_get_contents($file->getPathName());
                if (!preg_match_all($pattern, $contents, $matches)) {
                    continue;
                }
                if (strpos($file->getPathName(), '/legacy/') !== false) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (strpos($file->getPathName(), 'xarayatesting/tests/core/') !== false) {
                    $this->log($file->getPathName() . ' - ' . count($matches[0]) . ' SKIP');
                    continue;
                }
                if (strpos($file->getPathName(), '/vendor/composer/') !== false) {
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
        if (strpos($inDir, '/lib/xaraya/') !== false) {
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

    public function find_module_functions()
    {
        $found = 0;
        foreach (array_keys($this->functions) as $lname) {
            $found += $this->match_module_function($lname);
        }
        $this->log('Found Functions: ' . $found, true);
    }

    public function match_module_function($lname)
    {
        $function = $this->functions[$lname];
        if (!empty($function['uses']) && preg_match('/^(xar[A-Z]\w+)::(\w+)\(\)$/', $function['uses'], $matches)) {
            $this->functions[$lname]['class'] = $matches[1];
            $this->functions[$lname]['method'] = $matches[2];
            $this->log($function['name'] . ' USES ' . $matches[1] . '::' . $matches[2] . '()');
            return 1;
        }
        if (!preg_match('/^(\w+)_(\w+)_(\w+)$/', $function['name'], $matches)) {
            $this->log($function['name'] . ' SKIP ' . $function['file']);
            return 0;
        }
        $this->functions[$lname]['module'] = $matches[1];
        $this->functions[$lname]['type'] = $matches[2];
        $this->functions[$lname]['func'] = $matches[3];
        $function = array_replace($function, $this->functions[$lname]);
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
                return 1;
            }
        }
         */
        $this->log($function['name'] . ' TODO: ' . $function['line'] . ' ' . $function['file']);
        $line = $this->get_next_return($function['file'], $function['line']);
        if (!empty($line) && preg_match('/ return (\w+)::(\w+)\(([^\)]*)/', $line, $matches)) {
            $this->log($function['name'] . ' FOUND ' . $matches[1] . '::' . $matches[2] . ' ' . $function['file']);
            $this->functions[$lname]['class'] = $matches[1];
            $this->functions[$lname]['method'] = $matches[2];
            $this->functions[$lname]['rargs'] = $matches[3];
            //$this->functions[$lname]['uses'] = $matches[1] . '::' . $matches[2] . '()';
            return 1;
        }
        $this->log(trim($line));
        //$this->functions[$lname]['return'] = trim($line);
        $this->log($function['name'] . ' LOST ');
        return 0;
    }

    public function find_module_classes()
    {
        $found = 0;
        foreach (array_keys($this->classes) as $lname) {
            $found += $this->match_module_class($lname);
        }
        $this->log('Found Class Methods: ' . $found, true);
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
            $this->log($class['name'] . '::' . $method['name'] . ' TODO: ' . $method['line'] . ' ' . $class['file']);
            $line = $this->get_next_return($class['file'], $method['line']);
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
        return $count;
    }
}

$refresh = false;
if ($refresh || !file_exists('core_functions.json') || !file_exists('core_constants.json') || !file_exists('core_classes.json')) {
    $inDir = dirname(dirname(__DIR__)) . '/html/lib/xaraya';
    $extraFiles = [dirname(dirname(__DIR__)) . '/html/bootstrap.php'];
    $analyzer = new XarayaCoreAnalyzer();
    $analyzer->verbose = true;
    $analyzer->parse_core_files($inDir, $extraFiles);
}
//$analyzer = new XarayaCoreAnalyzer();
//$analyzer->show_class_tree();

/**
 */
$fixMe = false;
//$inDir = dirname(dirname(__DIR__)) . '/html/lib/';  // don't fixMe this - use only for verification
$inDir = dirname(dirname(__DIR__)) . '/html/code/modules/';
//$inDir = dirname(dirname(__DIR__)) . '/html/code/';
//$inDir = dirname(dirname(__DIR__)).'/html/themes/';
//$inDir = dirname(dirname(__DIR__)).'/vendor/xaraya/';
$analyzer = new XarayaModuleAnalyzer();
$analyzer->verbose = true;
$analyzer->check_module_files($inDir, $fixMe);
/**
$contents = file_get_contents('/home/mikespub/xaraya-modules/selected.json');
$repos = json_decode($contents, true);
foreach (array_keys($repos) as $repo) {
    $inDir = "/home/mikespub/xaraya-$repo";
    echo $inDir . "\n";
    $analyzer = new XarayaModuleAnalyzer();
    $analyzer->verbose = true;
    $analyzer->check_module_files($inDir, $fixMe);
}
 */

/**
//$modName = 'dynamicdata';
//$inDir = dirname(dirname(__DIR__)) . '/html/code/modules/' . $modName . '/';
//$inDir = dirname(dirname(__DIR__)).'/vendor/xaraya/modules/xarcachemanager/';
$inDir = dirname(dirname(__DIR__)) . '/html/code/modules/';
//$inDir = dirname(dirname(__DIR__)) . '/vendor/xaraya/modules/';
$analyzer = new XarayaModuleAnalyzer($inDir);
//$analyzer->verbose = true;
$analyzer->load_project();
$analyzer->parse_project();
echo $analyzer->to_json($analyzer->totals);
echo $analyzer->to_json(array_keys($analyzer->functions));
// @todo
//$analyzer->find_module_functions();
//$analyzer->find_module_classes();
//ksort($analyzer->classes);
//file_put_contents('module_classes.json', $analyzer->to_json($analyzer->classes));
//$analyzer->get_class_tree();
//$analyzer->show_class_tree();
$analyzer->show_class_tree('dataproperty');
 */
