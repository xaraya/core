<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author Johnny Robeson <johnny@localmomentum.net>
 */

sys::import('modules.dynamicdata.class.ui_handlers.default');
sys::import("xaraya.context.context");
use Xaraya\DataObject\Handlers\DefaultHandler;
use Xaraya\Context\Context;

/**
  * Simple Object Interface
  */
class SimpleObjectInterface extends DefaultHandler
{
    public function __construct(array $args = [])
    {
        parent::__construct($args);
        if (!xarVar::fetch('tplmodule', 'isset', $args['tplmodule'], 'dynamicdata', xarVar::NOT_REQUIRED)) {
            return;
        }

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
    }

    /**
     * Summary of handle
     * @param array<string, mixed> $args
     * @param ?Context<string, mixed> $context optional context for the handler call (default = none)
     * @return mixed
     */
    public function handle(array $args = [], ?Context $context = null)
    {
        if (!xarVar::fetch('method', 'str', $args['method'], 'showDisplay', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'id', $args['itemid'], null, xarVar::DONT_SET)) {
            return;
        }
        // @todo maybe this should be done somewhere else ?
        if (!xarVar::fetch('qparam', 'str', $qparam, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('qstring', 'str', $qstring, null, xarVar::DONT_SET)) {
            return;
        }
        // set the context for this handler call
        $this->setContext($context);

        if (!empty($qparam) && !empty($qstring)) {
            $args['where'] = "$qparam LIKE '$qstring%'";
        }
        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        // set context if available in handler
        $this->object = DataObjectFactory::getObjectList($this->args, $this->getContext());
        if (method_exists($this->object, $this->args['method'])) {
            $this->object->getItems();
        } else {
            $this->object = DataObjectFactory::getObject($this->args, $this->getContext());
        }

        if (empty($this->object)) {
            return;
        }

        return $this->object->{$this->args['method']}($this->args);
    }
}
