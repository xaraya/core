<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\Traits;

/**
 * Trait to handle other api functions for modules with their own DD objects
 */
trait OtherApiTrait
{
    /**
     * Get module util API class for this module
     */
    public function utilapi(): UserApiInterface
    {
        $component = $this->getModule()->getComponent('UtilApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }

    /**
     * Get module data API class for this module
     */
    public function dataapi(): UserApiInterface
    {
        $component = $this->getModule()->getComponent('DataApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }
}
