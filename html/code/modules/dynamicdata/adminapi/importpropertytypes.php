<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;
use Exception;
use PropertyRegistration;

/**
 * dynamicdata adminapi importpropertytypes function
 * @extends MethodClass<AdminApi>
 */
class ImportpropertytypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Check the properties directory for properties and import them into the Property Type table.
     * @param array<string,mixed> $args array of optional parameters<br/>
     * boolean  $args[flush] flush the property type table before import true/false (optional)<br/>
     * array    $args[dirs]
     * @return array an array of the property types currently available
     * @see AdminApi::importpropertytypes()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($flush)) {
            $flush = true;
        }
        if (!isset($dirs)) {
            $dirs = [];
        }
        try {
            $proptypes = PropertyRegistration::importPropertyTypes($flush, $dirs, $this->getParent());
        } catch (Exception $e) {
            throw $e;
        }
        return $proptypes;
    }
}
