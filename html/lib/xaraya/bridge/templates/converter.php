<?php

namespace Xaraya\Bridge\TemplateEngine;

/**
 * Experimental template converter from Blocklayout to Twig syntax
 *
 * Usage:
 * ```php
 * use Xaraya\Bridge\TemplateEngine\BlocklayoutToTwigConverter;
 *
 * // convert all *.xt templates from workflow module
 * $options = [
 *     'namespace' => 'workflow',
 * ];
 * $converter = new BlocklayoutToTwigConverter($options);
 * $sourcePath = dirname(__DIR__) . '/xartemplates';
 * $targetPath = dirname(__DIR__) . '/templates';
 * $converter->convertDir($sourcePath, $targetPath, '.xt');
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
    public string $basePath = '';
    public string $filePath = '';

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function convertDir(string $fromPath, string $toPath, string $suffix = '.xt', string $prefix = '', int $depth = 0)
    {
        if ($depth == 0) {
            $this->basePath = $toPath;
        }
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        $fileList = scandir($fromPath);
        foreach ($fileList as $fileName) {
            if (str_starts_with($fileName, '.')) {
                continue;
            }
            $source = $fromPath . '/' . $fileName;
            if (is_dir($source)) {
                $this->convertDir($source, $toPath . '/' . $fileName, $suffix, '', $depth + 1);
                continue;
            }
            if (!empty($prefix) && !str_starts_with($fileName, $prefix)) {
                continue;
            }
            if (!str_ends_with($fileName, $suffix)) {
                continue;
            }
            if ($depth == 0) {
                $fileName = $this->renameFile($fileName, $prefix);
            }
            $fileName = substr($fileName, 0, strlen($fileName) - strlen($suffix)) . '.html.twig';
            $target = $toPath . '/' . $fileName;
            echo "$source -> $target\n";
            $this->convertFile($source, $target);
        }
    }

    public function renameFile(string $fileName, string $prefix = '')
    {
        if (!empty($prefix) && str_starts_with($fileName, $prefix)) {
            $fileName = substr($fileName, strlen($prefix));
        } elseif (str_starts_with($fileName, 'admin-')) {
            // move admin-* templates to admin/ directory
            $fileName = 'admin/' . substr($fileName, strlen('admin-'));
        } elseif (str_starts_with($fileName, 'user-')) {
            // default user-* templates
            $fileName = substr($fileName, strlen('user-'));
        }
        return $fileName;
    }

    public function convertFile(string $fromPath, string $toPath)
    {
        $this->filePath = $toPath;
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
        $this->replaceTemplateTag();
        $this->replaceIfTag();
        $this->replaceForEachTag();
        $this->replaceSetTag();
        $this->replaceVarTag();
        $this->replaceStyleTag();
        $this->replaceImageTag();
        $this->replaceButtonTag();
        $this->replaceCommentTag();
        $this->replaceSecurityTag();
        $this->addHeader();
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

    public function addHeader()
    {
        if (empty($this->basePath) || empty($this->filePath) || !str_starts_with($this->filePath, $this->basePath)) {
            return;
        }
        $namespace = $this->getNamespace();
        $fileName = substr($this->filePath, strlen($this->basePath) + 1);
        if (!empty($namespace)) {
            $name = '@' . $namespace . '/' . $fileName;
            $base = '@' . $namespace . '/base.html.twig';
        } else {
            $name = $fileName;
            $base = 'base.html.twig';
        }
        $block = str_replace('-', '_', basename($fileName, '.html.twig'));
        if (!str_contains($fileName, '/')) {
            $this->content = '{# ' . $name . ' #}' . "\n\n" .
                '{% extends \'' . $base . '\' %}' . "\n\n" .
                '{% block modulespace %}' . "\n" .
                $this->content .
                '{% endblock %}';
        } else {
            $this->content = '{# ' . $name . ' #}' . "\n\n" .
                '{% block ' . $block . ' %}' . "\n" .
                $this->content .
                '{% endblock %}';
        }
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
    public function replaceTemplateTag()
    {
        $pattern = '~<xar:template file="([^"]+)"\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            if (!empty($prefix) && str_starts_with($matches[1], $prefix)) {
                $file = substr($matches[1], strlen($prefix));
            } else {
                $file = $matches[1];
            }
            $namespace = $this->getNamespace();
            if (!str_ends_with($namespace, '/includes')) {
                $namespace .= '/includes';
            }
            if (!empty($namespace)) {
                return '{{ include(\'@' . $namespace . '/' . $file . '.html.twig\') }}';
            }
            return '{{ include(\'' . $file . '.html.twig\') }}';
        }, $this->content);

        $pattern = '~<xar:template ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            if (empty($attrib['file'])) {
                return $matches[0];
            }
            if (!empty($attrib['type']) && $attrib['type'] !== 'module') {
                return $matches[0];
            }
            if (!empty($prefix) && str_starts_with($attrib['file'], $prefix)) {
                $file = substr($attrib['file'], strlen($prefix));
            } else {
                $file = $attrib['file'];
            }
            $namespace = $this->getNamespace();
            if (!empty($attrib['module']) && $attrib['module'] !== $namespace) {
                if (str_contains($attrib['module'], '$')) {
                    // @todo handle variable include
                    return $matches[0];
                }
                $namespace = $attrib['module'];
            }
            if (!str_ends_with($namespace, '/includes')) {
                $namespace .= '/includes';
            }
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
    public function replaceIfTag()
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
    public function replaceForEachTag()
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
    public function replaceSetTag()
    {
        $pattern = '~<xar:set name="([^"]+)">([^<]+)</xar:set>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $expression = trim($matches[2], '#');
            return '{% set ' . $matches[1] . ' = ' . $this->replaceExpression($expression) . ' %}';
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
    public function replaceVarTag()
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
     * Replace style
     * <xar:style scope="module" module="base" file="tabs"/>
     * @todo replace array with fixed order of params
     */
    public function replaceStyleTag()
    {
        $pattern = '~<xar:style ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_style(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);
    }

    /**
     * Replace image
     * <xar:img scope="theme" file="icons/info.png" class="xar-icon" alt="info"/>
     * @todo replace array with fixed order of params?
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
     * @todo replace array with fixed order of params?
     */
    public function replaceButtonTag()
    {
        // we strip spaces before & after for button?
        $pattern = '~<xar:button ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{- xar_button(' . $this->replaceAttributes($matches[1]) . ') -}}';
        }, $this->content);
    }

    /**
     * Replace comments
     * <xar:comment>...</xar:comment>
     */
    public function replaceCommentTag()
    {
        $pattern = '~<xar:comment>(.+?)</xar:comment>~i';
        $replace = '{# $1 #}';
        $this->content = preg_replace($pattern, $replace, $this->content);

        $this->content = str_replace(['<!--', '-->'], ['{# <!--', '--> #}'], $this->content);
    }

    /**
     * Replace comments
     * <xar:sec mask="..." catch="false">
     * <xar:else/> - handled by replaceIf()
     * </xar:sec>
     */
    public function replaceSecurityTag()
    {
        $pattern = '~<xar:sec mask="([^"]+)" catch="false">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $mask = $matches[1];
            if (str_contains($mask, '$')) {
                return '{% if xar_security_check(' . $this->replaceVariable($mask) . ') }}';
            }
            return '{% if xar_security_check(\'' . $mask . '\') %}';
        }, $this->content);

        $pattern = '~</xar:sec>~i';
        $replace = '{% endif %}';
        $this->content = preg_replace($pattern, $replace, $this->content);
    }

    /**
     * Replace workflow actions
     * <xar:workflow-actions name="actions" config="$config" item="$item" title="$item['marking']" template="$item['marking']"/>
     * @todo replace array with fixed order of params?
     */
    public function replaceWorkflowActions()
    {
        $pattern = '~<xar:workflow-actions ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_workflow_actions(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);
    }

    public function parseAttributes($attributes)
    {
        $attrib = [];
        $matches = [];
        preg_match_all('~(\w+)\s*=\s*"([^"]+)"~', $attributes, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name = $match[1];
            $value = $match[2];
            $value = trim($value, '#');
            $attrib[$name] = $value;
        }
        return $attrib;
    }

    public function replaceAttributes($attributes)
    {
        $attrib = $this->parseAttributes($attributes);
        $parts = [];
        foreach ($attrib as $name => $value) {
            if (str_contains($value, '$')) {
                $parts[] = $name . ': ' . $this->replaceVariable($value);
            } else {
                $parts[] = $name . ': "' . $value . '"';
            }
        }
        return '{' . implode(', ', $parts) . '}';
    }

    public function replaceCondition($condition)
    {
        $condition = $this->replaceFunctions($condition);
        $condition = $this->replaceConstants($condition);
        $condition = $this->replaceVariable($condition);
        $condition = str_replace(['!', '^'], ['not ', ':'], $condition);
        return str_replace([' eq ', ' ne ', ' gt ', ' lt ', ' ge ', ' le ', ' AND ', ' OR '], [' == ', ' != ', ' > ', ' < ', ' >= ', ' <= ', ' and ', ' or '], $condition);
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
        if (!str_contains($expression, '=>')) {
            return $expression;
        }
        // not matching correctly if last item is array
        $pattern = '~\[([^]]+)\]~i';
        $fixme = false;
        $expression = preg_replace_callback($pattern, function ($matches) use (&$fixme) {
            if (!str_contains($matches[1], '=>')) {
                return $matches[0];
            }
            $pieces = explode(',', $matches[1]);
            $parts = [];
            foreach ($pieces as $piece) {
                [$name, $value] = explode('=>', $piece);
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
        // @todo handle arrays as 4th argument in functions better
        $mapping = [
            'xarMod::apiFunc(' => 'xar_apifunc(',
            'xarServer::getModuleURL(' => 'xar_moduleurl(',
            'xarController::URL(' => 'xar_moduleurl(',
            'xarServer::getObjectURL(' => 'xar_objecturl(',
            'xarTpl::getImage(' => 'xar_imageurl(',
            'xarTpl::getFile(' => 'xar_fileurl(',
            'xarMLS::translate(' => 'xar_translate(',
            'xarML(' => 'xar_translate(',
        ];
        $expression = str_replace(array_keys($mapping), array_values($mapping), $expression);

        $pattern = '~xarUser::getVar\(([^)]*)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            $args = $matches[1];
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
        }, $expression);

        $pattern = '~xarLocale::(\w+)\(([^)]*)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            $className = 'xarLocale';
            $methodName = $matches[1];
            $args = $matches[2];
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
            return "xar_coremethod('{$className}', '{$methodName}', [" . $this->replaceVariable($args) . '])';
        }, $expression);

        // @todo placeholder until corresponding functions have been added
        $pattern = '~(xar\w+)::(\w+)\(([^)]*)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            $className = $matches[1];
            $methodName = $matches[2];
            $args = $matches[3];
            return "xar_coremethod('{$className}', '{$methodName}', [" . $this->replaceVariable($args) . '])';
        }, $expression);

        $pattern = '~(!?)empty\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            if ($matches[1]) {
                return $this->replaceVariable($matches[2]);
            }
            return 'not ' . $this->replaceVariable($matches[2]);
        }, $expression);

        $pattern = '~(!?)isset\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            if ($matches[1]) {
                return $this->replaceVariable($matches[2]) . ' is null';
            }
            return $this->replaceVariable($matches[2]) . ' is not null';
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
