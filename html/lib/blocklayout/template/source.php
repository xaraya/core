<?php
/**
 * Representing blocklayout source templates
 *
 * @package blocklayout
 * @subpackage compiler
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

sys::import('blocklayout.template.compiled');

/**
 * Abstract class to model the source template
 *
 * @package blocklayout
**/
class SourceTemplate extends CompiledTemplate
{
    public function &compile() {}
}
?>
