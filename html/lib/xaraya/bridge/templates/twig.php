<?php
/**
 * Twig bridge to use Twig template engine for output in Xaraya
 *
 * Requirement:
 * ```shell
 * $ composer require twig/twig
 * ```
 *
 * Usage:
 * ```php
 * use Xaraya\Bridge\TemplateEngine\TwigBridge;
 *
 * // add paths for Twig filesystem loader (with namespace)
 * // {{ include('@workflow/includes/trackeritem.html.twig') }}
 * $paths = [
 *     'code/modules/workflow/templates' => 'workflow',
 * ];
 * // override default options for Twig environment
 * $options = [
 *     //'cache' => sys::varpath() . '/cache/templates',
 *     //'debug' => false,
 * ];
 * // get $context from GUI/API function call or DataObject
 *
 * $twigbridge = new TwigBridge($paths, $options, $context);
 * $twig = $twigbridge->getEnvironment();
 *
 * $data = [];
 * // render twig template with data
 * $template = $twig->load('@workflow/test.html.twig');
 * return $template->render($data);
 * // or render individual block defined in the template
 * //return $template->renderBlock('content', $data);
 * ```
 *
 */

namespace Xaraya\Bridge\TemplateEngine;

use Twig\Environment;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader;
use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use Xaraya\Context\Context;
use xarLocale;
use xarMLS;
use xarMod;
use xarServer;
use xarTpl;
use xarUser;
use sys;

sys::import('xaraya.traits.contexttrait');
sys::import("xaraya.context.context");

/**
 * Use Twig template engine to generate output in Xaraya
 *
 * Xaraya Extensions:
 * ```twig
 * {% set info = xar_apifunc(modName, modType, funcName, params) %}
 * {% set link = xar_moduleurl(modName, modType, funcName, params) %}
 * {{ xar_objecturl(objectName, methodName, params) }}
 * {{ xar_username(userId) }} or {% set email = xar_username(userId, 'email') %}
 * {{ xar_uservar('id') }}
 * {{ xar_translate(text) }} or {{ xar_translate(text, arg1, arg2, ...) }}
 * {{ xar_localedate(timestamp) }}
 * {% set info = xar_coremethod(className, methodName, args) %}
 * {{- xar_image(...) -}}
 * {{- xar_button(...) -}}
 * ```
 * @uses \sys::autoload()
 */
class TwigBridge implements ContextInterface
{
    use ContextTrait;

    /** @var array<string, mixed> */
    private array $paths = [];
    /** @var array<string, mixed> */
    private array $options = [];
    private ?Environment $twig = null;
    private ?FilesystemLoader $loader = null;

    /**
     * @param array<string, mixed> $paths
     * @param array<string, mixed> $options
     */
    public function __construct(array $paths = [], array $options = [], ?Context $context = null)
    {
        $this->setPaths($paths);
        $this->setOptions($options);
        $this->setContext($context);
    }

    public function getPaths()
    {
        return $this->paths;
    }

    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = array_replace([
            'cache' => sys::varpath() . '/cache/templates',
        ], $options);
        return $this->options;
    }

    public function getLoader()
    {
        if (!isset($this->loader)) {
            $this->loader = new FilesystemLoader();
            if (!empty($this->paths)) {
                foreach ($this->paths as $path => $namespace) {
                    if (empty($namespace)) {
                        $namespace = FilesystemLoader::MAIN_NAMESPACE;
                    }
                    $this->loader->addPath($path, $namespace);
                }
            }
        }
        return $this->loader;
    }

    /**
     * Get the Twig environment
     */
    public function getEnvironment()
    {
        if (!isset($this->twig)) {
            $this->twig = new Environment($this->getLoader(), $this->getOptions());
            if (!empty($this->options['debug'])) {
                $this->twig->addExtension(new \Twig\Extension\DebugExtension());
            }
            $this->addXarayaExtensions();
        }
        return $this->twig;
    }

    public function addXarayaExtensions()
    {
        // add context as global variable - @todo do we want this here?
        $this->twig->addGlobal('context', $this->getContext());

        $this->addXarayaFunctions();
        $this->addBlocklayoutTags();

        return $this->twig;
    }

    public function addXarayaFunctions()
    {
        $context = $this->getContext();

        $apiFunc = new TwigFunction('xar_apifunc', function ($modName, $modType = 'user', $funcName = 'main', $args = []) use ($context) {
            // use current context
            return xarMod::apiFunc($modName, $modType, $funcName, $args, $context);
        });
        $this->twig->addFunction($apiFunc);

        $moduleURL = new TwigFunction('xar_moduleurl', function ($modName, $modType = 'user', $funcName = 'main', $args = []) {
            // avoid double-encoding URLs
            return xarServer::getModuleURL($modName, $modType, $funcName, $args, false);
        });
        $this->twig->addFunction($moduleURL);

        $objectURL = new TwigFunction('xar_objecturl', function ($objectName, $methodName = 'view', $args = []) {
            // avoid double-encoding URLs
            return xarServer::getObjectURL($objectName, $methodName, $args, false);
        });
        $this->twig->addFunction($objectURL);

        // we need to mark this as safe for html
        $imageURL = new TwigFunction('xar_imageurl', function ($fileName, $scope = null, $package = null) {
            // @todo avoid double-encoding URLs
            return xarTpl::getImage($fileName, $scope, $package);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($imageURL);

        $userName = new TwigFunction('xar_username', function ($userId, $name = 'name') {
            return xarUser::getVar($name, $userId);
        });
        $this->twig->addFunction($userName);

        $userVar = new TwigFunction('xar_uservar', function ($name = 'id') {
            return xarUser::getVar($name);
        });
        $this->twig->addFunction($userVar);

        $translate = new TwigFunction('xar_translate', function ($rawstring, ...$args) {
            return xarMLS::translate($rawstring, ...$args);
        });
        $this->twig->addFunction($translate);

        $localeDate = new TwigFunction('xar_localedate', function ($timestamp, $dateFormat = 'medium', $timeFormat = 'short') {
            $date = '';
            if (!empty($dateFormat)) {
                $date .= xarLocale::getFormattedDate($dateFormat, $timestamp) . ' ';
            }
            if (!empty($timeFormat)) {
                $date .= xarLocale::getFormattedTime($timeFormat, $timestamp);
            }
            return $date;
        });
        $this->twig->addFunction($localeDate);

        // {% set infolink = attribute('xarServer', 'getObjectURL', ['workflow_tracker', 'display', {'itemid': item['id']}]) %}
        // @todo placeholder until corresponding functions have been added
        $function = new TwigFunction('xar_coremethod', function ($class, $method, $params) {
            return $class::$method(...$params);
        });
        $this->twig->addFunction($function);

        return $this->twig;
    }

    public function addBlocklayoutTags()
    {
        $context = $this->getContext();

        // <xar:img scope="theme" file="icons/info.png" class="xar-icon" alt="info"/>
        // we need to mark this as safe for html
        $image = new TwigFunction('xar_image', function ($args = []) use ($context) {
            $link = xarMod::apiFunc('themes', 'user', 'getimage', $args, $context);
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
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($image);

        // <xar:button type="link" name="$name" target="$runlink" label="$label"/>
        $button = new TwigFunction('xar_button', function ($args = []) use ($context) {
            return xarTpl::module('themes', 'user', 'buttontag', $args);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($button);

        // <xar:workflow-actions name="actions" config="$config" item="$item" title="$item['marking']" template="$item['marking']"/>
        $workflow = new TwigFunction('xar_workflow_actions', function ($args = []) use ($context) {
            return xarMod::apiFunc('workflow', 'user', 'showactions', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($workflow);

        return $this->twig;
    }
}
