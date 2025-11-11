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

use HookObserver;
use ixarEventSubject;
use ixarHookSubject;

/**
 * DataObject Hook Observer for Item* and Module* ixarHookSubject events
 * Notified if DD module is hooked to a particular module, itemtype and/or scope
 */
class DataObjectHookObserver extends HookObserver
{
    /** @var string */
    public $module = 'dynamicdata';
    /** @var string */
    public $type = 'admin';

    /**
     * @param ixarHookSubject $subject
     */
    public function notify(ixarEventSubject $subject)
    {
        // this is used in most run methods below, so we import it here
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
