<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_getall">

    <xsl:message>      * xaruserapi/getall.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/getall.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/getall.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_getall_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- ========================================================================

        MODE: xaruserapi_getall             MATCH:  table
-->
<xsl:template mode="xaruserapi_getall_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 *
 * @param $args['startnum'] starting article number
 * @param $args['numitems'] number of articles to get
 * @param $args['sort'] sort order ('date','title','hits','rating',...)
 * @param $args['fields'] array with all the fields to return per article
 *                        Default list is : 'aid','title','summary','authorid',
 *                        'pubdate','pubtypeid','notes','status','body'
 *                        Optional fields : 'cids','author','counter','rating','dynamicdata'
 */
function <xsl:value-of select="$module_prefix" />_userapi_getall( $args ) {

    extract( $args );

    if ( empty($startnum) ) {
        $startnum = NULL;
    }

    if ( empty($numitems) ) {
        $numitems = NULL;
    }

    if ( empty($sort) ) {
        $sort = NULL;
    }

    if ( empty($fieldlist) ) {
        $fieldlist = NULL;
    }

    if ( empty($itemids) ) {
        $itemids = NULL;
    }

    // Retrieve all objects via the dynamicdata module api.
    $objects =&amp; xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getitems'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => $itemtype
            ,'numitems'  => $numitems
            ,'startnum'  => $startnum
            ,'status'    => 1
            ,'sort'      => $sort
            ,'getobject' => 1
            ,'itemids'   => $itemids
            ,'fieldlist' => $fieldlist
        ));

    return $objects;
}
</xsl:template>

</xsl:stylesheet>
