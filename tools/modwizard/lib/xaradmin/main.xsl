<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaradmin_main">

    <xsl:message>      * xaradmin/main.php</xsl:message>

    <xsl:document href="{$output}/xaradmin/main.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/main.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_main_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================

     TEMPLATE xaradmin_main ( xaraya_module )

     Create the <module>_admin_main() function.

-->
<xsl:template mode="xaradmin_main_func" match="xaraya_module">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >=1">
/*
 * The main ( default ) administration view.
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_admin_main() 
{
    if (!xarSecurityCheck( 'Edit<xsl:value-of select="$module_prefix" />')) return;

    // Check if we should show the overview page <xsl:if test="$gCommentsLevel >=2">
    // The admin system looks for a var to be set to skip the introduction
    // page altogether.  This allows you to add sparse documentation about the
    // module, and allow the site admins to turn it on and off as they see fit. </xsl:if>
    if (xarModVars::Get('adminpanels', 'overview') == 0) {

        // Yes we should
        $data = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'private'
            ,'common'
            ,array(
                'title' => xarML( 'Overview' )
                ,'type' => 'admin'
                ));
        return $data;
    }

    // No we shouldn't. So we redirect to the admin_view() function.
    return xarResponseRedirect(
        xarModURL(
            '<xsl:value-of select="registry/name" />'
            ,'admin'
            ,'view' ));

}
</xsl:template>

</xsl:stylesheet>
