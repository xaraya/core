<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Xaraya\Context\Context;
use Exception;
use xarBlock;
use xarMod;

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
    public static function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array;

    /**
     * Summary of buildBlockPath
     * @param string|int $type
     * @param ?string $method
     * @param string|int|null $instance
     * @param array<string, mixed> $extra
     * @param string $prefix
     * @return string
     */
    public static function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string;

    /**
     * Summary of runBlockGuiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @return string
     */
    public static function runBlockGuiRequest($vars, $query = null, $context = null): string;

    /**
     * Summary of runBlockApiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @return mixed
     */
    public static function runBlockApiRequest($vars, $query = null, $context = null): mixed;
}

/**
 * Handle Block requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */
trait BlockBridgeTrait
{
    /**
     * Summary of parseBlockPath
     * @param string $path
     * @param array<string, mixed> $query
     * @param string $prefix
     * @return array<string, mixed>
     */
    public static function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array
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
    public static function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string
    {
        // @todo
        return '/';
    }

    // @checkme limited to renderBlock() for now
    /**
     * Summary of runBlockGuiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @throws \Exception
     * @return string
     */
    public static function runBlockGuiRequest($vars, $query = null, $context = null): string
    {
        if (empty($vars['instance'])) {
            throw new Exception("Missing object parameter");
        }
        return xarBlock::renderBlock($vars, $context);
    }

    // @checkme limited to getinfo() for now
    /**
     * Summary of runBlockApiRequest
     * @param array<string, mixed> $vars
     * @param ?array<string, mixed> $query
     * @param ?Context<string, mixed> $context
     * @throws \Exception
     * @return mixed
     */
    public static function runBlockApiRequest($vars, $query = null, $context = null): mixed
    {
        if (empty($vars['instance'])) {
            throw new Exception("Missing object parameter");
        }
        return xarMod::apiFunc('blocks', 'blocks', 'getinfo', $vars, $context);
    }
}
