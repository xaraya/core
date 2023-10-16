<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

//use Xaraya\Core\Traits\DatabaseInterface;
//use Xaraya\Core\Traits\DatabaseTrait;
use Xaraya\DataObject\Traits\UserApiInterface;
use Xaraya\DataObject\Traits\UserApiTrait;
use sys;

//sys::import('xaraya.traits.databasetrait');
sys::import('modules.dynamicdata.class.traits.userapi');

/**
 * Class to handle the dynamicdata user API (example)
 */
class UserApi implements UserApiInterface  //, DatabaseInterface
{
    //use DatabaseTrait;
    use UserApiTrait;

    //protected static string $moduleName = 'dynamicdata';
    protected static int $moduleId = 182;
    protected static int $itemtype = 0;
}
