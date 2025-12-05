<?php

/**
 * Blocks available via methods (TODO)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use iBlock;
use iBlockType;
use ixarBlock;
use xarClassMap;
use ReflectionClass;
use BadParameterException;
use ClassNotFoundException;
use EmptyParameterException;
use FunctionNotFoundException;
use Exception;

/**
 * For documentation purposes only - available via BlocksTrait
 */
interface BlocksInterface extends ServiceInterface
{
    public const SLICE = 'blocks';

    /** @param array<string, mixed> $blockinfo */
    public function render(array $blockinfo = []): string;
    public function renderGroup(string $groupName, ?string $template): string;
    /** @param array<string, mixed> $args */
    public function renderBlock(array $args): string;
    /** @param array<string, mixed> $blockinfo */
    public function getObject(array $blockinfo = [], ?string $interface = null, ?string $method = null): iBlock;
    public function guiMethod(iBlock $block, string $method, ?string $block_tpl = null): string;
    public static function hasMethod(iBlockType $block, string $method, bool $strict = false): bool;
    /** @param array<mixed> $args */
    public function guiRequest(array $args): string;
    /**
     * @param array<mixed> $args
     * @return array<mixed>
     */
    public function apiRequest(array $args): array;
    public function checkAccess(iBlock $block, string $action, ?int $roleid = null): bool;
}

/**
 * Blocks available via methods
 */
trait BlocksTrait
{
    use ServiceTrait;

    protected bool $initialized = false;

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($config) && $this->initialized) {
            return true;
        }
        $xar = $this->getServicesClass();
        // Blocks Support Tables
        $xar->mod()->loadDbInfo('blocks');
        $this->initialized = true;
        return true;
    }

    public function isLoaded(): bool
    {
        return $this->initialized;
    }

    /**
     * Summary of render
     * @param array<string, mixed> $blockinfo
     */
    public function render(array $blockinfo = []): string
    {
        $xar = $this->getServicesClass();
        // Get a cache key for this block if it's suitable for block caching
        $cacheKey = $xar->cache()->getBlockKey($blockinfo);

        // Check if the block is cached
        if ($xar->cache()->hasBlock($cacheKey)) {
            // Return the cached block output
            return $xar->cache()->getBlock($cacheKey);
        }

        try {
            // get the block instance
            $block = $this->getObject($blockinfo, 'display', null);
            // set context if available in block render
            $block->setContext($this->getContext());

            // check if block expired already
            $now = time();
            if ($block->expire && $now > $block->expire) {
                $xar->cache()->setBlock($cacheKey, '');
                return '';
            }
            // checkAccess for display method
            if (!$block->checkAccess('display')) {
                $xar->cache()->setBlock($cacheKey, '');
                if (isset($block->display_access) && $block->display_access['failure']) {
                    // @TODO: render to an error/exception block?
                    return $xar->tpl()->module(
                        'privileges',
                        'user',
                        'errors',
                        ['layout' => 'no_block_privileges'],
                    );
                }
                return '';
            }
            // don't render hidden blocks
            if ($block->state == ixarBlock::BLOCK_STATE_HIDDEN) {
                // just execute the display method and return an empty string
                $block->display();
                $xar->cache()->setBlock($cacheKey, '');
                return '';
            }
            // render the block
            $blockinfo['content'] = $this->guiMethod($block, 'display');
            // no content, ok, nothing to display
            if (empty($blockinfo['content'])) {
                $xar->cache()->setBlock($cacheKey, '');
                return '';
            }
            // render to box template if necessary
            if ($block->type_category == 'group') {
                $boxOutput = $blockinfo['content'];
            } else {
                // title may have been over-ridden by the block setTitle() method
                $blockinfo['title'] = $block->title;
                $blockinfo['_bl_block_id']       = $block->block_id;
                $blockinfo['_bl_block_name']     = $block->name;
                $blockinfo['_bl_block_type']     = $block->type;
                $blockinfo['_bl_block_type_id']  = $block->type_id;
                $blockinfo['_bl_block_group']    = $block->group;
                $blockinfo['_bl_block_group_id'] = $block->group_id;
                // @todo: deprecate use of these
                $blockinfo['group'] = $block->group;
                $blockinfo['group_id'] = $block->group_id;
                // Pass along the block context for xar::tpl()->renderBlockBox() if needed
                $blockinfo['context'] ??= $block->getContext();
                $boxOutput = $xar->tpl()->renderBlockBox($blockinfo, $block->box_template);
            }

            // Set the output of the block in cache
            $xar->cache()->setBlock($cacheKey, $boxOutput);

            return $boxOutput;

        } catch (Exception $e) {
            if ((bool) $xar->mod('blocks')->getVar('noexceptions') || !$xar->user()->isDebugAdmin()) {
                $xar->cache()->setBlock($cacheKey, '');
                return '';
            } else {
                throw($e);
            }
        }
    }

    /**
     * Render block group by name
     */
    public function renderGroup(string $groupName, ?string $template): string
    {
        if (empty($groupName)) {
            throw new EmptyParameterException('groupName');
        }
        return $this->renderBlock(['instance' => $groupName, 'box_template' => $template]);
    }

    /**
     * Render single block instance
     * @param array<string, mixed> $args
     */
    public function renderBlock(array $args): string
    {
        $xar = $this->getServicesClass();
        // All the hard work is done in this function.
        // It keeps the core code lighter when standalone blocks are not used.
        if (isset($args['instance'])) {  // valid block instance states
            $args['state'] = [ixarBlock::BLOCK_STATE_VISIBLE, ixarBlock::BLOCK_STATE_HIDDEN];
        }
        $args['type_state'] = [ixarBlock::TYPE_STATE_ACTIVE]; // valid block type states
        // get block info
        try {
            $blockinfo = $xar->mod()->apiFunc('blocks', 'blocks', 'getinfo', $args);
            return $this->render($blockinfo);
        } catch (Exception $e) {
            if ((bool) $xar->mod('blocks')->getVar('noexceptions') || !$xar->user()->isDebugAdmin()) {
                // Get a cache key for this block if it's suitable for block caching
                if (!empty($blockinfo)) {
                    $cacheKey = $xar->cache()->getBlockKey($blockinfo);
                    $xar->cache()->setBlock($cacheKey, '');
                }
                return '';
            } else {
                throw($e);
            }
        }
    }

    /**
     * Summary of getObject
     * @param array<string, mixed> $blockinfo
     * @throws \BadParameterException
     * @throws \ClassNotFoundException
     * @throws \FunctionNotFoundException
     */
    public function getObject(array $blockinfo = [], ?string $interface = null, ?string $method = null): iBlock
    {
        $xar = $this->getServicesClass();
        $invalid = [];
        if (empty($blockinfo['type']) || !is_string($blockinfo['type'])) {
            $invalid[] = 'type';
        }
        if (!empty($blockinfo['module']) && !is_string($blockinfo['module'])) {
            $invalid[] = 'module';
        }
        if (isset($interface) && !is_string($interface)) {
            $invalid[] = 'interface';
        }
        if (isset($method) && !is_string($method)) {
            $invalid[] = 'method';
        }
        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) subsystem #(3) class method #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'xarBlock', 'getObject'];
            throw new BadParameterException($vars, $msg);
        }

        // use xarClassMap::findBlock() here
        $result = xarClassMap::findBlock($blockinfo['module'] ?? '', $blockinfo['type'], $interface);
        if (!empty($result)) {
            $classname = $result['classname'];
            $filepath = $result['filepath'];
            // require the file (raises error if file not found)
            require_once($filepath);
            // we need to set the actual $filepath here before constructing the object
            $blockinfo['filepath'] = $filepath;

            if (!class_exists($classname)) {
                throw new ClassNotFoundException($classname);
            }

            if (!empty($method) && !method_exists($classname, $method)) {
                throw new FunctionNotFoundException($classname . '::' . $method);
            }

            // Load the block language files
            // What to do here? return doesnt seem right
            if (!$xar->mls()->loadTranslations($filepath)) {
                throw new FunctionNotFoundException($classname . '::' . $method . ' translations');
            }

            $object = new $classname($blockinfo, $this->getContext(), $xar);

            return $object;
        }

        // @deprecated 2.7.0 remove old code
        $key = !empty($blockinfo['module']) ? $blockinfo['module'] . ':' . $blockinfo['type'] : $blockinfo['type'];
        throw new ClassNotFoundException($key);
    }

    public function guiMethod(iBlock $block, string $method, ?string $block_tpl = null): string
    {
        $xar = $this->getServicesClass();
        if (!method_exists($block, $method)) {
            throw new FunctionNotFoundException($method);
        }

        $tplData = $block->$method();
        if (is_array($tplData)) {
            // handler for legacy block display methods returning tpl data in $content
            // @todo remove when all module blocks are updated
            if ($method == 'display' && isset($tplData['content'])) {
                $tplData = $tplData['content'];
            }
            // inject blocklayout info
            $tplData['_bl_block_id']       = $block->block_id;
            $tplData['_bl_block_name']     = $block->name;
            $tplData['_bl_block_type']     = $block->type;
            $tplData['_bl_block_type_id']  = $block->type_id;
            $tplData['_bl_block_group']    = $block->group;
            $tplData['_bl_block_group_id'] = $block->group_id;

            // Legacy (deprecated)
            // @TODO: remove these once all block templates are using the _bl_ variables
            $tplData['blockid'] = $tplData['bid'] = $block->block_id;
            $tplData['blockname'] = $block->name;
            $tplData['blocktypename'] = $block->type;
            // The block may not be rendered as part of a group.
            $tplData['blockgid'] = $block->group_id;
            $tplData['blockgroupname'] = $tplData['group'] = $block->group;

            if ($method != 'display') {
                if (empty($block_tpl)) {
                    $block_tpl = $method . '-' . $block->type;
                }
                $block->setTemplateBase($block_tpl);
                $block->setBlockTemplate(null);
            }
            // Pass along the block context for xar::tpl()->block() if needed
            $tplData['context'] ??= $block->getContext();
            return $xar->tpl()->block(
                $block->module,
                $block->type,
                $tplData,
                $block->block_template,
                $block->template_base,
                $block->tplmodule,
            );
        } elseif (!empty($tplData) && is_string($tplData)) {
            return $tplData;
        } else {
            return '';
        }
    }

    public static function hasMethod(iBlockType $block, string $method, bool $strict = false): bool
    {
        $hasMethod = method_exists($block, $method);
        // if not strict or method not exist, return
        if (!$strict || !$hasMethod) {
            return $hasMethod;
        }

        // strict checks that this class and not one of its parents declared it
        $refObject  = new ReflectionClass($block);
        $baseClass = !empty($block->module)
                     ? ucfirst($block->module) . '_' . ucfirst($block->type) . 'Block'
                     : ucfirst($block->type) . 'Block';
        if ($refObject->hasMethod($method)) {
            $methodObject = $refObject->getMethod($method);
            $hasMethod = (($methodObject->class === $refObject->getName())
                           || (stripos($methodObject->class, $baseClass) === 0));
        } else {
            $hasMethod = false;
        }
        unset($refObject, $methodObject);

        return $hasMethod;
    }

    /**
     * Summary of guiRequest
     * @todo limited to renderBlock() for now
     * @param array<mixed> $args
     * @return string
     */
    public function guiRequest(array $args): string
    {
        if (empty($args['instance'])) {
            throw new Exception("Missing object parameter");
        }
        return $this->renderBlock($args);
    }

    /**
     * Summary of apiRequest
     * @todo limited to getinfo() for now
     * @param array<mixed> $args
     * @throws \Exception
     * @return array<mixed>
     */
    public function apiRequest(array $args): array
    {
        if (empty($args['instance'])) {
            throw new Exception("Missing object parameter");
        }
        $xar = $this->getServicesClass();
        return $xar->mod()->apiFunc('blocks', 'blocks', 'getinfo', $args);
    }

    public function checkAccess(iBlock $block, string $action, ?int $roleid = null): bool
    {
        // TODO: support $roleid there someday ?
        return $block->checkAccess($action);
    }
}

/**
 * Access xarBlock*::* Blocks methods (render, ...)
 *
 * Available methods:
 * - render()
 * - renderBlock()
 * - renderGroup()
 * - guiRequest()
 * - apiRequest()
 * - ...
 *
 */
class BlocksService implements BlocksInterface
{
    use BlocksTrait;
}
