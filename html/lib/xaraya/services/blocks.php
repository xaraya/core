<?php

/**
 * Blocks available via methods (TODO)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarBlock;
use xarMod;
use xarModVars;
use xarTpl;
use sys;
use Exception;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via BlocksTrait
 */
interface BlocksInterface extends ServiceInterface
{
    /** @param array<string, mixed> $tplData */
    public function template(string $funcName, array $tplData = [], ?string $templateName = null): string;
    /**
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array;
    /** @param array<mixed> $args */
    public function guiRequest(array $args): string;
    /**
     * @param array<mixed> $args
     * @return array<mixed>
     */
    public function apiRequest(array $args): array;
}

/**
 * Blocks available via methods
 */
trait BlocksTrait
{
    use ServiceTrait;

    /**
     * Render output with block template
     * @uses xarTpl::block()
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @param ?string $templateName
     * @return string
     */
    public function template(string $funcName, array $tplData = [], ?string $templateName = null): string
    {
        // Add standard template variables (module, itemtype and context)
        $tplData = $this->prepare($tplData);

        // See if we have a special template to apply
        if (!isset($templateName) && isset($tplData['_bl_template'])) {
            $templateName = (string) $tplData['_bl_template'];
        }

        $modName = $this->getModName();
        $blockType = $this->getBlockType();

        // Create the output.
        return xarTpl::block(
            $modName,
            $blockType,
            $tplData,
            $templateName
        );
    }

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array
    {
        // Add standard template variables
        $tplData['module'] ??= $this->getModName();
        //@todo $tplData['blocktype'] ??= $this->getBlockType();
        // Pass along the context for xarTpl::module() if needed
        $tplData['context'] ??= $this->getContext();
        return $tplData;
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
        return xarBlock::renderBlock($args, $this->getContext());
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
        return xarMod::apiFunc('blocks', 'blocks', 'getinfo', $args, $this->getContext());
    }
}

/**
 * Access xarBlock*::* Blocks methods (template, ...)
 *
 * Available methods:
 * - template() for current block type - or use tpl()->block() in general with modName blockType
 * - prepare()
 * - guiRequest()
 * - apiRequest()
 * - ...
 *
 * Required methods in parent:
 * - getModName()
 * - getBlockType() for block()->template()
 *
 */
class BlocksService implements BlocksInterface
{
    use BlocksTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }

    /**
     * Get block type from parent
     */
    public function getBlockType(): string
    {
        return $this->getParent()->getBlockType();
    }
}
