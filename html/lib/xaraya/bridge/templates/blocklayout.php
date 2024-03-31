<?php
/**
 * Twig extension to use Twig template engine for output in Xaraya
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\TwigFunction;
use xarBlock;
use xarConfigVars;
use xarController;
use xarMLS;
use xarMod;
use xarModVars;
use xarSecurity;
use xarSession;
use xarTpl;
use xarUser;
use xarVar;
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
            new TwigFunction('xar_twig_header', [$this, 'xar_twig_header']),
            new TwigFunction('xar_blockgroup', [$this, 'xar_blockgroup'], ['is_safe' => ['html']]),
            new TwigFunction('xar_block', [$this, 'xar_block'], ['is_safe' => ['html']]),
            /**
            <xar:set name="checked">
                <xar:var scope="module" module="themes" name="var_dump"/>
            </xar:set>
            // @todo use context where relevant
            */
            new TwigFunction('xar_var', [$this, 'xar_var']),
            // <xar:pager startnum="$object->startnum" itemsperpage="$object->numitems" total="$object->startnum" urltemplate="$object->pagerurl" template="multipageprev"/>
            new TwigFunction('xar_pager', [$this, 'xar_pager'], ['is_safe' => ['html']]),
            // <xar:javascript scope="theme" filename="checkall.js" position="head"/>
            new TwigFunction('xar_javascript', [$this, 'xar_javascript']),
            // <xar:place-javascript position="body"/>
            new TwigFunction('xar_place_javascript', [$this, 'xar_place_javascript'], ['is_safe' => ['html']]),
            // <xar:style scope="module" module="base" file="tabs"/>
            // @todo replace array with fixed order of params
            new TwigFunction('xar_style', [$this, 'xar_style']),
            // <xar:place-css />
            new TwigFunction('xar_place_css', [$this, 'xar_place_css'], ['is_safe' => ['html']]),
            // @todo <xar:meta type="name" value="keywords" content="$keywords" lang="en" dir="ltr" append="1"/>
            // <xar:place-meta/>
            new TwigFunction('xar_place_meta', [$this, 'xar_place_meta'], ['is_safe' => ['html']]),
            // <xar:img scope="theme" file="icons/info.png" class="xar-icon" alt="info"/>
            // @todo replace array with fixed order of params?
            // we need to mark this as safe for html
            new TwigFunction('xar_image', [$this, 'xar_image'], ['is_safe' => ['html']]),
            // <xar:button type="link" name="$name" target="$runlink" label="$label"/>
            // @todo replace array with fixed order of params?
            new TwigFunction('xar_button', [$this, 'xar_button'], ['is_safe' => ['html']]),
            // @todo do we even want this with autoescape enabled?
            new TwigFunction('xar_prep_display', [$this, 'xar_prep_display'], ['is_safe' => ['html']]),
            // @todo do we even want this with autoescape enabled?
            new TwigFunction('xar_prep_html', [$this, 'xar_prep_html'], ['is_safe' => ['html']]),
            // <xar:sec mask="..." catch="false">
            new TwigFunction('xar_security_check', [$this, 'xar_security_check']),
        ];
    }

    public function xar_twig_header($contentType, $charSet = null)
    {
        if (!headers_sent()) {
            // @todo use current context
            if (empty($charSet)) {
                $locale = xarMLS::getCurrentLocale();
                $charSet = xarMLS::getCharsetFromLocale($locale);
            }
            header("Content-Type: " . $contentType . "; charset=" . $charSet);
        }
        // Note: doctype is already converted once
        return '';
    }

    public function xar_blockgroup($groupname, $template = null)
    {
        // use current context
        return xarBlock::renderGroup($groupname, $template, $this->context);
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
        return xarBlock::renderBlock($params, $this->context);
    }

    /**
    <xar:set name="checked">
        <xar:var scope="module" module="themes" name="var_dump"/>
    </xar:set>
    // @todo use context where relevant
    */
    public function xar_var($args = [])
    {
        // @todo not sure how this is supposed to work
        $args['scope'] ??= 'local';
        $result = match ($args['scope']) {
            'local' => $args['name'],
            'module' => xarModVars::get($args['module'], $args['name']),
            'user' => xarUser::getVar($args['name'], $args['user'] ?? null),
            'config' => xarConfigVars::get(null, $args['name']),
            'session' => xarSession::getVar($args['name']),
            'request' => xarController::getVar($args['name']),
            default => 'Unknown scope ' . $args['scope'],
        };
        if (!empty($args['prep'])) {
            return xarVar::prepForDisplay($result);
        }
        return $result;
    }

    public function xar_pager($args = [])
    {
        //$args['context'] ??= $this->context;
        return xarMod::apiFunc('base', 'user', 'pager', $args, $this->context);
    }

    public function xar_javascript($args = [])
    {
        //$args['context'] ??= $this->context;
        xarMod::apiFunc('themes', 'user', 'registerjs', $args, $this->context);
        return '';
    }

    public function xar_place_javascript($args = [])
    {
        $position = $args['position'];
        $type = $args['type'] ?? '';
        $params = ['position' => $position, 'type' => $type];
        $params['context'] = $this->context;
        return trim(xarMod::apiFunc('themes', 'user', 'renderjs', $params, $this->context));
    }

    public function xar_style($args = [])
    {
        //$args['context'] ??= $this->context;
        xarMod::apiFunc('themes', 'user', 'register', $args, $this->context);
        return '';
    }

    public function xar_place_css($args = [])
    {
        $params = ['method' => 'render', 'base' => 'theme'];
        $params['context'] = $this->context;
        return xarMod::apiFunc('themes', 'user', 'deliver', $params);
    }

    public function xar_place_meta($args = [])
    {
        $args['context'] ??= $this->context;
        return trim(xarMod::apiFunc('themes', 'user', 'rendermeta', $args, $this->context));

    }

    public function xar_image($args = [])
    {
        $link = xarMod::apiFunc('themes', 'user', 'getimage', $args, $this->context);
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
        return xarTpl::module('themes', 'user', 'buttontag', $args);
    }

    public function xar_prep_display(...$args)
    {
        return xarVar::prepForDisplay(...$args);
    }

    public function xar_prep_html(...$args)
    {
        return xarVar::prepHTMLDisplay(...$args);
    }

    public function xar_security_check($mask, $catch = 0)
    {
        return xarSecurity::check($mask, $catch);
    }
}
