<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_get">

    <xsl:message>      * xaruserapi/get.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/get.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/get.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_get_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- ========================================================================

        MODE: xaruserapi_get                MATCH:  table
-->
<xsl:template mode="xaruserapi_get_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 *
 * @param $args['itemid'] starting article number
 * @param $args['numitems'] number of articles to get
 * @param $args['sort'] sort order ('date','title','hits','rating',...)
 * @param $args['fields'] array with all the fields to return
 * @param $args['fields'] array with all the fields to return
 */
function <xsl:value-of select="$module_prefix" />_userapi_get( $args ) {

    extract( $args );

    // Retrieve the object via the dynamicdata module api.
    $object = xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getitem'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => $itemtype
            ,'itemid'    => $itemid
            ,'status'    => 1
            ,'getobject' => 1
        ));
    if ( empty($object) ) return;

    return $object;
}
</xsl:template>

</xsl:stylesheet>
