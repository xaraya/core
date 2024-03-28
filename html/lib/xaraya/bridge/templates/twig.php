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

use Exception;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\TwigTest;
use Twig\Loader\FilesystemLoader;
use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use Xaraya\Context\Context;
use DataObjectFactory;
use DataPropertyMaster;
use xarBlock;
use xarConfigVars;
use xarConst;
use xarController;
use xarLocale;
use xarMLS;
use xarMod;
use xarModVars;
use xarSecurity;
use xarServer;
use xarSession;
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

        $guiFunc = new TwigFunction('xar_guifunc', function ($modName, $modType = 'user', $funcName = 'main', $args = []) use ($context) {
            // use current context
            return xarMod::guiFunc($modName, $modType, $funcName, $args, $context);
        });
        $this->twig->addFunction($guiFunc);

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

        $currentURL = new TwigFunction('xar_currenturl', function ($args = [], $generateXMLURL = null, $target = null) {
            // avoid double-encoding URLs
            $generateXMLURL ??= false;
            return xarServer::getCurrentURL($args, $generateXMLURL, $target);
        });
        $this->twig->addFunction($currentURL);

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
        $function = new TwigFunction('xar_coremethod', function ($class, $method, $params = []) {
            return $class::$method(...$params);
        });
        $this->twig->addFunction($function);

        // @todo add some simple tests too
        $numeric = new TwigTest('numeric', function ($value) {
            return is_numeric($value);
        });
        $this->twig->addTest($numeric);

        $object = new TwigTest('object', function ($value) {
            return is_object($value);
        });
        $this->twig->addTest($object);

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

        // <xar:blocklayout version="2.0" content="text/html" xmlns:xar="http://xaraya.com/2004/blocklayout" dtd="xhtml1-strict">
        $content = new TwigFunction('xar_twig_content', function ($contentType) use ($context) {
            if (!headers_sent()) {
                // @todo use current context
                $locale = xarMLS::getCurrentLocale();
                $charSet = xarMLS::getCharsetFromLocale($locale);
                header("Content-Type: " . $contentType . "; charset=" . $charSet);
            }
            // Note: doctype is already converted once
            return '';
        });
        $this->twig->addFunction($content);

        $blockGroup = new TwigFunction('xar_blockgroup', function ($groupname, $template = null) use ($context) {
            // use current context
            return xarBlock::renderGroup($groupname, $template, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($blockGroup);

        $block = new TwigFunction('xar_block', function ($args = []) use ($context) {
            $fixed = ['instance', 'module', 'type', 'name', 'title', 'template', 'state', 'tplmodule'];
            $allowed = array_flip($fixed);
            $params = array_intersect_key($args, $allowed);
            $params['content'] = array_keys(array_diff_key($args, $allowed));
            if (!empty($params['content'])) {
                throw new Exception('Content in block tag: ' . var_export($params, true));
            }
            // use current context
            return xarBlock::renderBlock($params, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($block);

        /**
        <xar:set name="checked">
            <xar:var scope="module" module="themes" name="var_dump"/>
        </xar:set>
         */
        // @todo use context where relevant
        $var = new TwigFunction('xar_var', function ($args = []) use ($context) {
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
        });
        $this->twig->addFunction($var);

        // <xar:pager startnum="$object->startnum" itemsperpage="$object->numitems" total="$object->startnum" urltemplate="$object->pagerurl" template="multipageprev"/>
        $pager = new TwigFunction('xar_pager', function ($args = []) use ($context) {
            return xarMod::apiFunc('base', 'user', 'pager', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($pager);

        // <xar:javascript scope="theme" filename="checkall.js" position="head"/>
        $javascript = new TwigFunction('xar_javascript', function ($args = []) use ($context) {
            xarMod::apiFunc('themes', 'user', 'registerjs', $args, $context);
            return '';
        });
        $this->twig->addFunction($javascript);

        // <xar:place-javascript position="body"/>
        $place_js = new TwigFunction('xar_place_javascript', function ($args = []) use ($context) {
            $position = $args['position'];
            $type = $args['type'] ?? '';
            return trim(xarMod::apiFunc('themes', 'user', 'renderjs', ['position' => $position, 'type' => $type], $context));
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($place_js);

        // <xar:style scope="module" module="base" file="tabs"/>
        // @todo replace array with fixed order of params
        $style = new TwigFunction('xar_style', function ($args = []) use ($context) {
            xarMod::apiFunc('themes', 'user', 'register', $args, $context);
            return '';
        });
        $this->twig->addFunction($style);

        // <xar:place-css />
        $place_css = new TwigFunction('xar_place_css', function ($args = []) use ($context) {
            return xarMod::apiFunc('themes', 'user', 'deliver', ['method' => 'render', 'base' => 'theme']);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($place_css);

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
                return xarMod::apiFunc('dynamicdata', 'user', 'showdisplay', $args['definition'], $context);
            }
            // No direct definition, use the attributes
            return xarMod::apiFunc('dynamicdata', 'user', 'showdisplay', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($datadisplay);

        $dataform = new TwigFunction('xar_data_form', function ($args = []) use ($context) {
            if (!empty($args['object'])) {
                // Use the object attribute
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
                return $object->showForm($args);
            }
            // No object passed in
            if (!empty($args['definition'])) {
                return xarMod::apiFunc('dynamicdata', 'user', 'showform', $args['definition'], $context);
            }
            // No direct definition, use the attributes
            return xarMod::apiFunc('dynamicdata', 'user', 'showform', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($dataform);

        $datafilterform = new TwigFunction('xar_data_filterform', function ($args = []) use ($context) {
            if (!empty($args['object'])) {
                // Use the object attribute
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
                return $object->showFilterForm($args);
            }
            // No object passed in
            if (!empty($args['definition'])) {
                return xarMod::apiFunc('dynamicdata', 'user', 'showfilterform', $args['definition'], $context);
            }
            // No direct definition, use the attributes
            return xarMod::apiFunc('dynamicdata', 'user', 'showfilterform', $args, $context);
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($datafilterform);

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

        $datainput = new TwigFunction('xar_data_input', function ($args = []) use ($context) {
            try {
                $params = $args;
                unset($params['hidden']);
                unset($params['preset']);
                if (empty($args['property'])) {
                    // No property, gotta make one
                    $property = DataPropertyMaster::getProperty($params);
                    $property->objectref = (object) ['context' => $context];
                } else {
                    // We do have a property in the attribute
                    $property = $args['property'];
                    unset($params['property']);
                    if (empty($property->objectref)) {
                        $property->objectref = (object) ['context' => $context];
                    }
                }
                if (!empty($args['preset']) && !isset($args['value'])) {
                    return $property->_showPreset($params);
                }
                if (!empty($args['hidden'])) {
                    return $property->showHidden($params);
                }
                return $property->showInput($params);
            } catch (Exception $e) {
                if (xarModVars::get('dynamicdata', 'debugmode') && in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                    return "<pre>" . $e->getMessage() . "</pre>";
                }
                return '';
            }
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($datainput);

        $datafilter = new TwigFunction('xar_data_filter', function ($args = []) use ($context) {
            try {
                $params = $args;
                unset($params['hidden']);
                unset($params['preset']);
                if (empty($args['property'])) {
                    // No property, gotta make one
                    $property = DataPropertyMaster::getProperty($params);
                    $property->objectref = (object) ['context' => $context];
                } else {
                    // We do have a property in the attribute
                    $property = $args['property'];
                    unset($params['property']);
                    if (empty($property->objectref)) {
                        $property->objectref = (object) ['context' => $context];
                    }
                }
                if (!empty($args['hidden'])) {
                    return $property->showHidden($params);
                }
                return $property->showFilter($params);
            } catch (Exception $e) {
                if (xarModVars::get('dynamicdata', 'debugmode') && in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
                    return "<pre>" . $e->getMessage() . "</pre>";
                }
                return '';
            }
        }, ['is_safe' => ['html']]);
        $this->twig->addFunction($datafilter);

        $datagetitems = new TwigFunction('xar_data_getitems', function ($args = []) use ($context) {
            // take a copy of the arguments if we're passing variables we want to re-use!?
            $properties = $args['properties'];
            $values = $args['values'];
            $params = $args;
            unset($params['properties']);
            unset($params['values']);
            // Use the object attribute
            if (!empty($args['object'])) {
                $object = $args['object'];
                unset($params['object']);
                // @todo do we always overwrite the context or not?
                if (empty($object->getContext())) {
                    $object->setContext($context);
                }
                $values = $object->getItems($params);
                $properties = $object->getProperties();
                return [$properties, $values];
            }
            // This a string. we assume it's an object name
            if (!empty($args['objectname'])) {
                $objectName = $args['objectname'];
                unset($params['objectname']);
                $object = DataObjectFactory::getObjectList(['name' => $objectName], $context);
                $values = $object->getItems($params);
                $properties = $object->getProperties();
                return [$properties, $values];
            }
            [$properties, $values] = xarMod::apiFunc('dynamicdata', 'user', 'getitemsforview', $params, $context);
            return [$properties, $values];
        });
        $this->twig->addFunction($datagetitems);

        $datagetitem = new TwigFunction('xar_data_getitem', function ($args = []) use ($context) {
            // take a copy of the arguments if we're passing variables we want to re-use!?
            $properties = $args['properties'];
            $params = $args;
            unset($params['properties']);
            if (!empty($args['object'])) {
                $object = $args['object'];
                unset($params['object']);
                if (is_string($object)) {
                    $objectName = $object;
                    $object = DataObjectFactory::getObject(['name' => $objectName], $context);
                } else {
                    // @todo do we always overwrite the context or not?
                    if (empty($object->getContext())) {
                        $object->setContext($context);
                    }
                }
            } else {
                $params = array_merge(['getobject' => 1], $params);
                $object = xarMod::apiFunc('dynamicdata', 'user', 'getitem', $params, $context);
            }
            $object->getItem($params);
            // @todo not sure this will help unless we change template too
            $properties = $object->getProperties($params);
            return $properties;
        });
        $this->twig->addFunction($datagetitem);

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
