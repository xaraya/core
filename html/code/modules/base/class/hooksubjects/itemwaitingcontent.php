<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * ItemWaitingcontent Hook Subject
 *
 * Notifies hooked observers when displaying waiting content block
 * @FIXME: this should be ModuleWaitingcontent
**/
/**
 * GUI type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.guisubject');
class BaseItemWaitingcontentSubject extends GuiHookSubject
{
    public $subject = 'ItemWaitingcontent';
}
?>