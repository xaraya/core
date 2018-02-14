<?php
/**
 * ItemFormaction hook Subject
 *
 * Handles item formaction hook observers (these typically return string of template data)
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('xaraya.structures.hooks.guisubject');
class ModulesItemFormactionSubject extends GuiHookSubject
{
    public $subject = 'ItemFormaction';
    // methods inherited from parent...
}
?>