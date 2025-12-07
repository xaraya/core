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
        $this->tpl()->init();
        try {
            $result = $this->block()->renderBlock($args);
        } catch (Exception $e) {
            $result = "Exception: " . $e->getMessage();
        }
        return $result;
    }
}
