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

use Xaraya\DataObject\Handlers\DefaultHandler;
use Xaraya\Context\Context;

/**
  * Simple Object Interface
  */
class SimpleObjectInterface extends DefaultHandler
{
    public function __construct(array $args = [], ?Context $context = null, $xar = null)
    {
        parent::__construct($args, $context, $xar);
        $xar = $this->getStaticServices();
        $xar->var()->check('tplmodule', $args['tplmodule'], 'isset', 'dynamicdata');

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
        $xar = $this->getStaticServices();
        // set the context before checking any variables
        $this->setContext($context);
        $xar->var()->check('method', $args['method'], 'str', 'showDisplay');
        $xar->var()->check('itemid', $args['itemid'], 'id', null);
        // @todo maybe this should be done somewhere else ?
        $xar->var()->find('qparam', $qparam, 'str', null);
        $xar->var()->find('qstring', $qstring, 'str', null);

        if (!empty($qparam) && !empty($qstring)) {
            $args['where'] = "$qparam LIKE '$qstring%'";
        }
        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        // set context if available in handler
        $this->object = $xar->data()->getObjectList($this->args);
        if (method_exists($this->object, $this->args['method'])) {
            $this->object->getItems();
        } else {
            $this->object = $xar->data()->getObject($this->args);
        }
        $this->context?->tracePath(__METHOD__ . ': ' . $this->object->name, $this->args);

        if (empty($this->object)) {
            return;
        }

        return $this->object->{$this->args['method']}($this->args);
    }
}
