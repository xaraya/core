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

use Xaraya\Services\ServicesInterface;
use Xaraya\Services\ServicesTrait;

/**
 * Hook Observer Interface
 *
 * All Hook Observers must implement this
**/
interface ixarHookObserver extends ixarEventObserver
{
    /**
     * Phpstan complaint about not contravariant
     * @param ixarHookSubject $subject
     */
    public function notify(ixarEventSubject $subject);
}

class HookObserver extends EventObserver implements ixarHookObserver, ServicesInterface
{
    use ServicesTrait;

    /** @var string */
    public $module = "modules";
    /** @var string */
    public $type = "admin";
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
     * @param array<string, mixed>|mixed $extrainfo
     * @return array<string, mixed>|bool
     */
    public function validate($extrainfo = [])
    {
        // Check whether a valid array was passed
        if (!isset($extrainfo) || !is_array($extrainfo)) {
            $msg = $this->ml(
                'Invalid #(1) in function #(2)() in module #(3)',
                'extrainfo',
                'updatehook',
                'pubsub'
            );
            throw new Exception($msg);
        }

        // We can use hooks via module/itemtype or object
        if (!isset($extrainfo['module']) && !isset($extrainfo['object'])) {
            $msg = $this->ml(
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
            $modname = $this->mod()->getName();
        }
        $module_id = $this->mod()->getRegID($modname);
        if (!$module_id) {
            return false; // throw back
        } else {
            $extrainfo['module_id'] = $module_id;
        }

        // If we have an object, we need to get its ID
        if (isset($extrainfo['object']) && is_string($extrainfo['object'])) {
            $item = $this->data()->getObjectID(['name' => $extrainfo['object']]);
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
        // Pass along the context for xar::tpl()->module() if needed
        $tplData['context'] ??= $this->getContext();

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
        return $this->tpl()->module(
            $modName,
            $modType,
            $funcName,
            $tplData,
            $templateName,
        );
    }
}
