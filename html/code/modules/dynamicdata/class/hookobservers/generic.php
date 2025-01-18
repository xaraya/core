<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\HookObservers;

use Xaraya\Services\ServicesInterface;
use Xaraya\Services\ServicesTrait;
use HookObserver;
use ixarEventSubject;
use ixarHookSubject;
use sys;

sys::import('xaraya.structures.hooks.observer');
sys::import('xaraya.services.servicestrait');

/**
 * DataObject Hook Observer for Item* and Module* ixarHookSubject events
 * Notified if DD module is hooked to a particular module, itemtype and/or scope
 */
class DataObjectHookObserver extends HookObserver implements ServicesInterface
{
    /** @use ServicesTrait<static> */
    use ServicesTrait;

    /** @var string */
    public $module = 'dynamicdata';
    /** @var string */
    public $section = 'admin';

    /**
     * Get name for this module in hook observer
     */
    public function getModName(): string
    {
        return $this->module;
    }

    /**
     * Get module type (user, admin, ...) from here
     */
    public function getModType(): string
    {
        return $this->section;
    }

    /**
     * @param ixarHookSubject $subject
     */
    public function notify(ixarEventSubject $subject)
    {
        // this is used in most run methods below, so we import it here
        sys::import('modules.dynamicdata.class.objects.factory');
        $this->setContext($subject->getContext());
        return $this->run($subject->getExtrainfo());
    }

    /**
     * @param array<string, mixed> $extrainfo extra information
     * @return array<mixed>|string|void API returns extrainfo array, GUI returns string or void
     */
    public function run(array $extrainfo = [])
    {
        return $extrainfo;
    }
}
