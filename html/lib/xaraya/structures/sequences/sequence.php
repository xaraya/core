<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Sequence datastructure implementation
 *
 * A sequence is an ordered, yet unsorted linear
 * list of items. Items can be inserted and deleted at specified
 * positions. This general linear list can be further specialised
 * to implement stacks, queues, deques or other special linear lists. 
 * 
 */
sys::import('xaraya.structures.sequences.interfaces');
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
    public function insert($item, $position) 
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
    public function load($seq) 
    {
        return parent::load($seq);
    }
}
?>