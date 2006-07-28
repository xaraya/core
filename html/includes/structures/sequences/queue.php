<?php
sys::import('structures.sequences.interfaces');
sys::import('structures.sequences.adapters.sequence_adapter');

/**
 * A queue inserts at the head of the sequence and
 * deletes at the tail of the sequence
 *
 * This means it only differs from a stack in the pop part
 */
class Queue extends SequenceAdapter implements iQueue
{
    public function &pop()
    {
        $item = null;
        if($this->empty) return $item;
        $item = parent::get($this->tail);
        if($item == null) return $item;
        parent::delete($this->tail);
        return $item;
    }
    
    public function push($item)
    {
        return parent::insert($item, $this->head);
    }
    
    public function clear()
    {
        parent::clear();
    }
}
?>
