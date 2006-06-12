<?php

include_once dirname(__FILE__).'/interfaces.php';
include_once dirname(__FILE__).'/adapters/sequence_adapter.php';

/**
 * A stack manipulates only the item at the head of the sequence
 *
 */
class Stack extends SequenceAdapter implements iStack
{
    public function push($item)
    {
        return $this->insert($item, $this->head);
    }

    public function &pop()
    {
        $item = null;
        if($this->empty) return $item;
        $item = parent::get($this->head);
        if($item == null) return $item;
        parent::delete($this->head);
        return $item;
    }
    
    public function clear()
    {
        parent::clear();
    }
}
?>