<?php
/**
 * Handle Block requests via PSR-7 and PSR-15 compatible middleware controllers or routing bridges
 */

namespace Xaraya\Bridge\Requests;

// use some Xaraya classes
use Exception;
use xarBlock;
use xarMod;

/**
 * For documentation purposes only - available via BlockBridgeTrait
 */
interface BlockBridgeInterface extends CommonRequestInterface
{
    public static function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array;
    public static function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string;
    public static function runBlockGuiRequest($vars, $query = null): string;
    public static function runBlockApiRequest($vars, $query = null): mixed;
}

trait BlockBridgeTrait
{
    public static function parseBlockPath(string $path = '/', array $query = [], string $prefix = ''): array
    {
        // @todo
        return [];
    }

    public static function buildBlockPath(string|int $type = 'menu', ?string $method = null, string|int|null $instance = null, array $extra = [], string $prefix = '/block'): string
    {
        // @todo
        return '/';
    }

    // @checkme limited to renderBlock() for now
    public static function runBlockGuiRequest($vars, $query = null): string
    {
        if (empty($vars['instance'])) {
            throw new Exception("Missing object parameter");
        }
        return xarBlock::renderBlock($vars);
    }

    // @checkme limited to getinfo() for now
    public static function runBlockApiRequest($vars, $query = null): mixed
    {
        if (empty($vars['instance'])) {
            throw new Exception("Missing object parameter");
        }
        return xarMod::apiFunc('blocks', 'blocks', 'getinfo', $vars);
    }
}
