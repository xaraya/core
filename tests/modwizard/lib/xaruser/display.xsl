<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xaruser_display" xml:space="default">

    <xsl:message>      * xaruser/display.php</xsl:message>

    <xsl:document href="{$output}/xaruser/display.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruser/display</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xaruser_display_func" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- FUNCTION module_admin_display() -->
<xsl:template mode="xaruser_display_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Standard interface for displaying objects
 *
 * This is a generic display() function for DD handled itemtype's. The
 * itemtype specific parts are separated to a function
 * userpriv_displaytable().
 *
 * <xsl:if test="$gCommentsLevel >= 20">
 * This function has to deal with some special events.
 *
 * dynamic data
 * ============
 *
 * The dynamic data module calls this function to display a object. The
 * following informations are provided
 *      'itemtype'  =>  type of the object to delete
 *      'itemid'    =>  id of the item to delete
 *
 * preview
 * =======
 *
 * The admin interface calls this function to render a preview. It has to
 * provide the followwing informations.
 *
 *      'object'    =>  the object we should render
 *      'itemtype'  =>  ala the object is not able to tell me his type ...
 *
 * </xsl:if>
 */
function <xsl:value-of select="$module_prefix" />_user_display( $args ) {

    $itemtype = xarVarCleanFromInput( 'itemtype' );
    extract( $args );

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@user='true' or @admin='true']">
        case <xsl:value-of select="@itemtype" />:
            $data = xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'display'
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
        ,'display'
        ,$data
        ,$itemtype_name );


}
</xsl:template>

</xsl:stylesheet>
