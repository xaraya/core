<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\RestApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\RestApi;
use Exception;
use xarBlock;
use xarTpl;
use sys;

sys::import('xaraya.modules.method');

/**
 * blocks restapi render function
 * @extends MethodClass<RestApi>
 */
class RenderMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Renders a single block
     * @return string render block output
     * @see RestApi::render()
     */
    public function __invoke($args = [])
    {
        // needed to initialize the template cache
        xarTpl::init();
        // not really needed here but why not?
        xarBlock::init();
        try {
            $result = xarBlock::renderBlock($args, $this->getContext());
        } catch (Exception $e) {
            $result = "Exception: " . $e->getMessage();
        }
        return $result;
    }
}
