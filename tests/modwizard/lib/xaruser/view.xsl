<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruser_view" xml:space="default">

    <xsl:message>      * xaruser/view.php</xsl:message>

    <xsl:document href="{$output}/xaruser/view.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruser/view</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xaruser_view_func" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<xsl:template mode="xaruser_view_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Standard interface for view item lists
 *
 * This is a generic display() function for DD handled itemtype's. The
 * itemtype specific parts are separated to a function
 * userpriv_viewtable().
 *
 */
function <xsl:value-of select="$module_prefix" />_user_view( $args ) {

    $itemtype = xarVarCleanFromInput( 'itemtype' );
    extract( $args );

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@user='true']">
        case <xsl:value-of select="@itemtype" />:
            $data = xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'view'
                , $args );
            $itemtype_name = '<xsl:value-of select="@name" />';
            break;
    </xsl:for-each>

        default:
            xarSessionSetVar(
                '<xsl:value-of select="$module_prefix" />_statusmsg'
                ,xarML( 'Error: Itemtype not specified or invalid. Redirected you to main page!' ) );

            xarResponseRedirect(
                xarModURL(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'user'
                    ,'main' ));
    }

    return xarTplModule(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'view'
        ,$data
        ,$itemtype_name );



}
</xsl:template>

</xsl:stylesheet>
