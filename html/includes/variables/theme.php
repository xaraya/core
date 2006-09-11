<?php

/**
 * Interface declaration for theme vars
 *
 */
sys::import('variables');
interface IxarThemeVars extends IxarVars
{}

sys::import('variables.module');
class xarThemeVars implements IxarThemeVars
{
    /**
     * get a theme variable
     *
     * @access public
     * @param  string $scope The name of the theme
     * @param  sting  $name  The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     */
    public static function get($scope, $name)
    {
        if (empty($scope)) throw new EmptyParameterException('themename');

        $itemid = xarThemeGetIDFromName($scope,'systemid');
        $modVarName = $scope . '_' . $name;
        return xarVar__GetVarByAlias('themes', $modVarName, $itemid, null, $type = 'moditemvar');
    }
    
    /**
     * set a theme variable
     *
     * Note that this method is incompatible with 1.x even if wrapped.
     * the prime/description parameters were dropped from the signature.
     * 
     * @access public
     * @param themeName The name of the theme
     * @param name The name of the variable
     * @param value The value of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     *
     */
    public static function set($scope, $name, $value)
    {
        if (empty($scope)) throw new EmptyParameterException('themename');

        $itemid = xarThemeGetIDFromName($scope,'systemid');
        $modVarName = $scope . '_' . $name;
        // Make sure we set it as modvar first
        // TODO: this sucks
        if(!xarModVars::get('themes',$modVarName)) {
            xarModVars::set('themes',$modVarName,$value);
        }
        return xarVar__SetVarByAlias('themes', $modVarName, $value, null, '', $itemid, $type = 'moditemvar');
    }

    /**
     * delete a theme variable
     *
     * @access public
     * @param themeName The name of the theme
     * @param name The name of the variable
     * @return bool true on success
     * @throws EmptyParameterException
     */
    public static function delete($scope, $name)
    {
        if (empty($scope)) throw new EmptyParameterException('themename');

        $itemid = xarThemeGetIDFromName($scope,'systemid');
        $modVarName = $scope . '_' . $name;
        return xarVar__DelVarByAlias('themes', $modVarName, $itemid, $type = 'moditemvar');
    }

}
?>