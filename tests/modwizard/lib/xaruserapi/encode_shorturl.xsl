<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_encode_shorturl">

    <xsl:message>      * xaruserapi/encode_shorturl.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/encode_shorturl.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/encode_shorturl.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_encode_shorturl_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================

    MODE: xaruserapi_encode_shorturl        MATCH:  xaraya_module

-->
<xsl:template mode="xaruserapi_encode_shorturl_func" match="xaraya_module">
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
function <xsl:value-of select="$module_prefix" />_userapi_encode_shorturl( $args ) {
    <xsl:if test="boolean( database/table[@user='true'] )">
    $func       = NULL;
    $module     = NULL;
    $itemid     = NULL;
    $itemtype   = NULL;
    $rest       = array();

    foreach( $args as $name => $value ) {

        switch( $name ) {

            case 'module':
                $module = $value;
                break;

            case 'itemtype':
                $itemtype = $value;
                break;

            case 'objectid':
            case 'itemid':
                $itemid = $value;
                break;

            case 'func':
                $func = $value;
                break;

            default:
                $rest[] = $value;

       }
    }

    // kind of a assertion :-))
    if( isset( $module ) and $module != '<xsl:value-of select="$module_prefix" />' ) {
        return;
    }

    /*
     * LETS GO. We start with the module.
     */
    $path = '/<xsl:value-of select="$module_prefix" />';

    if ( empty( $func ) )
        return;

    /*
     * We only provide support for display and view and main
     */
    if ( $func != 'display' and $func != 'view' and $func != 'main' )
        return;

    /*
     * Now add the itemtype if possible
     */
    if ( isset( $itemtype ) ) {

        switch ( $itemtype ) {
        <xsl:for-each select="database/table[@user='true']">
            case <xsl:value-of select="@itemtype" />:
                $itemtype_name = '<xsl:value-of select="@name" />';
                break;
        </xsl:for-each>

        default:
            // Unknown itemtype?
            return;
        }

        $path = $path . '/' . $itemtype_name;

        /*
         * And last but not least the itemid
         */
        If ( isset( $itemid ) ) {
                $path = $path . '/' . $itemid;
        }
    }

    /*
     * ADD THE REST !!!! THIS HAS TO BE DONE EVERYTIME !!!!!
     */
    $add = array();
    foreach ( $rest as $argument ) {
        if ( isset( $rest['argument'] ) ) {
            $add[] =  $argument . '=' . $rest[$argument];
        }
    }

    if ( count( $add ) > 0 ) {
        $path = $path . '?' . implode( '&amp;', $add );
    }

    return $path;
    </xsl:if>
}
</xsl:template>
</xsl:stylesheet>
