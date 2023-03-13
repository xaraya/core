<?php
/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

function mail_admin_modify($args = array())
{
    if(!xarVar::fetch('itemid','int:1:',$itemid,0,xarVar::NOT_REQUIRED)) return;
    if (empty($itemid)) return xarResponse::notFound();
    xarController::redirect(xarController::URL('mail','admin','view',array('itemid' => $itemid)));
    return true;
}
