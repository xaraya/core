<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xarprivateapi_common">

    <xsl:message>      * xarprivateapi/common.php</xsl:message>

    <xsl:document href="{$output}/xarprivateapi/common.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xarprivateapi/common.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xarprivateapi_common_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- ========================================================================

        MODE: xarprivateapi_common               MATCH: xaraya_module

-->
<xsl:template mode="xarprivateapi_common_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * This function provides information to the templates which are common to all
 * pageviews.
 *
 * It provides the following informations:
 *
 *      'menu'      => Array with information about the module menu
 *      'statusmsg' => Status message if set
 */
function <xsl:value-of select="$module_prefix" />_privateapi_common( $args ) {

    extract( $args );

    $common = array();

    $common['menu'] = array();

    // Initialize the statusmessage
    $statusmsg = xarSessionGetVar( '<xsl:value-of select="$module_prefix" />_statusmsg' );
    if ( isset( $statusmsg ) ) {
        xarSessionDelVar( '<xsl:value-of select="$module_prefix" />_statusmsg' );
        $common['statusmsg'] = $statusmsg;
    }

    <xsl:if test="not( boolean( configuration/capabilities/setpagetitle ) ) or configuration/capabilities/setpagetitle/text() = 'yes'">
    // Set the page title
    xarTplSetPageTitle( '<xsl:value-of select="$module_prefix" /> :: ' . $title );
    </xsl:if>

    // Initialize the title
    $common['pagetitle'] = $title;
    if ( isset( $type ) and $type == 'admin' ) {
        $common['type']      = xarML( '<xsl:value-of select="about/name" /> Administration' );
    }

    return array( 'common' => $common );
}
</xsl:template>

</xsl:stylesheet>
