<?php
/**
 * xarTpl__XarModuleNode: <xar:module> tag class
 *
 * This is used in <xar:module main="true" /> as placeholder for the main module output,
 * or in <xar:module main="false" module="mymodule" type="mytype" func="myfunc" args="$args" />
 * or <xar:module main="false" module="mymodule" type="mytype" func="$somefunc" numitems="10" whatever="$this" ... />
 * to insert the result of another module function call in a template...
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModuleNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);
        
        if (!isset($main)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'main\' attribute in <xar:module> tag.');
            return;
        }
        
        if (empty($module)) {
            return '$_bl_mainModuleOutput';
        } else {
            // CHECKME: check attribute handling
            $args = $this->attributes;
            unset($args['main']);
            unset($args['module']);
            $module = xarTpl__ExpressionTransformer::transformPHPExpression($module);
            if (!empty($type)) {
                $type = xarTpl__ExpressionTransformer::transformPHPExpression($type);
                unset($args['type']);
            } else {
                $type = 'user';
            }
            if (!empty($func)) {
                $func = xarTpl__ExpressionTransformer::transformPHPExpression($func);
                unset($args['func']);
            } else {
                $func = 'main';
            }
            // TODO: improve handling of extra arguments if necessary
            if (isset($args['args']) && substr($args['args'],0,1) == XAR_TOKEN_VAR_START) {
                return 'xarModFunc("'.$module.'", "'.$type.'", "'.$func.'", '.$args['args'].')';
            } elseif (count($args) > 0) {
                $out = 'xarModFunc("'.$module.'", "'.$type.'", "'.$func.'", array(';
                                                                                  foreach ($args as $key => $val) {
                                                                                      $out .= "'$key' => ";
                                                                                      if (substr($val,0,1) == XAR_TOKEN_VAR_START) {
                                                                                          $out .= $val . ', ';
                                                                                      } else {
                                                                                          $out .= "'$val', ";
                                                                                      }
                                                                                  }
                                                                                  $out = substr($out,0,-2) . '))';
                return $out;
            } else {
                return 'xarModFunc("'.$module.'", "'.$type.'", "'.$func.'")';
            }
        }
    }
}
?>