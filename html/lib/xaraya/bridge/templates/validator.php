<?php

namespace Xaraya\Bridge\TemplateEngine;

use Twig\Environment;
use Exception;

/**
 * Experimental template validator for Twig templates
 *
 * Usage:
 * ```php
 * use Xaraya\Bridge\TemplateEngine\TwigValidator;
 *
 * // validate all *.html.twig templates from workflow module
 * $options = [
 *     'namespace' => 'workflow',
 *     'extension' => '.html.twig',
 * ];
 * $validator = new TwigValidator($options);
 * $targetPath = dirname(__DIR__) . '/templates';
 * $converter->convertDir($sourcePath, $targetPath, '.xt');
 * ```
 * @todo parse extends + add comments in files
 */
class TwigValidator
{
    /** @var array<string, mixed> */
    public array $options = [];
    public string $basePath = '.';
    public string $extension = '.html.twig';
    /** @var array<string, array<string, string>> */
    public array $variables = [];

    /**
     * @param array<string, mixed> $options
     * with
     *     string $options['namespace'] the twig namespace to use when validating files
     *     string $options['extension'] the template filename extension to look for
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        if (!empty($this->options['extension'])) {
            $this->extension = $this->options['extension'];
        }
    }

    /**
     * Get the current namespace
     * @return string namespace or empty
     */
    public function getNamespace()
    {
        return $this->options['namespace'] ?? '';
    }

    /**
     * Get the current extension
     * @return string current extension
     */
    public function getExtension()
    {
        return $this->extension;
    }

    public function getDirPaths(string $dirPath)
    {
        $paths = [];
        $extension = $this->getExtension();
        $fileList = scandir($dirPath);
        foreach ($fileList as $fileName) {
            if (str_starts_with($fileName, '.')) {
                continue;
            }
            $filePath = $dirPath . '/' . $fileName;
            if (is_dir($filePath)) {
                $paths += $this->getDirPaths($filePath);
                continue;
            }
            if (!str_ends_with($fileName, $extension)) {
                continue;
            }
            $paths[] = $filePath;
        }
        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateDir(string $targetPath)
    {
        $this->basePath = $targetPath;
        $twig = TwigConfig::getTwigEnvironment();

        echo "Directory $targetPath:\n";
        $paths = $this->getDirPaths($targetPath);
        return $this->validate($twig, $paths);
    }

    /**
     * Validate all twig templates listed in paths (by validateDir or other)
     * @param ?list<string> $paths list of file paths to validate
     * @return array<string, mixed>
     */
    public function validate(Environment $twig, ?array $paths = null)
    {
        $paths ??= $this->getDirPaths($this->basePath);
        $namespace = $this->getNamespace();
        $issues = [];
        $dependencies = [];
        echo "Issues by file:\n";
        foreach ($paths as $path) {
            try {
                $code = file_get_contents($path);
                if ($code === false) {
                    throw new Exception('Unable to get file ' . $path);
                }
                $name = substr($path, strlen($this->basePath) + 1);
                if (!empty($namespace)) {
                    $name = '@' . $namespace . '/' . $name;
                } else {
                    $name = basename($this->basePath) . '/' . $name;
                }
                $nodes = $twig->parse($twig->tokenize(new \Twig\Source($code, $name, $path)));
                $this->variables = [
                    'extends' => [],
                    'includes' => [],
                    'input' => [],
                    'set' => [],
                    'var' => [],
                    'path' => [],
                    'includedby' => [],
                ];
                //echo "Variables in $path:\n    ";
                $this->getVariables($nodes);
                //$this->variables = array_filter($this->variables);
                //echo json_encode($this->variables, JSON_PRETTY_PRINT);
                $dependencies[$name] = $this->variables;

                // the $code is valid
            } catch (\Twig\Error\SyntaxError $e) {
                // $code contains one or more syntax errors
                $message = $e->getMessage();
                $line = $e->getTemplateLine();
                echo "Syntax error in $path:" . $line . "\n  " . $message . "\n";
                $issues[$message] ??= [];
                $issues[$message][] = $path . ':' . $line;
            }
        }
        foreach (array_keys($dependencies) as $name) {
            $dependencies[$name]['descendants'] = [];
            $done = [];
            $children = $dependencies[$name]['includes'];
            while (count($children) > 0) {
                $child = array_shift($children);
                $dependencies[$name]['descendants'][] = $child;
                $dependencies[$child]['includedby'] ??= [];
                $dependencies[$child]['includedby'][] = $name;
                $dependencies[$child]['includes'] ??= [];
                foreach ($dependencies[$child]['includes'] as $baby) {
                    // avoid self-referencing templates
                    if ($baby == $child || in_array($baby, $done) || in_array($baby, $children)) {
                        continue;
                    }
                    array_unshift($children, $baby);
                    array_push($done, $baby);
                }
                array_push($done, $child);
            }
        }
        foreach (array_keys($dependencies) as $name) {
            $dependencies[$name] = array_filter($dependencies[$name]);
        }
        /**
        $input = $dependencies[$child]['input'];
        foreach ($input as $var => $text) {
            // tell parent that child requires input - recursively?
        }
        foreach ($dependencies['included'] as $included => $including) {
            $input = $dependencies['variables'][$included]['input'] ?? [];
            $children = $dependencies['variables'][$included]['includes'] ?? [];
            foreach ($children as $child) {
                // avoid self-referencing templates
                if ($child == $included) {
                    continue;
                }
                $childinput = $dependencies['variables'][$child]['input'] ?? [];
                if (!empty($childinput)) {
                    $input[$child] = $childinput;
                }
            }
            foreach ($including as $parent) {

            }
        }
         */
        if (empty($issues)) {
            echo "No issues found\n";
            return $dependencies;
        }
        echo "Top issues by count:\n";
        uasort($issues, function ($a, $b) {
            return count($b) <=> count($a);
        });
        foreach ($issues as $message => $files) {
            echo "Syntax error: $message (" . count($files) . "):\n  ";
            echo implode("\n  ", $files);
            echo "\n";
        }
        return $dependencies;
    }

    public function getVariables($nodes)
    {
        $variables = [];
        foreach ($nodes as $node) {
            if ($node instanceof \Twig\Node\Expression\NameExpression) {
                $name = $node->getAttribute('name');
                if ($node->getAttribute('always_defined')) {
                    $this->variables['var'][$name] ??= $name;
                } elseif (array_key_exists($name, $this->variables['set'])) {
                    //$this->variables['input'][$name] ??= $name . ' *';
                } else {
                    $this->variables['input'][$name] ??= $name;
                }
                $variables[$name] = $name;
            } elseif ($node->getNodeTag() === 'extends') {
                var_dump($node);
                //$this->variables['extends'][$value] ??= $value;
            } elseif ($node instanceof \Twig\Node\IncludeNode) {
                $expr = $node->getNode('expr');
                var_dump($expr);
                //$this->variables['includes'][$value] ??= $value;
            } elseif ($node instanceof \Twig\Node\Expression\FunctionExpression) {
                $name = $node->getAttribute('name');
                if ($name == 'include') {
                    foreach ($node->getNode('arguments') as $arg) {
                        if ($arg instanceof \Twig\Node\Expression\ConstantExpression) {
                            $value = $arg->getAttribute('value');
                            $this->variables['includes'][$value] ??= $value;
                        } elseif ($arg instanceof \Twig\Node\Expression\Binary\ConcatBinary) {
                            $left = $arg->getNode('left');
                            $right = $arg->getNode('right');
                            $values = [];
                            while ($right instanceof \Twig\Node\Expression\Binary\ConcatBinary) {
                                if ($left instanceof \Twig\Node\Expression\ConstantExpression) {
                                    $values[] = $left->getAttribute('value');
                                } else {
                                    echo "Left: ";
                                    var_dump($left);
                                }
                                $left = $right->getNode('left');
                                $right = $right->getNode('right');
                            }
                            if ($right instanceof \Twig\Node\Expression\ConstantExpression) {
                                $values[] = $right->getAttribute('value');
                            } else {
                                echo "Right: ";
                                var_dump($right);
                            }
                            $value = implode('.', $values);
                            $this->variables['includes'][$value] ??= $value;
                        } elseif ($arg instanceof \Twig\Node\Expression\ArrayExpression) {
                            // @todo
                        } else {
                            var_dump($arg);
                        }
                        break;
                    }
                }
                $variables += $this->getVariables($node);
            } elseif ($node instanceof \Twig\Node\SetNode) {
                foreach ($node->getNode('names') as $names) {
                    $name = $names->getAttribute('name');
                    $this->variables['set'][$name] ??= $name;
                }
                $variables += $this->getVariables($node);
                /**
                } elseif ($node instanceof \Twig\Node\Expression\AssignNameExpression) {
                    $name = $node->getAttribute('name');
                    if ($node->getAttribute('always_defined')) {
                        $this->variables['assign'][$name] ??= $name . ' *';
                    } else {
                        $this->variables['assign'][$name] ??= $name;
                    }
                    $variables[$name] = $name;
                 */
            } elseif ($node instanceof \Twig\Node\Expression\ConstantExpression && $nodes instanceof \Twig\Node\Expression\GetAttrExpression) {
                $value = $node->getAttribute('value');
                if (!empty($value) && is_string($value)) {
                    //$this->variables['value'][$value] ??= $value;
                    $variables[$value] = $value;
                }
            } elseif ($node instanceof \Twig\Node\Expression\GetAttrExpression) {
                $path = implode('.', $this->getVariables($node));
                if (!empty($path)) {
                    $this->variables['path'][$path] ??= $path;
                    $variables[$path] = $path;
                }
            } elseif ($node instanceof \Twig\Node\Node) {
                $variables += $this->getVariables($node);
            }
        }
        return $variables;
    }
}
