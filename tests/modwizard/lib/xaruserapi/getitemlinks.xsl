<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_getitemlinks">

    <xsl:message>      * xaruserapi/getitemlinks.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/getitemlinks.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/getitemlinks.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_getitemlinks_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- =========================================================================
     TEMPLATE FOR <module>_userapi_getmenulinks()
-->
<xsl:template match="xaraya_module" mode="xaruserapi_getitemlinks_func">

<xsl:variable name="module_prefix" select="registry/name" />
/**
 * Utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @returns array
 * @return array containing the itemlink(s) for the item(s).
 */
function <xsl:value-of select="$module_prefix" />_userapi_getitemlinks ( $args ) {

    extract($args);

    if (empty($itemtype)) {
        return;
    }

    $itemlinks = array();
    $objects =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'getall'
        ,array(
             'itemids'   => $itemids
            ,'itemtype'  => $itemtype
#            ,'fieldlist' => array( <xsl:for-each select="labelfields/field">'<xsl:value-of select="@name" />'<xsl:if test="position() != last()">,</xsl:if>
#               </xsl:for-each>)
        ));
    if ( empty($objects) ) return;

    $data =&amp; $objects->items;

    foreach( $data as $id => $object ) {

        $title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'itemtype'  =>  $itemtype
                ,'item'     =>  &amp; $object
                ));

        $itemlinks[$id] = array(
            'url'   =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'display'
                ,array(
                    'itemid'    => $id
                    ,'itemtype' => $itemtype
                    ))
            ,'title'    =>  $title
            ,'label'    =>  $title
            );
    }

    return $itemlinks;
}
</xsl:template>


</xsl:stylesheet>
