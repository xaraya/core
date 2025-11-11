<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData;

use Xaraya\Modules\UserApiClass;

/**
 * Handle the dynamicdata data API
 *
 * @method mixed getdatatypeoptions(array $args = [])
 * @extends UserApiClass<Module>
 */
class DataApi extends UserApiClass
{
    public function configure()
    {
        $this->setModType('data');
        // don't call xar::mod()->apiLoad() for dynamicdata data API
    }
}
