<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_decode_shorturl">

    <xsl:message>      * xaruserapi/decode_shorturl.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/decode_shorturl.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/decode_shorturl.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_decode_shorturl_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================

    MODE: xaruserapi_decode_shorturl        MATCH:  xaraya_module

-->
<xsl:template mode="xaruserapi_decode_shorturl_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * This function is called when xarModURL is invoked and Short URL Support is
 * enabled.
 *
 * The parameters are passed in $args.
 *
 * Some hints:
 *
 * o If you want to get rid of the modulename. Look at xarModGetAlias() and
 *   xarModSetAlias().
 * o
 *
 */
function <xsl:value-of select="$module_prefix" />_userapi_decode_shorturl( $params ) 
{
    <xsl:if test="boolean( database/table[@user='true'] )">
    if ( $params[0] != '<xsl:value-of select="$module_prefix" />' )
        return;

    /*
     * Check for the itemtype
     */
    if ( empty( $params[1] ) )
        return array( 'main', array() );

    switch ( $params[1] ) {
    <xsl:for-each select="database/table[@user='true']">
        case '<xsl:value-of select="@name" />':
            $itemtype = <xsl:value-of select="@itemtype" />;
            break;
    </xsl:for-each>

        default:
            return array( 'main', array() );
    }

    if ( !isset( $params[2] ) )
        return array(
            'view'
            ,array(
                'itemtype' => $itemtype ));

    return array(
        'display'
        ,array(
            'itemid'    => $params[2]
            ,'itemtype' => $itemtype ));
    </xsl:if>
}
</xsl:template>
</xsl:stylesheet>
