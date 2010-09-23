<?php
/**
 * ApiHook Subject
 *
 * Handles api type hook observers (these typically return array of $extrainfo)
 *
 * NOTE: this class is never called directly, but should be extended
 * by api type hook subjects. Hook subjects should only need to extend this
 * class and overload the $subject property with their event subject name,
 * the inherited methods will take care of the rest
**/
/**
 * API type hook, observers should return array of $extrainfo
**/
sys::import('modules.modules.class.eventsubjects.hook');
abstract class ModulesApiHookSubject extends ModulesHookSubject
{
    public $subject = 'ApiHook';  // change this to the name of your event subject
    
    /**
     * Notify hooked observers
     * @todo: it shouldn't be necessary to overload this method, make it final?
     *
     * @params none
     * @throws none
     * @return array of cumulative extrainfo from observers
    **/
    public function notify()
    {
        foreach ($this->observers as $obs) {
            // @TODO: wrap this in a try / catch clause, hooks shouldn't fail, ever! 
            // notify observer and capture response 
            $extrainfo = $obs->notify($this);
            // we expect an array of extrainfo from each observer
            if (!empty($extrainfo) && is_array($extrainfo)) {
                // update extrainfo for next observer 
                $this->setArgs(array('extrainfo' => $extrainfo));
            } 
        }
        return $this->args['extrainfo'];
    }
}
?>