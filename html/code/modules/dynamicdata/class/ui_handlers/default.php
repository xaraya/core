<?php
/**
 * Dynamic Object User Interface Handler
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

namespace Xaraya\DataObject\Handlers;

use xarObject;
use DataObjectList;
use DataObject;
use sys;

sys::import('xaraya.objects');
sys::import('modules.dynamicdata.class.ui_handlers.servicestrait');

/**
 * Dynamic Object User Interface Handler
 *
 */
class DefaultHandler extends xarObject implements HandlerServicesInterface
{
    use HandlerServicesTrait;

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
        // set core services for access via methods - nothing to do here
        //$this->setCoreServices();

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
            $this->tpltitle = $this->mls()->translate('Dynamic Data Object Interface');
        }

        // get some common URL parameters
        if (!$this->var()->check('object', $args['object'])) {
            return;
        }
        if (!$this->var()->check('name', $args['name'])) {
            return;
        }
        if (!$this->var()->check('module', $args['module'])) {
            return;
        }
        if (!$this->var()->check('itemtype', $args['itemtype'])) {
            return;
        }
        if (!$this->var()->check('table', $args['table'])) {
            return;
        }
        if (!$this->var()->check('layout', $args['layout'])) {
            return;
        }
        if (!$this->var()->check('template', $args['template'])) {
            return;
        }
        if (!$this->var()->check('startnum', $args['startnum'])) {
            return;
        }
        if (!$this->var()->check('numitems', $args['numitems'])) {
            return;
        }

        if (!$this->var()->check('fieldlist', $fieldlist)) {
            return;
        }
        // make fieldlist an array,
        // @todo should the object class do it?
        if (!empty($fieldlist)) {
            $args['fieldlist'] = explode(',', $fieldlist);
        }

        // Default number of items per page in object view
        if (!isset($args['numitems']) && $args['object'] != 'objects') {
            $args['numitems'] = $this->mod()->getVar('items_per_page');
        }

        // support name=... parameter for DD if no object=... is found
        if (empty($args['object']) && !empty($args['name'])) {
            $args['object'] = $args['name'];
        }

        sys::import('modules.dynamicdata.class.objects.factory');

        // retrieve the object information for this object
        if (!empty($args['object'])) {
            $info = $this->data()->getObjectInfo(
                ['name' => $args['object']]
            );
            if (!empty($info)) {
                $args = array_merge($args, $info);
            }
        } elseif (!empty($args['module']) && empty($args['moduleid'])) {
            // @todo is this still actually needed here?
            $args['moduleid'] = $this->mod()->getRegID($args['module']);
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
     * @return string|void output of data()->template() using 'ui_default'
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
                $this->object = $this->data()->getObject($this->args);
            } else {
                $this->object = $this->data()->getObjectList($this->args);
            }
            if (empty($this->object) || (!empty($this->args['object']) && $this->args['object'] != $this->object->name)) {
                $msg = $this->mls()->translate('Object #(1) seems to be unknown', $this->args['object']);
                return $this->ctl()->notFound($msg);
            }

            if (empty($this->tplmodule)) {
                // set in DataObjectDescriptor::getObjectID()
                $this->tplmodule = $this->object->tplmodule;
            }
        } else {
            // set context if available in handler
            $this->object->setContext($this->getContext());
        }

        if (!method_exists($this->object, $this->method)) {
            return $this->mls()->translate('Unknown method #(1) for #(2)', $this->var()->prep($this->method), $this->object->label);
        }

        // Pre-fetch item(s) for some standard dataobject methods
        if (empty($args['itemid']) && $this->method == 'showview' && assert($this->object instanceof DataObjectList)) {
            if (!$this->object->checkAccess('view')) {
                $msg = $this->mls()->translate('View #(1) is forbidden', $this->object->label);
                return $this->ctl()->forbidden($msg);
            }

            $this->object->getItems();
        } elseif (!empty($args['itemid']) && ($this->method == 'showdisplay' || $this->method == 'showform') && assert($this->object instanceof DataObject)) {
            if (!$this->object->checkAccess('display')) {
                $msg = $this->mls()->translate('Display Itemid #(1) of #(2) is forbidden', $this->args['itemid'], $this->object->label);
                return $this->ctl()->forbidden($msg);
            }

            // get the requested item
            $itemid = $this->object->getItem();
            if (empty($itemid) || $itemid != $this->object->itemid) {
                $msg = $this->mls()->translate('Itemid #(1) of #(2) seems to be invalid', $this->args['itemid'], $this->object->label);
                return $this->ctl()->notFound($msg);
            }
        }

        $title = $this->object->label;
        $this->tpl()->setPageTitle($this->var()->prep($title));

        // Here we try to run the requested method directly
        $output = $this->object->{$this->method}($this->args);

        // CHECKME: do we redirect to return_url or nextmethod in some cases here too ?

        // add data to original method args
        $data = array_replace($args, [
            'object'   => $this->object,
            'context'  => $this->getContext(),
            'output'   => $output,
            'tpltitle' => $this->tpltitle,
        ]);

        return $this->data()->template(
            'ui_default',
            $data
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
            $return_url = $this->ctl()->getObjectURL($this->object->name, $this->nextmethod, ['itemid' => $this->object->itemid]);
        } else {
            $return_url = $this->ctl()->getObjectURL($this->object->name, $this->nextmethod);
        }

        return $return_url;
    }
}
