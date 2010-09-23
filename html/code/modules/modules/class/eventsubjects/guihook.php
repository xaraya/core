<?php
/**
 * GuiHook Subject
 *
 * Handles gui type hook observers (these typically return string of template data)
 *
 * NOTE: this class is never called directly, but should be extended
 * by gui type hook subjects. Hook subjects should only need to extend this
 * class and overload the $subject property with their event subject name,
 * the inherited methods will take care of the rest
**/
/**
 * GUI type hook, observers should return string template data
**/
sys::import('modules.modules.class.eventsubjects.hook');
abstract class ModulesGuiHookSubject extends ModulesHookSubject
{
    public $subject = 'GuiHook';  // change this to the name of your event subject

    protected $hookoutput = array(); // property to store array of hooked module responses 

    /**
     * Notify hooked observers
     * @todo: it shouldn't be necessary to overload this method, make it final?
     *
     * @params none
     * @throws none
     * @return array of cumulative responses from observers
    **/    
    public function notify()
    {
        foreach ($this->observers as $obs) {
            // @TODO: wrap this in a try / catch clause, hooks shouldn't fail, ever! 
            // notify observer and store response in hookoutput property keyed by hook module name
            $this->hookoutput[$obs->module] = $obs->notify($this);
        }
        // return array of hooked responses 
        return $this->hookoutput;
    }
}
?>