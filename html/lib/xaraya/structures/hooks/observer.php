<?php

/**
 * @package core\hooks
 * @subpackage hooks
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

use Xaraya\Services\WithServicesInterface;
use Xaraya\Services\WithServicesTrait;

/**
 * Hook Observer Interface
 *
 * All Hook Observers must implement this
**/
interface ixarHookObserver extends ixarEventObserver, WithServicesInterface
{
    /**
     * Phpstan complaint about not contravariant
     * @param ixarHookSubject $subject
     */
    public function notify(ixarEventSubject $subject);
}

class HookObserver extends EventObserver implements ixarHookObserver
{
    use WithServicesTrait;

    /** @var string */
    public $module = "modules";
    /** @var string */
    public $type = "admin";
    /** @var ?int */
    public $itemtype = 0;

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
        return $this->type;
    }

    /**
     * Get item type from here
     */
    public function getItemType(): ?int
    {
        return $this->itemtype;
    }

    /**
     * @param ixarHookSubject $subject
     */
    public function notify(ixarEventSubject $subject)
    {
        // make core services available via subject, so that each observer doesn't have to get them
        $this->setServicesClass($subject->getServicesClass());
        // observers obtain arguments from the subject
        $args = $subject->getArgs();
        // observers may, or may not return a response,
        // developers writing observers should return whatever the subject expects
        // get event name from the subject
        //$eventName = $subject->getSubject();
        // get event context from the subject
        //$context = $subject->getContext();
    }

    /**
     * @param array<string, mixed>|mixed $extrainfo
     * @return array<string, mixed>|bool
     * @deprecated 2.4.1 not needed - extrainfo is prepared in HookSubject() constructor
     */
    public function validate($extrainfo = [])
    {
        $xar = $this->getServicesClass();
        // Check whether a valid array was passed
        if (!isset($extrainfo) || !is_array($extrainfo)) {
            $msg = $xar->ml(
                'Invalid #(1) in function #(2)() in module #(3)',
                'extrainfo',
                'updatehook',
                'pubsub'
            );
            throw new Exception($msg);
        }

        // We can use hooks via module/itemtype or object
        if (!isset($extrainfo['module']) && !isset($extrainfo['object'])) {
            $msg = $xar->ml(
                'Missing #(1) in function #(2)() in module #(3)',
                'module or object',
                'updatehook',
                'pubsub'
            );
            throw new Exception($msg);
        }

        // When called via hooks, the module name may be empty, so we get it from
        // the current module
        if (isset($extrainfo['module']) && is_string($extrainfo['module'])) {
            $modname = $extrainfo['module'];
        } else {
            $modname = $xar->req()->getModule();
        }
        $module_id = $xar->mod()->getRegID($modname);
        if (!$module_id) {
            return false; // throw back
        } else {
            $extrainfo['module_id'] = $module_id;
        }

        // If we have an object, we need to get its ID
        if (isset($extrainfo['object']) && is_string($extrainfo['object'])) {
            $item = $xar->data()->getObjectID(['name' => $extrainfo['object']]);
            $extrainfo['object_id'] = (int) $item['objectid'];
        }

        // Assign the itemtype if we don't have one
        if (!isset($extrainfo['itemtype']) || !is_numeric($extrainfo['itemtype'])) {
            $extrainfo['itemtype'] = 0;
        }

        // Assign the url if we don't have one
        if (!isset($extrainfo['url'])) {
            $extrainfo['url'] = '';
        }

        // Check for a category ID
        if (isset($extrainfo['cid']) && is_numeric($extrainfo['cid'])) {
            $cid = $extrainfo['cid'];
        } elseif (isset($extrainfo['cids'][0]) && is_numeric($extrainfo['cids'][0])) {
            // TODO: loop over all categories
            $cid = $extrainfo['cids'][0];
        } else {
            $cid = 1;
        }
        $extrainfo['cid'] = $cid;

        return $extrainfo;
    }

    /**
     * Render output with module template
     * @uses xar::tpl()->module()
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @param ?string $templateName
     * @return string
     */
    public function render(string $funcName, array $tplData = [], ?string $templateName = null)
    {
        // Add standard template variables
        $tplData['module'] ??= $this->getModName();
        $tplData['itemtype'] ??= $this->getItemType();

        $xar = $this->getServicesClass();
        // Pass along the context for xar::tpl()->module() if needed
        $tplData['context'] ??= $xar->getContext();

        // See if we have a special template to apply
        if (!isset($templateName) && isset($tplData['_bl_template'])) {
            $templateName = (string) $tplData['_bl_template'];
        }

        $modName = $this->getModName();
        // @todo Check if we're called from an api $modType and adapt to gui!?
        $modType = $this->getModType();
        if (str_ends_with($modType, 'api')) {
            $modType = substr($modType, 0, -3);
        }

        // Create the output.
        return $xar->tpl()->module(
            $modName,
            $modType,
            $funcName,
            $tplData,
            $templateName,
        );
    }
}
