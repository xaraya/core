<?php
/**
 * ModuleActivate Hook Subject
 *
 * Notifies hooked observers when a module has been activated
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('xaraya.structures.hooks.apisubject');
class ModulesModuleActivateSubject extends ApiHookSubject
{
    public $subject = 'ModuleActivate';
}
