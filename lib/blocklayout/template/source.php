<?php
/**
 * Representing blocklayout source templates
 *
 * @package blocklayout
 * @copyright The Digital Development Foundation, 2006-07-26
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