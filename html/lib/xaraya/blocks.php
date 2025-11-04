<?php

/**
 * Display Blocks
 * *
 * @package core\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Paul Rosania
 * @author Chris Powis
 */

sys::import("xaraya.context.context");
sys::import('xaraya.services.xar');
use Xaraya\Context\Context;
use Xaraya\Services\BlocksService;
use Xaraya\Services\xar;

interface ixarBlock
{
    public const TYPE_STATE_ACTIVE = 1;
    public const TYPE_STATE_MISSING = 2;
    public const TYPE_STATE_ERROR = 3;
    public const TYPE_STATE_MOD_UNAVAILABLE = 4;

    public const BLOCK_STATE_HIDDEN = 0;   // Hidden blocks still execute, they just don't render
    public const BLOCK_STATE_INACTIVE = 1; // Inactive blocks don't execute, don't render
    public const BLOCK_STATE_VISIBLE = 2;

    public static function render(array $data = [], $context = null);
    public static function renderBlock(array $args = [], $context = null);
    public static function renderGroup($groupname, $template = null, $context = null);
    public static function hasMethod(iBlockType $block, $method, $strict = false);
    public static function guiMethod(iBlock $block, $method);
    public static function checkAccess(iBlock $block, $action, $roleid = null);

}

/**
 * Display Blocks
 * *
 * @package core\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Paul Rosania
 * @author Chris Powis
 * @deprecated 2.8.5 use xar::block() instead
 */
class xarBlock extends xarObject implements ixarBlock
{
    protected static bool $initialized = false;
    protected static ?BlocksService $blockService = null;

    protected static function block(): BlocksService
    {
        if (!isset(self::$blockService)) {
            $xar = xar::getServicesClass();
            self::$blockService = $xar->block();
        }
        return self::$blockService;
    }

    private function __construct() {}
    /**
     * Initialize blocks subsystem
     *
     * @author Paul Rosania
     *
     * @param  array<string, mixed> $args
     * @return boolean
     */
    public static function init(array $args = [])
    {
        // static cache for migration
        self::$blockService = null;
        return self::block()->init($args);
    }

    /**
     * Renders a block instance
     *
     * @author Paul Rosania
     * @author Marco Canini <marco@xaraya.com>
     * @author Chris Powis
     *
     * @param array<string, mixed> $blockinfo block information parameters
     * @param ?Context<string, mixed> $context optional context for the block call (default = none)
     * @return string output the block to show
     * @todo   this function calls a module function, keep an eye on it.
     */
    public static function render(array $blockinfo = [], $context = null)
    {
        return self::block()->render($blockinfo);
    }

    public static function getObject(array $blockinfo = [], $interface = null, $method = null, $context = null)
    {
        return self::block()->getObject($blockinfo, $interface, $method);
    }

    /**
     * Helper function used by block subsystem to call a block method suitabled for rendering
     *
     * @author Chris Powis
     *
     * @param  BasicBlock $block the block instance supplying the method
     * @param  string $method, name of the method to call
     * @return string output the block to show
     * @throws  FunctionNotFoundException
     */
    public static function guiMethod(iBlock $block, $method, $block_tpl = null)
    {
        return self::block()->guiMethod($block, $method, $block_tpl);
    }

    /**
     * Helper function used by block subsystem to check if a block explicitly declared a method
     *
     * @author Chris Powis
     *
     * @param  BlockType $block the block instance supplying the method
     * @param  string $method, name of the method to check
     * @param  bool $strict, flag to indicate if the block must have declared the method
     * @return bool
     */
    public static function hasMethod(iBlockType $block, $method, $strict = false)
    {
        return self::block()->hasMethod($block, $method, $strict);
    }

    /**
     * Renders a single block
     *
     * @author John Cox
     *
     * @param array<string, mixed> $args
     * with
     *     string args[instance] id or name of block instance to render
     *     string args[module] module that owns the block
     *     string args[type] module that owns the block
     * @param ?Context<string, mixed> $context optional context for the block call (default = none)
     * @return string
     * @todo   this function calls a module function, keep an eye on it.
     */
    public static function renderBlock(array $args = [], $context = null)
    {
        return self::block()->renderBlock($args);
    }

    /**
     * Renders a block group
     *
     * @author Paul Rosania
     * @author Marco Canini <marco@xaraya.com>
     *
     * @todo support context in templates? See xar:blockgroup tag
     * @param string $groupname the name of the block group
     * @param ?string $template optional template to apply to all blocks in the group
     * @param ?Context<string, mixed> $context optional context for the block call (default = none)
     * @return string
     * @throws EmptyParameterException
     */
    public static function renderGroup($groupname, $template = null, $context = null)
    {
        return self::block()->renderGroup($groupname, $template);
    }

    /**
     * Check access for a specific action on block level (see also xarMod and xarObject)
     *
     * @param iBlock $block the block we want to check access for
     * @param string $action the action we want to take on this block (display/modify/delete)
     * @param mixed $roleid override the current user or null
     * @return boolean true if access
     */
    public static function checkAccess(iBlock $block, $action, $roleid = null)
    {
        return self::block()->checkAccess($block, $action, $roleid);
    }
}
