<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 */
sys::import('xaraya.structures.events.subject');
class BaseServerRequestSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'ServerRequest';
}
?>