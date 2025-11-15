<?php

/**
 * Make Core Services available via $this->getServicesClass() in trait (instance method)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

/**
 * Make Core Services available via $this->getServicesClass() in trait (instance method)
 *
 * ```
 * use Xaraya\Services\WithServicesClass;
 *
 * class MyFancyClass
 * {
 *     use WithServicesClass;
 *
 *     public function helloWorld():
 *     {
 *         $xar = $this->getServicesClass();
 *         $dbconn = $xar->db()->getConn();
 *         // ...
 *     }
 * }
 * ```
 */
trait WithServicesClass
{
    // aligned with CoreServicesTrait if both are used, e.g. DD UtilApi or Library = UserApiTrait + DatabaseTrait
    protected ?StaticServicesClass $xarServices = null;

    /**
     * Get services class, possibly preset with parent for methods (e.g. xarCSS::getInstance())
     */
    public function getServicesClass(?ServicesInterface $xar = null): StaticServicesClass
    {
        $this->setServicesClass($xar);
        if (!isset($this->xarServices)) {
            $this->xarServices = xar::getServicesClass();
        }
        return $this->xarServices;
    }

    public function setServicesClass(?ServicesInterface $xar): void
    {
        if (!isset($xar)) {
            return;
        }
        // set static services from the parent, e.g. UserGui()
        $this->xarServices = $xar->getStaticServices();
    }
}
