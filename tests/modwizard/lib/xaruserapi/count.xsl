<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_count">

    <xsl:message>      * xaruserapi/count.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/count.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/count.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_count_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<!-- ========================================================================

        MODE: xaruserapi_count              MATCH:  table
-->
<xsl:template mode="xaruserapi_count_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Generic function to retrieve the number of objects stored in database of
 * itemtype $itemtype;
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 */
function <xsl:value-of select="$module_prefix" />_userapi_count( $args ) {

    extract( $args );

    // Retrieve all objects via the dynamicdata module api.
    $numitems =&amp; xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'countitems'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => $itemtype
        ));

    return $numitems;
}
</xsl:template>

</xsl:stylesheet>
