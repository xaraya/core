<?php

namespace Xaraya\Bridge\TemplateEngine;

/**
 * Experimental template converter from Blocklayout to Twig syntax
 *
 * Usage:
 * ```php
 * use Xaraya\Bridge\TemplateEngine\BlocklayoutToTwigConverter;
 *
 * // convert all test_*.xt templates from includes directory
 * $options = [
 *     'namespace' => 'workflow/includes',
 * ];
 * $converter = new BlocklayoutToTwigConverter($options);
 * $sourcePath = dirname(__DIR__) . '/xartemplates/includes';
 * $targetPath = dirname(__DIR__) . '/templates/includes';
 * $converter->convertDir($sourcePath, $targetPath, '.xt', 'test_');
 * ```
 * @todo fix ternary + add more tags
 */
class TwigConverter
{
    /** @var array<string, mixed> */
    public array $options = [];
    public string $content = '';
    public string $prefix = '';
    public string $suffix = '';

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function convertDir(string $fromPath, string $toPath, string $suffix = '.xt', string $prefix = '')
    {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $fileList = scandir($fromPath);
        foreach ($fileList as $fileName) {
            if (!empty($prefix) && !str_starts_with($fileName, $prefix)) {
                continue;
            }
            if (!str_ends_with($fileName, $suffix)) {
                continue;
            }
            $source = $fromPath . '/' . $fileName;
            if (!is_file($source)) {
                continue;
            }
            if (!empty($prefix)) {
                $fileName = substr($fileName, strlen($prefix));
            }
            $fileName = substr($fileName, 0, strlen($fileName) - strlen($suffix)) . '.html.twig';
            $target = $toPath . '/' . $fileName;
            echo "$source -> $target\n";
            $this->convertFile($source, $target);
        }
    }

    public function convertFile(string $fromPath, string $toPath)
    {
        $content = file_get_contents($fromPath);
        $content = $this->convert($content);
        file_put_contents($toPath, $content);
    }

    public function convert(string $content)
    {
        $this->content = $content;
        return $this->content;
    }
}

class BlocklayoutToTwigConverter extends TwigConverter
{
    public function convert(string $content)
    {
        $this->content = $content;
        $this->removeHeader();
        $this->removeFooter();
        $this->replaceTemplateFile();
        $this->replaceIf();
        $this->replaceForEach();
        $this->replaceSet();
        $this->replaceVar();
        $this->replaceImageTag();
        $this->replaceButtonTag();
        return $this->content;
    }

    public function getNamespace()
    {
        return $this->options['namespace'] ?? '';
    }

    /**
     * Remove header
     * <?xml version="1.0" encoding="utf-8"?>
     * <xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">
     */
    public function removeHeader()
    {
        $pattern = '~^<\?xml version="1.0" encoding="utf-8"\?>\s*<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">\s*~i';
        $replace = '';
        $this->content = preg_replace($pattern, $replace, $this->content);
    }

    /**
     * Remove footer
     * </xar:template>
     */
    public function removeFooter()
    {
        $pattern = '~</xar:template>\s*$~i';
        $replace = '';
        $this->content = preg_replace($pattern, $replace, $this->content);
    }

    /**
     * Replace template file
     * <xar:template file="..."/>
     */
    public function replaceTemplateFile()
    {
        $pattern = '~<xar:template file="([^"]+)"\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            if (!empty($this->prefix)) {
                $file = substr($matches[1], strlen($this->prefix));
            } else {
                $file = $matches[1];
            }
            $namespace = $this->getNamespace();
            if (!empty($namespace)) {
                return '{{ include(\'@' . $namespace . '/' . $file . '.html.twig\') }}';
            }
            return '{{ include(\'' . $file . '.html.twig\') }}';
        }, $this->content);
    }

    /**
     * Replace if control structure
     * <xar:if condition="...">
     * <xar:elseif condition="..."/>
     * <xar:else/>
     * </xar:if>
     */
    public function replaceIf()
    {
        $pattern = '~<xar:if condition="([^"]+)">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% if ' . $this->replaceCondition($matches[1]) . ' %}';
        }, $this->content);

        $pattern = '~<xar:elseif condition="([^"]+)"\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% elseif ' . $this->replaceCondition($matches[1]) . ' %}';
        }, $this->content);

        $pattern = '~<xar:else\s*/>~i';
        $replace = '{% else %}';
        $this->content = preg_replace($pattern, $replace, $this->content);

        $pattern = '~</xar:if>~i';
        $replace = '{% endif %}';
        $this->content = preg_replace($pattern, $replace, $this->content);
    }

    /**
     * Replace foreach control structure
     * <xar:foreach in="$..." value="$...">
     * <xar:foreach in="$..." key="$..." value="$...">
     * <xar:continue/> - @todo there is no break or continue in Twig?
     * </xar:foreach>
     */
    public function replaceForEach()
    {
        $pattern = '~<xar:foreach in="([^"]+)" value="([^"]+)">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% for ' . $this->replaceVariable($matches[2]) . ' in ' . $this->replaceVariable($matches[1]) . ' %}';
        }, $this->content);

        $pattern = '~<xar:foreach in="([^"]+)" key="([^"]+)" value="([^"]+)">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% for ' . $this->replaceVariable($matches[2]) . ', ' . $this->replaceVariable($matches[3]) . ' in ' . $this->replaceVariable($matches[1]) . ' %}';
        }, $this->content);

        // @todo handle <xar:continue/>

        $pattern = '~</xar:foreach>~i';
        $replace = '{% endfor %}';
        $this->content = preg_replace($pattern, $replace, $this->content);
    }

    /**
     * Replace set variable
     * <xar:set name="...">...</xar:set>
     * <xar:var name="...">...</xar:var> - @todo support scope="..."
     */
    public function replaceSet()
    {
        $pattern = '~<xar:set name="([^"]+)">([^<]+)</xar:set>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% set ' . $matches[1] . ' = ' . $this->replaceExpression($matches[2]) . ' %}';
        }, $this->content);

        // @todo support scope="..."
        $pattern = '~<xar:var name="([^"]+)">([^<]+)</xar:var>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% set ' . $matches[1] . " = '" . trim($matches[2]) . "' %}";
        }, $this->content);
    }

    /**
     * Replace var get
     * <xar:var name="..."/>
     * #$...#
     */
    public function replaceVar()
    {
        $pattern = '~<xar:var name="([^"]+)"/>~i';
        $replace = '{{ $1 }}';
        $this->content = preg_replace($pattern, $replace, $this->content);

        // avoid matching &#160; here
        $pattern = '~#([^\d][^#]+)#~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ ' . $this->replaceExpression($matches[1]) . ' }}';
        }, $this->content);
    }

    /**
     * Replace image
     * <xar:img scope="theme" file="icons/info.png" class="xar-icon" alt="info"/>
     */
    public function replaceImageTag()
    {
        // we strip spaces before & after for image
        $pattern = '~<xar:img ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{- xar_image(' . $this->replaceAttributes($matches[1]) . ') -}}';
        }, $this->content);
    }

    /**
     * Replace button
     * <xar:button type="link" name="$name" target="$runlink" label="$label"/>
     */
    public function replaceButtonTag()
    {
        // we strip spaces before & after for image
        $pattern = '~<xar:button ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{- xar_button(' . $this->replaceAttributes($matches[1]) . ') -}}';
        }, $this->content);
    }

    /**
     * Replace workflow actions
     * <xar:workflow-actions name="actions" config="$config" item="$item" title="$item['marking']" template="$item['marking']"/>
     */
    public function replaceWorkflowActions()
    {
        $pattern = '~<xar:workflow-actions ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_workflow_actions(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);
    }

    public function replaceAttributes($attributes)
    {
        $pieces = explode(' ', $attributes);
        $parts = [];
        foreach ($pieces as $piece) {
            [$name, $value] = explode('=', $piece);
            $name = trim($name);
            $value = trim($value);
            if (str_contains($value, '$')) {
                $value = trim($value, '"');
                $parts[] = $name . ': ' . $this->replaceVariable($value);
            } else {
                $parts[] = $name . ': ' . $value;
            }
        }
        return '{' . implode(', ', $parts) . '}';
    }

    public function replaceCondition($condition)
    {
        $condition = $this->replaceFunctions($condition);
        $condition = $this->replaceConstants($condition);
        $condition = $this->replaceVariable($condition);
        return str_replace([' eq ', ' gt ', ' AND ', ' OR ', '!', '^'], [' == ', ' > ', ' and ', ' or ', 'not ', ':'], $condition);
    }

    public function replaceExpression($expression)
    {
        $expression = $this->replaceArrays($expression);
        $expression = $this->replaceFunctions($expression);
        $expression = $this->replaceConstants($expression);
        $expression = $this->replaceVariable($expression);
        // string concatenation and replace placeholder in arrays - @todo issue with ternary ... ? ... : ...
        return str_replace([' . ', '^'], [' ~ ', ':'], $expression);
    }

    public function replaceArrays($expression)
    {
        if (!str_contains($expression, ' => ')) {
            return $expression;
        }
        // not matching correctly if last item is array
        $pattern = '~\[([^]]+)\]~i';
        $fixme = false;
        $expression = preg_replace_callback($pattern, function ($matches) use (&$fixme) {
            if (!str_contains($matches[1], ' => ')) {
                return $matches[0];
            }
            $pieces = explode(',', $matches[1]);
            $parts = [];
            foreach ($pieces as $piece) {
                [$name, $value] = explode(' => ', $piece);
                $name = trim($name);
                $value = trim($value);
                // we get into trouble using : here if we call replaceVariable() later - use ^ as placeholder
                if (str_contains($name, '$')) {
                    $parts[] = '(' . $this->replaceVariable($name) . ')^ ' . $this->replaceVariable($value);
                } else {
                    $parts[] = $name . '^ ' . $this->replaceVariable($value);
                }
            }
            $last = end($parts);
            if (str_contains($last, '[')) {
                $fixme = true;
            }
            return '{' . implode(', ', $parts) . '}';
        }, $expression);
        // fix not matching correctly if last item is array
        if ($fixme) {
            $expression = str_replace("'}]", "']}", $expression);
        }
        return $expression;
    }

    public function replaceFunctions($expression)
    {
        $mapping = [
            'xarMod::apiFunc(' => 'xar_apifunc(',
            'xarServer::getModuleURL(' => 'xar_moduleurl(',
            'xarServer::getObjectURL(' => 'xar_objecturl(',
            'xarTpl::getImage(' => 'xar_imageurl(',
            'xarMLS::translate(' => 'xar_translate(',
            'xarML(' => 'xar_translate(',
        ];
        $expression = str_replace(array_keys($mapping), array_values($mapping), $expression);

        $pattern = '~(xar\w+)::(\w+)\(([^)]*)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            $className = $matches[1];
            $methodName = $matches[2];
            $args = $matches[3];
            if ($className == 'xarUser' && $methodName == 'getVar') {
                if (!str_contains($args, ',')) {
                    return 'xar_uservar(' . $this->replaceVariable($args) . ')';
                }
                [$name, $userId] = explode(',', $args);
                $name = trim($name);
                $userId = trim($userId);
                if ($name == "'name'") {
                    return 'xar_username(' . $this->replaceVariable($userId) . ')';
                }
                return 'xar_username(' . $this->replaceVariable($userId) . ', ' . $this->replaceVariable($name) . ')';
            }
            if ($className == 'xarLocale') {
                if ($methodName == 'getFormattedDate') {
                    [$format, $value] = explode(',', $args);
                    $format = trim($format);
                    $value = trim($value);
                    return 'xar_localedate(' . $this->replaceVariable($value) . ', ' . $this->replaceVariable($format) . ", '')";
                }
                if ($methodName == 'getFormattedTime') {
                    [$format, $value] = explode(',', $args);
                    $format = trim($format);
                    $value = trim($value);
                    return 'xar_localedate(' . $this->replaceVariable($value) . ", '', " . $this->replaceVariable($format) . ')';
                }
            }
            return "xar_coremethod('{$className}', '{$methodName}', [" . $this->replaceVariable($args) . '])';
        }, $expression);

        $pattern = '~(!?)empty\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            if ($matches[1]) {
                return $this->replaceVariable($matches[2]);
            }
            return 'not ' . $this->replaceVariable($matches[2]);
        }, $expression);

        $pattern = '~(!?)is_array\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            if ($matches[1]) {
                return $this->replaceVariable($matches[2]) . ' is not iterable';
            }
            return $this->replaceVariable($matches[2]) . ' is iterable';
        }, $expression);

        $pattern = '~count\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . '|length';
        }, $expression);

        $pattern = '~(!?)in_array\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            [$needle, $haystack] = explode(',', $matches[2]);
            $needle = trim($needle);
            $haystack = trim($haystack);
            if ($matches[1]) {
                return $this->replaceVariable($needle) . ' not in ' . $this->replaceVariable($haystack);
            }
            return $this->replaceVariable($needle) . ' in ' . $this->replaceVariable($haystack);
        }, $expression);

        $pattern = '~(!?)str_contains\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            [$haystack, $needle] = explode(',', $matches[2]);
            $haystack = trim($haystack);
            $needle = trim($needle);
            if ($matches[1]) {
                return $this->replaceVariable($needle) . ' not in ' . $this->replaceVariable($haystack);
            }
            return $this->replaceVariable($needle) . ' in ' . $this->replaceVariable($haystack);
        }, $expression);

        $pattern = '~(!?)str_starts_with\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            [$haystack, $needle] = explode(',', $matches[2]);
            $haystack = trim($haystack);
            $needle = trim($needle);
            if ($matches[1]) {
                return 'not ' . $this->replaceVariable($haystack) . ' starts with ' . $this->replaceVariable($needle);
            }
            return $this->replaceVariable($haystack) . ' starts with ' . $this->replaceVariable($needle);
        }, $expression);

        /**
<             {% set places = item['marking']|split(constant('AND_OPERATOR', history)) %}
---
>             {% set places = explode(constant('AND_OPERATOR', history), item['marking']) %}
         */

        $pattern = '~explode\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            [$sep, $value] = explode(',', $matches[1]);
            return trim($this->replaceVariable($value)) . '|split(' . trim($this->replaceExpression($sep)) . ')';
        }, $expression);
 
        $pattern = '~ucwords\(str_replace\(([^)]+)\)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            [$from, $to, $var] = explode(',', $matches[1]);
            return trim($this->replaceVariable($var)) . '|replace({' . trim($from) . '^' . trim($to) . '})|title';
        }, $expression);

        return $expression;
    }

    public function replaceConstants($expression)
    {
        $pattern = '~(\$\w+)::([A-Z_]+)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            $object = $matches[1];
            $constant = $matches[2];
            return "constant('{$constant}', " . $this->replaceVariable($object) . ')';
        }, $expression);

        return $expression;
    }

    public function replaceVariable($variable)
    {
        // we get into trouble using : here if we call replaceVariable() later - use ^ as placeholder
        return str_replace(['$', ':', '->'], ['', '.', '.'], $variable);
    }
}
