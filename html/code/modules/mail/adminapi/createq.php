<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use Xaraya\Modules\Mail\UserApi;
use DataObjectFactory;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi createq function
 * @extends MethodClass<AdminApi>
 */
class CreateqMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see AdminApi::createq()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security Check
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        extract($args);

        // Create a new queue storage object from the xml definition
        $xmlDef = file_get_contents(sys::code() . 'modules/mail/xardata/qdatadef.xml');
        $qdataObjectId = $this->mod()->apiFunc('dynamicdata', 'util', 'import', ['objectname' => 'q_' . $name, 'xml' => $xmlDef]);
        if (!isset($qdataObjectId)) {
            return;
        }

        // Get the itemtypes of the mail module
        $itemtypes = $userapi->getitemtypes();
        // Get the max value from the keys and add one
        ksort($itemtypes);
        end($itemtypes);
        $newItemtype = key($itemtypes) + 1;
        if ($newItemtype == 0) {
            $newItemtype++;
        } // prevent the 0 value
        // Create a new itemtype by creating a new object in dd
        $params = ['objectid' => $qdataObjectId, 'itemtype' => $newItemtype];
        $itemid = DataObjectFactory::updateObject($params);

        return true;
    }
}
