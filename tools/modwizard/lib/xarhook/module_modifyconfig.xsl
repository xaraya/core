<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xarhook_module_modifyconfig" xml:space="default">

    <xsl:message>      * module_modifyconfig()</xsl:message>

    <xsl:document href="{$output}/xarhook/module_modifyconfig.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xarhook/module_modifyconfig.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xarhook_module_modifyconfig_func" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================
     TEMPLATE FOR <module>_hook_module_modifyconfig()
-->
<xsl:template match="xaraya_module" mode="xarhook_module_modifyconfig_func">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >= 1">
/**
 * Utility function to pass individual menu items to the main menu.
 *
 * This function is invoked by the core to retrieve the items for the
 * usermenu.
 *
 * @returns array
 * @return  array containing the menulinks for the main menu items
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_hook_module_modifyconfig ( $args ) 
{
    extract( $args );

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','module name', 'admin', 'module_modifyconfig', '<xsl:value-of select="$module_prefix" />');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    return xarTplModule(
        '<xsl:value-of select="$module_prefix" />'
        ,'hook'
        ,'module_modifyconfig'
        ,array()
        );

}
</xsl:template>

<!-- END OF FILE -->
</xsl:stylesheet>
