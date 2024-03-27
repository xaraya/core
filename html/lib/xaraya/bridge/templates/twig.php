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
use Twig\TwigTest;
use Twig\Loader\FilesystemLoader;
use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use Xaraya\Context\Context;
use DataObjectFactory;
use DataPropertyMaster;
use xarConst;
use xarLocale;
use xarMLS;
use xarMod;
use xarSecurity;
use xarServer;
use xarTpl;
use xarUser;
use xarVar;
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
 * {% set link = xar_imageurl(fileName, scope, package) %}
 * {% set link = xar_fileurl(fileName, scope, package) %}
 * {{ xar_username(userId) }} or {% set email = xar_username(userId, 'email') %}
 * {{ xar_uservar('id') }}
 * {{ xar_translate(text) }} or {{ xar_translate(text, arg1, arg2, ...) }}
 * {{ xar_localedate(timestamp) }}
 * {% set info = xar_coremethod(className, methodName, args) %}
 * {% if xar_security_check('AdminBase') %} ... {% else %} ... {% endif %}
 * {{ xar_style(...) }}
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
            'cache' => sys::varpath() . xarConst::TPL_CACHEDIR,
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
        $this->addXarayaTags();

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
            // avoid double-encoding URLs
            return xarTpl::getImage($fileName, $scope, $package);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($imageURL);

        $fileURL = new TwigFunction('xar_fileurl', function ($fileName, $scope = null, $package = null) {
            // avoid double-encoding URLs
            return xarTpl::getFile($fileName, $scope, $package);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($fileURL);

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

        // @todo add some simple tests too
        $numeric = new TwigTest('numeric', function ($value) {
            return is_numeric($value);
        });
        $this->twig->addTest($numeric);

        return $this->twig;
    }

    public function addXarayaTags()
    {
        // @todo make configurable by module
        $this->addBlocklayoutTags();
        $this->addDynamicDataTags();
        $this->addWorkflowTags();

        return $this->twig;
    }

    public function addBlocklayoutTags()
    {
        $context = $this->getContext();

        // <xar:style scope="module" module="base" file="tabs"/>
        // @todo replace array with fixed order of params
        $style = new TwigFunction('xar_style', function ($args = []) use ($context) {
            xarMod::apiFunc('themes', 'user', 'register', $args, $context);
            return '';
        });
        $this->twig->addFunction($style);

        // <xar:img scope="theme" file="icons/info.png" class="xar-icon" alt="info"/>
        // @todo replace array with fixed order of params?
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
        // @todo replace array with fixed order of params?
        $button = new TwigFunction('xar_button', function ($args = []) use ($context) {
            $args['context'] ??= $context;
            return xarTpl::module('themes', 'user', 'buttontag', $args);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($button);

        // <xar:sec mask="..." catch="false">
        $security = new TwigFunction('xar_security_check', function ($mask, $catch = 0) use ($context) {
            return xarSecurity::check($mask, $catch);
        });
        $this->twig->addFunction($security);

        return $this->twig;
    }

    public function addDynamicDataTags()
    {
        $context = $this->getContext();

        // <xar:data-view object="$object" newlink=""/>
        $dataview = new TwigFunction('xar_data_view', function ($args = []) use ($context) {
            // Use the object attribute
            if (!empty($args['object'])) {
                $object = $args['object'];
                unset($args['object']);
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($context);
                }
                return $object->showView($args);
            }
            // This a string. we assume it's an object name
            if (!empty($args['objectname'])) {
                $objectName = $args['objectname'];
                unset($args['objectname']);
                $object = DataObjectFactory::getObjectList(['name' => $objectName], $context);
                $object->getItems($args);
                return $object->showView($args);
            }
            // No object or objectname? Generate ourselves then
            return xarMod::apiFunc('dynamicdata', 'user', 'showview', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($dataview);

        // <xar:data-display object="$object"/>
        $datadisplay = new TwigFunction('xar_data_display', function ($args = []) use ($context) {
            if (!empty($args['object'])) {
                $object = $args['object'];
                unset($args['object']);
                if (is_string($object)) {
                    $objectName = $object;
                    $object = DataObjectFactory::getObject(['name' => $objectName], $context);
                } else {
                    // @todo do we always overwrite the context or not?
                    if (empty($object->getContext())) {
                        $object->setContext($context);
                    }
                }
                return $object->showDisplay($args);
            }
            // No object passed in
            if (!empty($args['definition'])) {
                return xarMod::apiFunc('dynamicdata','user','showdisplay', $args['definition'], $context);
            }
            // No direct definition, use the attributes
            return xarMod::apiFunc('dynamicdata', 'user', 'showdisplay', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($datadisplay);

        // <xar:data-label property="$properties[$name]"/>
        $datalabel = new TwigFunction('xar_data_label', function ($args = []) use ($context) {
            // If we have an object, throw out its label
            if (!empty($args['object'])) {
                $object = $args['object'];
                return xarVar::prepForDisplay($object->label);
            }
            // We have a property
            if (!empty($args['property'])) {
                $property = $args['property'];
                if (isset($property)) {
                    unset($args['property']);
                    if (empty($property->objectref)) {
                        $property->objectref = (object) ['context' => $context];
                    }
                    return $property->showLabel($args);
                }
                return '';
            }
            // Ok, we have nothin, but a label
            if (!empty($args['label'])) {
                $args['context'] ??= $context;
                return xarTpl::property('dynamicdata', 'label', 'showoutput', $args, 'label');
            }
            return 'I need an object or a property or a label attribute';
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($datalabel);

        // <xar:data-output property="$properties[$name]" _itemid="$itemid" value="$fields[$name]"/>
        $dataoutput = new TwigFunction('xar_data_output', function ($args = []) use ($context) {
            if (empty($args['property'])) {
                // No prop, get one (the right one, preferably)
                $property = DataPropertyMaster::getProperty($args);
                $property->objectref = (object) ['context' => $context];
                // if we have a field attribute, use just that, otherwise use all attributes
                if (!empty($args['field'])) {
                    return $property->showOutput($args['field']);
                }
                return $property->showOutput($args);
            }
            // We already had a property object, run its output method
            $property = $args['property'];
            if (isset($property)) {
                unset($args['property']);
                if (empty($property->objectref)) {
                    $property->objectref = (object) ['context' => $context];
                }
                // if we have a field attribute, use just that, otherwise use all attributes
                if (!empty($args['field'])) {
                    return $property->showOutput($args['field']);
                }
                return $property->showOutput($args);
            }
            return '';
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($dataoutput);

        return $this->twig;
    }

    public function addWorkflowTags()
    {
        $context = $this->getContext();

        // <xar:workflow-actions name="actions" config="$config" item="$item" title="$item['marking']" template="$item['marking']"/>
        // @todo replace array with fixed order of params
        $workflow = new TwigFunction('xar_workflow_actions', function ($args = []) use ($context) {
            return xarMod::apiFunc('workflow', 'user', 'showactions', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($workflow);

        return $this->twig;
    }
}
