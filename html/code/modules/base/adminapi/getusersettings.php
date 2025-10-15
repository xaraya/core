<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminApi;
use Exception;
use sys;

sys::import('xaraya.modules.method');

/**
 * base adminapi getusersettings function
 * @extends MethodClass<AdminApi>
 */
class GetusersettingsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get user settings for admin API
     * @param array<string,mixed> $args Optional function parameters
     * @var string $args ['module'] Required module parameter
     * @var int $args ['itemid'] Required item id parameter
     * @return object Data object
     * @throws \Exception Thrown is module or itemid have not been provided.
     * @see AdminApi::getusersettings()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['module'])) {
            throw new Exception($this->ml('The getusersettings function requires a module parameter'));
        }
        if (!isset($args['itemid'])) { // itemid = 0, module vars :)
            throw new Exception($this->ml('The getusersettings function requires an itemid parameter'));
        }
        sys::import('modules.dynamicdata.class.objects.factory');
        // look for module specific user settings object
        $object = $this->data()->getObject(['name' => $args['module'] . '_user_settings']);
        // fall back to base module user settings?
        if (!isset($object)) {
            $object = $this->data()->getObject(['name' => 'user_settings']);
        }
        // shouldn't be necessary here, props should have the correct modvar datastore
        // but since props are easily added set it anyway, just to be sure...
        if (isset($object)) {
            foreach ($object->properties as $name => $property) {
                $object->properties[$name]->source = 'module variables: ' . $args['module'];
            }
            $object->getDatastore();
            $object->getItem($args);
        }
        return $object;
    }
}
