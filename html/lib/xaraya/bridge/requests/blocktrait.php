<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Services\BlocksInterface;
use Xaraya\Services\ServiceFactory;

/**
 * For documentation purposes only - available via BlockBridgeTrait
 */
interface BlockBridgeInterface extends CommonRequestInterface
{
    /**
     * Summary of parseBlockPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildBlockPath
     * @param string|int $type
     * @param ?string $method
     * @param string|int|null $instance
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string;

    /**
     * Summary of runBlockGuiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return string
     */
    public function runBlockGuiRequest($vars, $query = null): string;

    /**
     * Summary of runBlockApiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return array<mixed>
     */
    public function runBlockApiRequest($vars, $query = null): array;
}

/**
 * Handle Block requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
trait BlockBridgeTrait
{
    protected ?BlocksInterface $xarBlock = null;

    public function block(): BlocksInterface
    {
        $this->xarBlock ??= ServiceFactory::getBlocksService($this);
        return $this->xarBlock;
    }

    /**
     * Summary of parseBlockPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        // @todo
        return [];
    }

    /**
     * Summary of buildBlockPath
     * @param string|int $type
     * @param ?string $method
     * @param string|int|null $instance
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string
    {
        // @todo
        return '/';
    }

    // @checkme limited to renderBlock() for now
    /**
     * Summary of runBlockGuiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return string
     */
    public function runBlockGuiRequest($vars, $query = null): string
    {
        if (!empty($query)) {
            $vars = array_merge($vars, $query);
        }
        return $this->block()->guiRequest($vars);
    }

    /**
     * Summary of runBlockApiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @return array<mixed>
     */
    public function runBlockApiRequest($vars, $query = null): array
    {
        if (!empty($query)) {
            $vars = array_merge($vars, $query);
        }
        return $this->block()->apiRequest($vars);
    }
}
