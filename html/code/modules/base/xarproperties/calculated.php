<?php
/**
 * @package modules
 * @subpackage base module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle floatbox property
 */
class CalculatedProperty extends TextBoxProperty
{
    public $id         = 3030;
    public $name       = 'calculated';
    public $desc       = 'Calculated Field';
    public $source     = 'dummy';            // default source is dummy here

    // sample calculation rule in initialization_other_rule
    public $initialization_other_rule = '( price * quantity + 5 ) / 0.95';

    public $display_size      = 10;
    public $display_maxlength = 30;
    public $display_tooltip   = 'Calculated Value: ';

    public $calculation       = null;
    public $calcfunction      = null;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        // we want a reference to the object here
        $this->include_reference = 1;

    // CHECKME: force using the dummy datastore for this property
        $this->source = 'dummy';

    // CHECKME: force the no input status for this property
        $this->status = $this->getDisplayStatus() + DataPropertyMaster::DD_INPUTSTATE_NOINPUT;

        // get the calculation rule from initialization_other_rule
        if (empty($this->calculation)) $this->calculation = $this->initialization_other_rule;
        $this->display_tooltip .= $this->calculation;
    }

    public function showInput(Array $data = array())
    {
        $data['value'] = $this->calculateValue($data);
    // CHECKME: see no input status above
        return parent::showInput($data);
    }

    public function showOutput(Array $args = array())
    {
        // the dummy datastore will use the itemid as value for this property !
        $args['value'] = $this->calculateValue($args);
        return parent::showOutput($args);
    }

    private function calculateValue($args)
    {
        if (empty($this->calculation)) {
            // nothing to calculate
            return;

        } elseif (empty($this->calcfunction) && !$this->parseCalculation()) {
            // calculation rule does not parse correctly
            return "Error parsing '$this->calculation'";
        }

        // assign the lambda function to a local variable - $this->calcfunction($item) won't work
        $func = $this->calcfunction;

        // we're dealing with a single item here, so check the objectref properties
        if (!empty($this->_itemid) && !empty($this->objectref)) {
            $item = array();
            foreach (array_keys($this->objectref->properties) as $name) {
                $item[$name] = $this->objectref->properties[$name]->value;
            }
            try {
                $result = $func($item);
            } catch (Exception $e) {
                return;
            }
            return $result;

        // the dummy datastore will use the itemid as value for this property
        } elseif (!empty($this->_items) && isset($args['value']) && !empty($this->_items[$args['value']])) {
            try {
                $result = $func($this->_items[$args['value']]);
            } catch (Exception $e) {
                return;
            }
            return $result;
        }
    }

    /**
     * Parse calculation rule, e.g. ( price * quantity + 5 ) / 0.95
     */
    private function parseCalculation()
    {
        if (empty($this->calculation)) {
            return true;
        }

        // get available property names
        $propnames = array_keys($this->objectref->properties);

        // list arithmetic operators and parenthesis
        $operators = array('+', '-', '*', '/', '%', '(', ')');

        // split on operators, and return the operators too
        $parts = preg_split('/\s+(\+|\-|\*|\/|\%|\(|\))\s+/',$this->calculation,-1,PREG_SPLIT_DELIM_CAPTURE);

        $pieces = array();
        foreach ($parts as $part) {
            if (in_array($part, $operators)) {
                // we have an operator
                $pieces[] = $part;
            } elseif (is_numeric($part)) {
                // we have a number
                $pieces[] = $part;
            } elseif (in_array($part, $propnames)) {
                // we have a property name
                $pieces[] = '$item["' . $part . '"]';
            } else {
                // oops, we have something else
                return false;
            }
        }

        // create a function to handle the calculation for an $item
        try {
            $this->calcfunction = create_function('$item', 'return ' . implode(' ', $pieces) . ';');
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}

?>
