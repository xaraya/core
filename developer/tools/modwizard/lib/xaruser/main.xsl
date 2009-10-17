<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xaruser_main" xml:space="default">

    <xsl:message>      * xaruser/main.php</xsl:message>

    <xsl:document href="{$output}/xaruser/main.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruser/main</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xaruser_main_func" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<xsl:template mode="xaruser_main_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
function <xsl:value-of select="$module_prefix" />_user_main() 
{
    // Security Check <xsl:if test="$gCommentsLevel >=2">
    // It is important to do this as early as possible to avoid potential
    // security holes or just too much wasted processing.  For the main
    // function we want to check that the user has at least edit privilege for
    // some item within this component, or else they won't be able to do
    // anything and so we refuse access altogether.  The lowest level of
    // access for administration depends on the particular module, but it is
    // generally either 'edit' or 'delete'. </xsl:if>
    if (!xarSecurityCheck( 'View<xsl:value-of select="$module_prefix" />')) return;

    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' =>  xarML( 'Splash Page' )));

    return $data;
}
</xsl:template>

</xsl:stylesheet>
