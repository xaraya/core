<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaradmin_view">

    <xsl:message>      * xaradmin/view.php</xsl:message>

    <xsl:document href="{$output}/xaradmin/view.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaradmin/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaradmin_view_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<!-- =========================================================================

     TEMPLATE xaradmin_view ( xaraya_module )

     Create the <module>_admin_view() function.

-->
<xsl:template mode="xaradmin_view_func" match="xaraya_module">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >=1">
/**
 * Show a overview of all available administration options.
 *
 * This is the main page if the admin 'Disabled Module Overview' in
 * 'adminpanels - configurations - configure overview'.
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_admin_view($args) {

    list( $itemtype ) = xarVarCleanFromInput('itemtype' );

    switch( $itemtype ) {
    <xsl:for-each select="database/table[@admin='true']">
        case <xsl:value-of select="@itemtype" />:
            $data = xarModAPIFunc(
                '<xsl:value-of select="$module_prefix" />'
                ,'<xsl:value-of select="@name" />'
                ,'view' );
            $itemtype_name = '<xsl:value-of select="@name" />';
            break;
    </xsl:for-each>

        default:
            return
                $data = xarModAPIFunc(
                    '<xsl:value-of select="$module_prefix" />'
                    ,'private'
                    ,'common'
                    ,array(
                        'title' => 'Main Page'
                        ,'type' => 'admin'
                        ));
    }

    return xarTplModule(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'view'
        ,$data
        ,$itemtype_name );
}


</xsl:template>


</xsl:stylesheet>
