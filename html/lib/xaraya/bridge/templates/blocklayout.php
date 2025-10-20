<?php

/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use Exception;

/**
 * Blocklayout Tags
 */
class BlocklayoutTagExtension extends XarayaTwigExtension
{
    public function getFilters()
    {
        return [
        ];
    }

    public function getTests()
    {
        return [
        ];
    }

    public function getFunctions()
    {
        return [
            // <xar:blocklayout version="2.0" content="text/html" xmlns:xar="http://xaraya.com/2004/blocklayout" dtd="xhtml1-strict">
            new TwigFunction('xar_twig_header', $this->xar_twig_header(...)),
            new TwigFunction('xar_twig_block', $this->xar_twig_block(...), [
                'needs_environment' => true,
                'needs_context' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('xar_blockgroup', $this->xar_blockgroup(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_block', $this->xar_block(...), ['is_safe' => ['html']]),
            // <xar:pager startnum="$object->startnum" itemsperpage="$object->numitems" total="$object->startnum" urltemplate="$object->pagerurl" template="multipageprev"/>
            new TwigFunction('xar_pager', $this->xar_pager(...), ['is_safe' => ['html']]),
            // <xar:javascript scope="theme" filename="checkall.js" position="head"/>
            new TwigFunction('xar_javascript', $this->xar_javascript(...)),
            // <xar:place-javascript position="body"/>
            new TwigFunction('xar_place_javascript', $this->xar_place_javascript(...), ['is_safe' => ['html']]),
            // <xar:style scope="module" module="base" file="tabs"/>
            // @todo replace array with fixed order of params
            new TwigFunction('xar_style', $this->xar_style(...)),
            // <xar:place-css />
            new TwigFunction('xar_place_css', $this->xar_place_css(...), ['is_safe' => ['html']]),
            new TwigFunction('xar_meta', $this->xar_meta(...)),
            // <xar:place-meta/>
            new TwigFunction('xar_place_meta', $this->xar_place_meta(...), ['is_safe' => ['html']]),
            // <xar:img scope="theme" file="icons/info.png" class="xar-icon" alt="info"/>
            // @todo replace array with fixed order of params?
            // we need to mark this as safe for html
            new TwigFunction('xar_image', $this->xar_image(...), ['is_safe' => ['html']]),
            // <xar:button type="link" name="$name" target="$runlink" label="$label"/>
            // @todo replace array with fixed order of params?
            new TwigFunction('xar_button', $this->xar_button(...), ['is_safe' => ['html']]),
            // @todo do we even want this with autoescape enabled?
            new TwigFunction('xar_prep_display', $this->xar_prep_display(...), ['is_safe' => ['html']]),
            // @todo do we even want this with autoescape enabled?
            new TwigFunction('xar_prep_html', $this->xar_prep_html(...), ['is_safe' => ['html']]),
            // @todo do we even want this with autoescape enabled?
            new TwigFunction('xar_prep_email', $this->xar_prep_email(...), ['is_safe' => ['html']]),
        ];
    }

    public function xar_twig_header($contentType, $charSet = null)
    {
        if (!headers_sent()) {
            // @todo use current context
            if (empty($charSet)) {
                $locale = $this->mls()->getCurrentLocale();
                $charSet = $this->mls()->getCharsetFromLocale($locale);
            }
            header("Content-Type: " . $contentType . "; charset=" . $charSet);
        }
        // Note: doctype is already converted once
        return '';
    }

    /**
     * Render twig template block or template from within another template
     * {{ xar_twig_block('@theme/common/includes/user-message.html.twig', 'user_message', {'message': 'Hello world!'}) }}
     * See https://twig.symfony.com/doc/3.x/advanced.html#context-aware-filters
     */
    public function xar_twig_block(\Twig\Environment $env, $context, $templateName, $blockName = null, $tplData = [])
    {
        try {
            $template = $env->load($templateName);
            $context = array_replace($context, $tplData);
            if (empty($blockName)) {
                return $template->render($context);
            }
            return $template->renderBlock($blockName, $context);
        } catch (Exception $e) {
            return 'Error renderBlock() for template ' . $templateName . ' block ' . $blockName . ': ' . $e->getMessage();
        }
    }

    public function xar_blockgroup($groupname, $template = null)
    {
        // use current context
        return $this->block()->renderGroup($groupname, $template);
    }

    public function xar_block($args = [])
    {
        $fixed = ['instance', 'module', 'type', 'name', 'title', 'template', 'state', 'tplmodule'];
        $allowed = array_flip($fixed);
        $params = array_intersect_key($args, $allowed);
        $params['content'] = array_keys(array_diff_key($args, $allowed));
        if (!empty($params['content'])) {
            throw new Exception('Content in block tag: ' . var_export($params, true));
        }
        // use current context
        return $this->block()->renderBlock($params);
    }

    public function xar_pager($args = [])
    {
        //$args['context'] ??= $this->context;
        return $this->mod()->apiFunc('base', 'user', 'pager', $args);
    }

    public function xar_javascript($args = [])
    {
        //$args['context'] ??= $this->context;
        $this->mod()->apiFunc('themes', 'user', 'registerjs', $args);
        return '';
    }

    public function xar_place_javascript($args = [])
    {
        $position = $args['position'];
        $type = $args['type'] ?? '';
        $params = ['position' => $position, 'type' => $type];
        $params['context'] = $this->context;
        return trim($this->mod()->apiFunc('themes', 'user', 'renderjs', $params));
    }

    public function xar_style($args = [])
    {
        //$args['context'] ??= $this->context;
        $this->mod()->apiFunc('themes', 'user', 'register', $args);
        return '';
    }

    public function xar_place_css($args = [])
    {
        $params = ['method' => 'render', 'base' => 'theme'];
        $params['context'] = $this->context;
        return $this->mod()->apiFunc('themes', 'user', 'deliver', $params);
    }

    public function xar_meta($args = [])
    {
        $this->mod()->apiFunc('themes', 'user', 'registermeta', $args);
        return '';
    }

    public function xar_place_meta($args = [])
    {
        $args['context'] ??= $this->context;
        return trim($this->mod()->apiFunc('themes', 'user', 'rendermeta', $args));

    }

    public function xar_image($args = [])
    {
        $link = $this->mod()->apiFunc('themes', 'user', 'getimage', $args);
        $html = '<img src="' . $link . '"';
        foreach ($args as $name => $value) {
            if (in_array($name, ['src', 'file', 'scope'])) {
                continue;
            }
            if (!preg_match('/^\w+$/', $name)) {
                continue;
            }
            $html .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '/>';
        return $html;
    }

    public function xar_button($args = [])
    {
        $args['context'] ??= $this->context;
        return $this->tpl()->module('themes', 'user', 'buttontag', $args);
    }

    public function xar_prep_display(...$args)
    {
        return $this->var()->prep(...$args);
    }

    public function xar_prep_html(...$args)
    {
        return $this->var()->prepHTML(...$args);
    }

    public function xar_prep_email(...$args)
    {
        return $this->var()->prepEmail(...$args);
    }
}
