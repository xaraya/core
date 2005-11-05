<?php

/**
* xarTpl__XarVarNode: <xar:var> tag class
 *
 *
 * @package blocklayout
 */
class xarTpl__XarVarNode extends xarTpl__TplTagNode
{
    function render()
    {
        $scope = 'local';
        $prep = false;
        $user = xarUserGetVar('uid');
        extract($this->attributes);
        
        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:var> tag.', $this);
            return;
        }
        
        $prefix = ''; $postfix = '';
        if(strtolower($prep) == 'true') {
            $prep = true;
            $prefix = "xarVarPrepForDisplay(";
            $postfix = ")";
        }
        
        // Allow specifying name="test" and name="$test" and deprecate the $ form over time
        if(substr($name,0,1) == XAR_TOKEN_VAR_START) $name = substr($name,1);
        
        switch ($scope) {
            case 'config':
                $value = "xarConfigGetVar('".$name."')";
                break;
            case 'session':
                $value = "xarSessionGetVar('".$name."')";
                break;
            case 'user':
                $user = xarTpl__ExpressionTransformer::transformPHPExpression($user);
                $value = "xarUserGetVar('".$name."',".$user.")";
                break;
            case 'module':
                if (!isset($module)) {
                    $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'module\' attribute in <xar:var> tag.', $this);
                    return;
                }
                $value = "xarModGetVar('".$module."', '".$name."')";
                break;
            case 'theme':
                if (!isset($themeName)) {
                    $themeName = xarModGetVar('themes', 'default');
                }
                $value = "xarThemeGetVar('".$themeName."', '".$name."')";
                break;
            case 'local':
                // Resolve the name, note that this works for both name="test" and name="$test"
                $value = xarTpl__ExpressionTransformer::transformPHPExpression(XAR_TOKEN_VAR_START . $name);
                if (!isset($value)) return; // throw back
                    break;
            default:
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid value for \'scope\' attribute in <xar:var> tag.', $this);
                return;
        }
        return $prefix . $value . $postfix;
    }
    
    function needExceptionsControl()
    {
        if (!isset($this->attributes['scope'])) {
            return false;
        }
        return ($this->attributes['scope'] == 'module' ||
                $this->attributes['scope'] == 'config' ||
                $this->attributes['scope'] == 'user');
    }
}
?>
