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
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use Xaraya\Context\Context;
use Xaraya\Services\ServicesInterface;
use Xaraya\Services\WithServicesClass;
use xarConst;
use sys;

/**
 * Use Twig template engine to generate output in Xaraya
 *
 * Xaraya Extensions:
 * 1. XarayaCoreExtension - see xaraya.php
 * 2. BlocklayoutTagExtension - see blocklayout.php
 * 3. DynamicDataTagExtension - see dynamicdata.php
 * 4. ModuleTagExtension - see modules.php
 * 5. PHPOtherExtension - see phpothers.php
 *
 */
class TwigBridge implements ContextInterface
{
    use ContextTrait;
    use WithServicesClass;

    /** @var array<string, string> */
    private array $paths = [];
    /** @var array<string, mixed> */
    private array $options = [];
    private Environment $twig;
    private FilesystemLoader $loader;

    /**
     * @param array<string, string> $paths
     * @param array<string, mixed> $options
     * @param ?Context<string, mixed> $context
     * @param ?ServicesInterface $xar
     */
    public function __construct(array $paths = [], array $options = [], ?Context $context = null, $xar = null)
    {
        $this->setPaths($paths);
        $this->setOptions($options);
        $this->setContext($context);
        $this->setServicesClass($xar);
    }

    /**
     * @return array<string, string>
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * @param array<string, string> $paths
     * @return array<string, string>
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
        return $this->paths;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function setOptions(array $options)
    {
        $this->options = array_replace([
            'cache' => sys::varpath() . xarConst::TPL_CACHEDIR,
        ], $options);
        return $this->options;
    }

    /**
     * @return LoaderInterface
     */
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
     * @return Environment
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
        // @todo do we need to update the context in the extension?
        // @see https://twig.symfony.com/doc/3.x/advanced.html#definition-vs-runtime
        return $this->twig;
    }

    /**
     * @return self
     */
    public function addXarayaExtensions()
    {
        $context = $this->getContext();
        // add context as global variable - @todo do we want this here?
        $this->twig->addGlobal('context', $context);

        $xar = $this->getServicesClass();
        $this->twig->addExtension(new XarayaCoreExtension($context, $xar));
        $this->twig->addExtension(new BlocklayoutTagExtension($context, $xar));
        $this->twig->addExtension(new DynamicDataTagExtension($context, $xar));
        $this->twig->addExtension(new ModuleTagExtension($context, $xar));
        $this->twig->addExtension(new PHPOtherExtension($context, $xar));

        return $this;
    }
}
