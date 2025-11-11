<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\UserApi;
use Exception;

/**
 * categories userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to retrieve the list of item types of this module (if any)
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return array Returns array containing the item types and their description
     * @see UserApi::getitemtypes()
     */
    public function __invoke(array $args = [])
    {
        $itemtypes = [];

        /* itemtype 0 not used, means "All". Itemtype 1 not used, would be a category object without properties*/
        $itemtypes[1] = ['label' => $this->ml('Bare Category'),
            'title' => $this->ml('View Bare Category'),
            'url'   => $this->ctl()->getModuleURL('categories', 'admin', 'view'),
        ];
        $itemtypes[2] = ['label' => $this->ml('Category'),
            'title' => $this->ml('View Category'),
            'url'   => $this->ctl()->getModuleURL('categories', 'admin', 'view'),
        ];

        try {
            $extensionitemtypes = $this->mod()->apiFunc('dynamicdata', 'user', 'getmoduleitemtypes', ['moduleid' => 147, 'native' => false]);
        } catch (Exception $e) {
            $extensionitemtypes = [];
        }
        if ($extensionitemtypes) {
            $types = [];
            foreach ($itemtypes as $key => $value) {
                $types[$key] = $value;
            }
            foreach ($extensionitemtypes as $key => $value) {
                $types[$key] = $value;
            }

            /* TODO: activate this code when we move to php5 - that would be about now, no? ;-)
            $keys = array_merge(array_keys($itemtypes),array_keys($extensionitemtypes));
            $values = array_merge(array_values($itemtypes),array_values($extensionitemtypes));
            return array_combine($keys,$values);
            */

        } else {
            $types = $itemtypes;
        }

        return $types;
    }
}
