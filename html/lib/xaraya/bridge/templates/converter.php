<?php

namespace Xaraya\Bridge\TemplateEngine;

use Exception;

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
    /** @var array<string, mixed> */
    public array $files = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getNamespace()
    {
        return $this->options['namespace'] ?? '';
    }

    public function convertDir(string $fromPath, string $toPath, string $suffix = '.xt', string $prefix = '', int $depth = 0)
    {
        if (!is_dir($toPath)) {
            mkdir($toPath);
        }
        if ($depth == 0) {
            $this->basePath = $toPath;
            if (empty($prefix) && $fromPath != $toPath && !is_dir($toPath . '/admin')) {
                mkdir($toPath . '/admin');
            }
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
        $this->files[] = $toPath;
    }

    public function convert(string $content)
    {
        $this->content = $content;
        return $this->content;
    }

    public function replaceVariable($variable)
    {
        return str_replace(['$', ':', '->'], ['', '.', '.'], $variable);
    }

    public function buildTwigParam($param)
    {
        if (!str_contains($param, '$')) {
            return '"' . $param . '"';
        }
        [$pre, $post] = explode('$', $param);
        if (empty($pre)) {
            return $this->replaceVariable($param);
        }
        return '"' . $pre . '" ~ ' . $this->replaceVariable($post);
    }

    public function buildTwigArray($params)
    {
        $parts = [];
        foreach ($params as $name => $value) {
            if (str_contains($name, '$')) {
                [$pre, $post] = explode('$', $name);
                if (!empty($pre)) {
                    throw new Exception('Composite array name not supported: ' . $name . ' in ' . var_export($params, true));
                }
                $name = '(' . $this->replaceVariable($name) . ')';
            }
            $parts[] = $name . ': ' . $this->buildTwigParam($value);
        }
        return '{' . implode(', ', $parts) . '}';
    }

    public function validate($twig)
    {
        $namespace = $this->getNamespace();
        $issues = [];
        echo "Issues by file:\n";
        foreach ($this->files as $path) {
            try {
                $code = file_get_contents($path);
                $name = substr($path, strlen($this->basePath) + 1);
                if (!empty($namespace)) {
                    $name = '@' . $namespace . '/' . $name;
                }
                $twig->parse($twig->tokenize(new \Twig\Source($code, $name, $path)));
        
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
        echo "Top issues by count:\n";
        uasort($issues, function ($a, $b) {
            return count($b) <=> count($a);
        });
        foreach ($issues as $message => $files) {
            echo "Syntax error: $message (" . count($files) . "):\n  ";
            echo implode("\n  ", $files);
            echo "\n";
        }
    }
}

class BlocklayoutToTwigConverter extends TwigConverter
{
    public function convert(string $content)
    {
        $this->content = $content;
        if ($this->isPageTemplate()) {
            $this->handlePageTemplate();
        } else {
            $this->removeHeader();
            $this->removeFooter();
        }
        $this->replaceBlocklayoutTags();
        $this->replaceDynamicDataTags();
        $this->replaceWorkflowTags();
        if (! $this->isPageTemplate()) {
            $this->addHeader();
        }
        return $this->content;
    }

    public function isPageTemplate()
    {
        return !str_contains($this->basePath, '/templates') && str_contains($this->filePath, '/pages/');
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
        // skip adding header if we're in the wrong place
        if (empty($this->basePath) || empty($this->filePath) || !str_starts_with($this->filePath, $this->basePath)) {
            return;
        }
        // skip adding header for theme pages
        if ($this->isPageTemplate()) {
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

    public function handlePageTemplate()
    {
        // remove other headers for theme pages
        $pattern = '~^<\?xml version="1.0" encoding="utf-8"\?>\s*~i';
        $replace = '';
        $this->content = preg_replace($pattern, $replace, $this->content);

        $pattern = '~<\?xar type="\w+"\s*\?>\s*~i';
        $replace = '';
        $this->content = preg_replace($pattern, $replace, $this->content);

        $pattern = '~<xar:blocklayout [^>]+>\s*~i';
        $replace = '';
        $this->content = preg_replace($pattern, $replace, $this->content);

        $pattern = '~<xar:module id="(\w+)"/>~i';
        $replace = '{% block $1 %}{{ _bl_mainModuleOutput }}{% endblock %}';
        $this->content = preg_replace($pattern, $replace, $this->content);

        // remove other footers for theme pages
        $pattern = '~</xar:blocklayout>\s*$~i';
        $replace = '';
        $this->content = preg_replace($pattern, $replace, $this->content);
    }

    public function replaceBlocklayoutTags()
    {
        $this->replaceBlockTag();
        $this->replaceModuleTag();
        $this->replaceTemplateTag();
        $this->replaceIfTag();
        $this->replaceForEachTag();
        $this->replaceStyleTag();
        $this->replaceImageTag();
        $this->replaceButtonTag();
        $this->replaceSetTag();
        $this->replaceVarTag();
        $this->replaceCommentTag();
        $this->replaceSecurityTag();
        $this->replaceMlTag();
        $this->replaceLoopTag();
        $this->replaceJavascriptTag();
        $this->replacePagerTag();
    }

    public function replaceModuleTag()
    {
        // <xar:module main="false" module="dynamicdata" type="user" func="filtertag" object="$object" fieldlist="name"/>
        $pattern = '~<xar:module ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            if (empty($attrib['module'])) {
                throw new Exception('Missing module in xar:module tag: ' . $matches[0]);
                //return $matches[0];
            }
            $attrib['type'] ??= 'user';
            $attrib['func'] ??= 'main';
            $params = $attrib;
            unset($params['module']);
            unset($params['type']);
            unset($params['func']);
            $module = $this->buildTwigParam($attrib['module']);
            $type = $this->buildTwigParam($attrib['type']);
            $func = $this->buildTwigParam($attrib['func']);
            $string = '{{ xar_guifunc(' . $module . ', ' . $type . ', ' . $func;
            if (!empty($params)) {
                $string .= ', ' . $this->buildTwigArray($params);
            }
            $string .= ') }}';
            return $string;
        }, $this->content);
    }

    public function replaceBlockTag()
    {
        // <xar:blockgroup name="header" id="header"/>
        $pattern = '~<xar:blockgroup ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            if (empty($attrib['name'])) {
                throw new Exception('Missing name in xar:blockgroup tag: ' . $matches[0]);
                //return $matches[0];
            }
            if (!empty($attrib['template'])) {
                throw new Exception('Unused template in xar:blockgroup tag: ' . $matches[0]);
                //return $matches[0];
            }
            $name = $this->buildTwigParam($attrib['name']);
            $string = '{{ xar_blockgroup(' . $name . ') }}';
            return $string;
        }, $this->content);

        // <xar:blockgroup name="header">...</xar:blockgroup>
        $pattern = '~<xar:blockgroup ([^>]+)\s*>(.+?)</xar:blockgroup>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            throw new Exception('Child nodes not supported in xar:blockgroup tag: ' . $matches[0]);
            //return $matches[0];
        }, $this->content);

        // <xar:block instance="$name"/>
        $pattern = '~<xar:block ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_block(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);
    }

    /**
     * Replace template file
     * <xar:template file="..."/>
     */
    public function replaceTemplateTag()
    {
        // <xar:template file="objectlist-$layout"/>
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
            if (str_contains($file, '$')) {
                [$pre, $post] = explode('$', $file);
                $file = $pre . '\' ~ ' . $this->replaceVariable($post) . ' ~ \'';
            }
            if (!empty($namespace)) {
                return '{{ include(\'@' . $namespace . '/' . $file . '.html.twig\') }}';
            }
            return '{{ include(\'' . $file . '.html.twig\') }}';
        }, $this->content);

        // <xar:template module="$tplmodule" file="display-$layout"/>
        $pattern = '~<xar:template ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            if (empty($attrib['file'])) {
                throw new Exception('Missing file in xar:template tag: ' . $matches[0]);
                //return $matches[0];
            }
            if (!empty($attrib['type']) && $attrib['type'] !== 'module') {
                throw new Exception('Wrong type in xar:template tag: ' . $matches[0]);
                //return $matches[0];
            }
            if (!empty($prefix) && str_starts_with($attrib['file'], $prefix)) {
                $file = substr($attrib['file'], strlen($prefix));
            } else {
                $file = $attrib['file'];
            }
            $namespace = $this->getNamespace();
            if (!empty($attrib['module']) && $attrib['module'] !== $namespace) {
                $namespace = $attrib['module'];
                if (str_contains($namespace, '$')) {
                    [$pre, $post] = explode('$', $namespace);
                    $namespace = $pre . '\' ~ ' . $this->replaceVariable($post) . ' ~ \'';
                }
            }
            if (!str_ends_with($namespace, '/includes')) {
                $namespace .= '/includes';
            }
            if (str_contains($file, '$')) {
                [$pre, $post] = explode('$', $file);
                $file = $pre . '\' ~ ' . $this->replaceVariable($post) . ' ~ \'';
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
     * <xar:foreach in="$..." key="$...">
     * <xar:continue/> - @todo there is no break or continue in Twig?
     * <xar:break /> - @todo there is no break or continue in Twig?
     * </xar:foreach>
     */
    public function replaceForEachTag()
    {
        $pattern = '~<xar:foreach in="([^"]+)"\s+value="([^"]+)">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% for ' . $this->replaceVariable($matches[2]) . ' in ' . $this->replaceVariable($matches[1]) . ' %}';
        }, $this->content);

        $pattern = '~<xar:foreach in="([^"]+)"\s+key="([^"]+)"\s+value="([^"]+)">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% for ' . $this->replaceVariable($matches[2]) . ', ' . $this->replaceVariable($matches[3]) . ' in ' . $this->replaceVariable($matches[1]) . ' %}';
        }, $this->content);

        $pattern = '~<xar:foreach in="([^"]+)" key="([^"]+)">~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{% for ' . $this->replaceVariable($matches[2]) . ' in ' . $this->replaceVariable($matches[1]) . '|keys %}';
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
        /**
        <xar:set name="checked">
            <xar:var scope="module" module="themes" name="var_dump"/>
        </xar:set>
         */

        $pattern = '~<xar:var ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            if (empty($attrib['name'])) {
                throw new Exception('Missing name for var tag: ' . $matches[0]);
            }
            // @todo not sure how this is supposed to work
            if (empty($attrib['scope']) || $this->replaceVariable($attrib['scope']) == 'local') {
                return '{{ ' . $this->replaceVariable($attrib['name']) . ' }}';
            }
            return '{{ xar_var(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

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
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ ' . $this->replaceVariable($matches[1]) . ' }}';
        }, $this->content);

        // @todo <xar:var name="SiteSlogan" scope="module" module="themes"/> in set context?

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
        // support multi-line comments too
        $pattern = '~<xar:comment([^>]*)>(.+?)</xar:comment>~is';
        $replace = '{# $1 $2 #}';
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

    public function replaceMlTag()
    {
        // @todo <xar:ml></xar:ml>
        /**
         <xar:ml>
            <xar:mlstring>
                Your account has been locked for #(1) minutes.
            </xar:mlstring>
            <xar:mlvar>#$lockouttime#</xar:mlvar>
        </xar:ml>
         */
    }

    public function replaceLoopTag()
    {
        // @todo <xar:loop name="$errors" key="$ix">
    }

    public function replaceForTag()
    {
        // @todo <xar:for start="$j=0" test="$j lt count($column_titles)" iter="$j++">
    }

    public function replaceJavascriptTag()
    {
        // <xar:javascript scope="theme" filename="checkall.js" position="head"/>
        $pattern = '~<xar:javascript ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_javascript(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        // <xar:place-javascript position="body"/>
        $pattern = '~<xar:place-javascript([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_place_javascript(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        // <xar:place-css />
        $pattern = '~<xar:place-css([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_place_css(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);
    }

    public function replacePagerTag()
    {
        // <xar:pager startnum="$object->startnum" itemsperpage="$object->numitems" total="$object->startnum" urltemplate="$object->pagerurl" template="multipageprev"/>
        $pattern = '~<xar:pager (.+?)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_pager(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);
    }

    /**
     * Replace dynamicdata tags
     * <xar:data-view object="$object" newlink=""/>
     * <xar:data-display object="$object"/>
     * <xar:data-label property="$properties[$name]"/>
     * <xar:data-output property="$properties[$name]" _itemid="$itemid" value="$fields[$name]"/>
     * @todo replace array with fixed order of params?
     */
    public function replaceDynamicDataTags()
    {
        $pattern = '~<xar:data-view ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_view(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        $pattern = '~<xar:data-display ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_display(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        $pattern = '~<xar:data-form ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_form(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        $pattern = '~<xar:data-filterform ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_filterform(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        // support multi-line data-label too
        $pattern = '~<xar:data-label (.+?)\s*/>~is';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_label(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        // support multi-line data-output too
        $pattern = '~<xar:data-output (.+?)\s*/>~is';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_output(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        // support multi-line data-input too
        $pattern = '~<xar:data-input (.+?)\s*/>~is';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_input(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        $pattern = '~<xar:data-filter ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            return '{{ xar_data_filter(' . $this->replaceAttributes($matches[1]) . ') }}';
        }, $this->content);

        $pattern = '~<xar:data-getitems ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            $properties = $this->replaceVariable($attrib['properties']);
            $values = $this->replaceVariable($attrib['values']);
            // @todo not sure this will help unless we change template too
            return '{% set tmp_dd_getitems = xar_data_getitems(' . $this->buildTwigArray($attrib) . ') %}' .
                '{% set ' . $properties . ' = tmp_dd_getitems.0 %}' .
                '{% set ' . $values . ' = tmp_dd_getitems.1 %}';
        }, $this->content);

        $pattern = '~<xar:data-getitem ([^>]+)\s*/>~i';
        $this->content = preg_replace_callback($pattern, function ($matches) {
            $attrib = $this->parseAttributes($matches[1]);
            $properties = $this->replaceVariable($attrib['properties']);
            // @todo not sure this will help unless we change template too
            return '{% set ' . $properties . ' = xar_data_getitem(' . $this->buildTwigArray($attrib) . ') %}';
        }, $this->content);
    }

    /**
     * Replace workflow tags
     * <xar:workflow-actions name="actions" config="$config" item="$item" title="$item['marking']" template="$item['marking']"/>
     * @todo replace array with fixed order of params?
     */
    public function replaceWorkflowTags()
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
        return $this->buildTwigArray($attrib);
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
        // if we already have an expression inside, e.g.
        // source <xar:set name="leftgroup"><xar:blockgroup name="left" id="left"/></xar:set>
        // became <xar:set name="leftgroup">{{ xar_blockgroup("left") }}</xar:set>
        if (str_starts_with($expression, '{{') and str_ends_with($expression, '}}')) {
            return substr($expression, 3, strlen($expression) - 6);
        }
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
        // @todo not matching correctly if last item is array
        $pattern = '~\[([^]]+)\]~i';
        $fixme = false;
        $expression = preg_replace_callback($pattern, function ($matches) use (&$fixme) {
            if (!str_contains($matches[1], '=>')) {
                return $matches[0];
            }
            $pieces = explode(',', $matches[1]);
            $parts = [];
            foreach ($pieces as $piece) {
                [$name, $value] = explode('=>', $piece . '=>');
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
            'xarServer::getCurrentURL(' => 'xar_currenturl(',
            'xarTpl::getImage(' => 'xar_imageurl(',
            'xarTpl::getFile(' => 'xar_fileurl(',
            'xarMLS::translate(' => 'xar_translate(',
            'xarML(' => 'xar_translate(',
        ];
        $expression = str_ireplace(array_keys($mapping), array_values($mapping), $expression);

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
            if (empty($args)) {
                return "xar_coremethod('{$className}', '{$methodName}')";
            }
            return "xar_coremethod('{$className}', '{$methodName}', [" . $this->replaceVariable($args) . '])';
        }, $expression);

        // @todo placeholder until corresponding functions have been added
        $pattern = '~\b(sys|xar\w+)::(\w+)\(([^)]*)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            $className = $matches[1];
            $methodName = $matches[2];
            $args = $matches[3];
            if (empty($args)) {
                return "xar_coremethod('{$className}', '{$methodName}')";
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

        $pattern = '~reset\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . '|first';
        }, $expression);

        $pattern = '~count\((\$[^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . '|length';
        }, $expression);

        $pattern = '~strlen\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . '|length';
        }, $expression);

        $pattern = '~trim\(([^,)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . '|trim';
        }, $expression);

        $pattern = '~trim\(([^,)]+),([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . '|trim(' . $this->replaceVariable($matches[1]) . ')';
        }, $expression);

        // @todo add some simple tests too
        $pattern = '~is_numeric\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . ' is numeric';
        }, $expression);

        $pattern = '~is_object\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return $this->replaceVariable($matches[1]) . ' is object';
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

        $pattern = '~implode\((\'[^\']+\')\s*,([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return trim($this->replaceExpression($matches[2])) . '|join(' . trim($this->replaceExpression($matches[1])) . ')';
        }, $expression);

        $pattern = '~array_keys\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            return trim($this->replaceVariable($matches[1])) . '|keys';
        }, $expression);

        $pattern = '~json_encode\(([^)]+)\)~';
        $expression = preg_replace_callback($pattern, function ($matches) {
            [$value, $flags] = explode(',', $matches[1]);
            if ($flags) {
                $flags = trim($flags);
                return trim($this->replaceVariable($value)) . '|json_encode(constant(\'' . $flags . '\'))'; 
            }
            return trim($this->replaceVariable($value)) . '|json_encode()'; 
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
}
