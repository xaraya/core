<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xarprivateapi_adminconfigmenu">

    <xsl:message>      * xarprivateapi/adminconfigmenu.php</xsl:message>

    <xsl:document href="{$output}/xarprivateapi/adminconfigmenu.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xarprivateapi/adminconfigmenu.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xarprivateapi_adminconfigmenu_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- ========================================================================

        MODE: xarprivateapi_adminconfigmenu             MATCH: xaraya_module

-->
<xsl:template mode="xarprivateapi_adminconfigmenu_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Create a little submenu for the configuration screen.
 */
function <xsl:value-of select="$module_prefix" />_privateapi_adminconfigmenu( $itemtype ) {

    /*
     * Build the configuration submenu
     */
    $menu = array();
    $menu[0] = array(
            'title' =>  xarML( 'Config' ),
            'url'   =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />',
                'admin',
                'config' ));

    <xsl:for-each select="database/table[@admin='true']">
    $menu[<xsl:value-of select="@itemtype" />] = array(
            'title' =>  xarML( '<xsl:value-of select="label/text()" />' ),
            'url'   =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />',
                'admin',
                'config'
                ,array( 'itemtype' => '<xsl:value-of select="@itemtype" />' )));
    </xsl:for-each>

    $menu[$itemtype]['url'] = "";

    return $menu;

}
</xsl:template>

</xsl:stylesheet>
