<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
sys::import('xaraya.structures.events.subject');
class BaseSessionCreateSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'SessionCreate';
}
?>