<?php

/**
 * Interface declaration for theme vars
 *
 */
sys::import('xaraya.variables');
sys::import('xaraya.variables.moditem');

class xarThemeVars extends xarModItemVars implements IxarModItemVars
{
    /**
     * get a theme variable
     *
     * @access public
     * @param  string $scope The name of the theme
     * @param  string $name  The name of the variable
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     * @todo the silent spec of itemid is a bit hacky
     */
    public static function get($scope, $name, $itemid = null)
    {
        if (empty($scope)) throw new EmptyParameterException('themename');

        $itemid = xarThemeGetIDFromName($scope,'systemid');
        return parent::get('themes', $name, $itemid);
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
    public static function set($scope, $name, $value, $itemid = null)
    {
        if (empty($scope)) throw new EmptyParameterException('themename');

        $itemid = xarThemeGetIDFromName($scope,'systemid');
        $varname = $scope . '_' . $name; // bah
        // Make sure we set it as modvar first
        // TODO: this sucks
        if(!xarModVars::get('themes',$varname)) {
            xarModVars::set('themes',$varname,$value);
        }
        return parent::set('themes', $varname, $value, $itemid);
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
    public static function delete($scope, $name, $itemid = null)
    {
        if (empty($scope)) throw new EmptyParameterException('themename');

        $itemid = xarThemeGetIDFromName($scope,'systemid');
        $varname = $scope . '_' . $name;
        return parent::delete($scope, $varname, $itemid);
    }
}
?>
