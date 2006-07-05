<?php
/**
 * Sequence datastructure implementation
 *
 * A sequence is an ordered, yet unsorted linear
 * list of items. Items can be inserted and deleted at specified
 * positions. This general linear list can be further specialised
 * to implement stacks, queues, deques or other special linear lists. 
 * 
 */
include_once dirname(__FILE__).'/interfaces.php';
class Sequence extends SequenceAdapter implements iSequence
{
    /* 
     We just have to make the methods public, as a 
     sequence doesnt have to be adapted to a sequence ;-)
    */
    public function &get($position) 
    {
        return parent::get($position);
    }
    public function insert(&$item, $position) 
    {
        return parent::insert($item, $position);
    }
    public function delete($position) 
    {
        return parent::delete($position);
    }
    public function clear() 
    {
        return parent::clear();
    }
}
?>
