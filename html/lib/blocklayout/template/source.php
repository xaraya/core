<?php
/**
 * Representing blocklayout source templates
 *
 * @package blocklayout
 * @subpackage compiler
 * @copyright see the html/credits.html file in this release
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
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
