<?php
/**
 * Dynamic Object User Interface Handler
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Handlers;

use Xaraya\Core\Traits\ContextInterface;
use Xaraya\Core\Traits\ContextTrait;
use xarObject;
use xarVar;
use xarMLS;
use xarMod;
use xarModVars;
use xarResponse;
use xarTpl;
use xarDDObject;
use DataObjectFactory;
use DataObjectList;
use DataObject;
use sys;

sys::import('xaraya.objects');
sys::import('xaraya.traits.contexttrait');

/**
 * Dynamic Object User Interface Handler
 *
 */
class DefaultHandler extends xarObject implements ContextInterface
{
    use ContextTrait;

    public string $method = 'overridden in child classes';

    // module where the main templates for the GUI reside (defaults to the object module)
    public ?string $tplmodule = null;
    // main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
    public string $linktype = 'object';
    // main function handling all object method calls (= if we're not using object URLs)
    /** @var string|callable */
    public $linkfunc = 'main';
    // default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
    public string $nextmethod = 'view';
    // title shown in the main templates
    public ?string $tpltitle = null;

    // current arguments for the handler
    /** @var ?array<string, mixed> */
    public $args = [];

    /** @var DataObjectList|DataObject|null */
    public $object = null;

    /**
     * Default constructor for all handlers - get common input arguments for objects
     *
     * @param array<string, mixed> $args
     * with
     *     $args['tplmodule'] module where the main templates for the GUI reside (defaults to the object module)
     *     $args['linktype'] main type of function handling all object method calls (= 'object' or 'user' [+ 'admin'] GUI)
     *     $args['linkfunc'] main function handling all object method calls (= if we're not using object URLs)
     *     $args['nextmethod'] default next method to redirect to after create/update/delete/yourstuff/etc. (defaults to 'view')
     *     $args any other arguments we want to pass to DataObjectFactory::getObject() or ::getObjectList() later on
     */
    public function __construct(array $args = [])
    {
        // set a specific GUI module for now
        if (!empty($args['tplmodule'])) {
            $this->tplmodule = $args['tplmodule'];
        }
        // specify the link type
        if (!empty($args['linktype'])) {
            $this->linktype = $args['linktype'];
        } else {
            $args['linktype'] = $this->linktype;
        }
        // specify the link function if relevant
        if (!empty($args['linkfunc'])) {
            $this->linkfunc = $args['linkfunc'];
        } else {
            $args['linkfunc'] = $this->linkfunc;
        }
        if (!empty($args['nextmethod'])) {
            $this->nextmethod = $args['nextmethod'];
        }
        if (!empty($args['tpltitle'])) {
            $this->tpltitle = $args['tpltitle'];
        }
        if (empty($this->tpltitle)) {
            $this->tpltitle = xarMLS::translate('Dynamic Data Object Interface');
        }

        // get some common URL parameters
        if (!xarVar::fetch('object', 'isset', $args['object'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('name', 'isset', $args['name'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('module', 'isset', $args['module'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'isset', $args['itemtype'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('table', 'isset', $args['table'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('layout', 'isset', $args['layout'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('template', 'isset', $args['template'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('startnum', 'isset', $args['startnum'], null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('numitems', 'isset', $args['numitems'], null, xarVar::DONT_SET)) {
            return;
        }

        if (!xarVar::fetch('fieldlist', 'isset', $fieldlist, null, xarVar::DONT_SET)) {
            return;
        }
        // make fieldlist an array,
        // @todo should the object class do it?
        if (!empty($fieldlist)) {
            $args['fieldlist'] = explode(',', $fieldlist);
        }

        // Default number of items per page in object view
        if (!isset($args['numitems']) && $args['object'] != 'objects') {
            $args['numitems'] = xarModVars::get('dynamicdata', 'items_per_page');
        }

        // support name=... parameter for DD if no object=... is found
        if (empty($args['object']) && !empty($args['name'])) {
            $args['object'] = $args['name'];
        }

        sys::import('modules.dynamicdata.class.objects.factory');

        // retrieve the object information for this object
        if (!empty($args['object'])) {
            $info = DataObjectFactory::getObjectInfo(
                ['name' => $args['object']]
            );
            if (!empty($info)) {
                $args = array_merge($args, $info);
            }
        } elseif (!empty($args['module']) && empty($args['moduleid'])) {
            $args['moduleid'] = xarMod::getRegID($args['module']);
        }

        if (empty($args['layout'])) {
            $args['layout'] = 'default';
        }

        // save the arguments for the handler (= used to initialize the object there)
        $this->args = $args;
    }

    /**
     * Run some other unknown ui method, or call some object/objectlist method directly
     *
     * @param array<string, mixed> $args
     * with
     *     $args['method'] the ui method we are handling here
     *     $args['itemid'] item id of the object to call the method for, if the method needs it
     *     $args any other arguments we want to pass to DataObjectFactory::getObject() or ::getObjectList()
     * @return string|void output of xarTpl::object() using 'ui_default'
     */
    public function run(array $args = [])
    {
        // This method is overridden in a child class for standard GUI methods

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }

        $this->method = $this->args['method'];

        if (!isset($this->object)) {
            // set context if available in handler
            if (!empty($this->args['itemid'])) {
                $this->object = DataObjectFactory::getObject($this->args, $this->getContext());
            } else {
                $this->object = DataObjectFactory::getObjectList($this->args, $this->getContext());
            }
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                return xarResponse::NotFound(xarMLS::translate('Object #(1) seems to be unknown', $this->args['object']));
            }

            if (empty($this->tplmodule)) {
                $modname = xarMod::getName($this->object->moduleid);
                $this->tplmodule = $modname;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }

        if (!method_exists($this->object, $this->method)) {
            return xarMLS::translate('Unknown method #(1) for #(2)', xarVar::prepForDisplay($this->method), $this->object->label);
        }

        // Pre-fetch item(s) for some standard dataobject methods
        if (empty($args['itemid']) && $this->method == 'showview') {
            if (!$this->object->checkAccess('view')) {
                $this->getContext()?->setStatus(403);
                return xarResponse::Forbidden(xarMLS::translate('View #(1) is forbidden', $this->object->label));
            }

            $this->object->getItems();
        } elseif (!empty($args['itemid']) && ($this->method == 'showdisplay' || $this->method == 'showform')) {
            if (!$this->object->checkAccess('display')) {
                $this->getContext()?->setStatus(403);
                return xarResponse::Forbidden(xarMLS::translate('Display Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label));
            }

            // get the requested item
            $itemid = $this->object->getItem();
            if (empty($itemid) || $itemid != $this->object->itemid) {
                return xarResponse::NotFound(xarMLS::translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label));
            }
        }

        $title = $this->object->label;
        xarTpl::setPageTitle(xarVar::prepForDisplay($title));

        // Here we try to run the requested method directly
        $output = $this->object->{$this->method}($this->args);

        // CHECKME: do we redirect to return_url or nextmethod in some cases here too ?

        return xarTpl::object(
            $this->tplmodule,
            $this->object->template,
            'ui_default',
            ['object'   => $this->object,
             'context'  => $this->getContext(),
             'output'   => $output,
             'tpltitle' => $this->tpltitle]
        );
    }

    /**
     * Check if we want a subset of fields here (projection)
     * @return void
     */
    public function checkFieldList()
    {
        // index.php?object=mongodb_properties&method=display&itemid=4&fieldlist=name,configuration.display_layout,configuration.initialization_refobject
        $fieldsubset = [];
        if (!empty($this->args['fieldlist'])) {
            if (!is_array($this->args['fieldlist'])) {
                $this->args['fieldlist'] = array_filter(explode(',', $this->args['fieldlist']));
            }
            $cleanfields = [];
            foreach($this->args['fieldlist'] as $field) {
                if (str_contains($field, '.')) {
                    [$field, $subset] = explode('.', $field, 2);
                    $fieldsubset[$field] ??= [];
                    $fieldsubset[$field][] = $subset;
                }
                if (!in_array($field, $cleanfields)) {
                    $cleanfields[] = $field;
                }
            }
            $this->args['fieldsubset'] = $fieldsubset;
            $this->args['fieldlist'] = $cleanfields;
        }
    }

    /**
     * Get the return URL (based on argument or handler settings)
     *
     * @param string $return_url any $args['return_url'] given by the method
     * @return string the return url
     */
    public function getReturnURL($return_url = '')
    {
        // if we already have a return_url, use that
        if (!empty($return_url)) {
            return $return_url;
        }

        if (isset($this->object->itemid)) {
            $return_url = xarDDObject::getActionURL($this->object, $this->nextmethod, $this->object->itemid);
        } else {
            $return_url = xarDDObject::getActionURL($this->object, $this->nextmethod);
        }

        return $return_url;
    }
}
