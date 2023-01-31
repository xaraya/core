<?php
/**
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('xaraya.blocks');

/**
 * Renders a single block
 *
 * @return string render block output
 */
function blocks_restapi_render($args = [])
{
    // needed to initialize the template cache
    xarTpl::init();
    // not really needed here but why not?
    xarBlock::init();
    try {
        $result = xarBlock::renderBlock($args);
    } catch (Exception $e) {
        $result = "Exception: " . $e->getMessage();
    }
    return $result;
}
