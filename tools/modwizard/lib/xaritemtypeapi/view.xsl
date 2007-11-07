<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_view">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * xar<xsl:value-of select="$itemtype" />api/view.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/view.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/view.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_view_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>



<xsl:template mode="xaritemtypeapi_view_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * // TODO // Add description
 *
 * // TODO // explain that the function is called from admin and user * interface.
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_view( $args ) 
{
    if (!xarSecurityCheck( 'View<xsl:value-of select="$module_prefix" />')) return;

    // Get parameter from browser
    list( $type, $startnum, $itemid ) = xarVarCleanFromInput( 'type', 'startnum', 'itemid' );
    extract( $args );

    $data =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => xarML( 'List all <xsl:value-of select="label" />' )
            ,'type' => $type
            ));

    $itemsperpage = xarModVars::Get(
            '<xsl:value-of select="$module_prefix" />'
            ,'itemsperpage.' . <xsl:value-of select="@itemtype" /> );

    $objects =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'getall'
        ,array(
             'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'numitems'  => $itemsperpage
            ,'startnum'  => $startnum
            ,'sort'      => array(
                <xsl:for-each select="order/field">'<xsl:value-of select="@name" />'<xsl:if test="position() != last()">,</xsl:if>
                </xsl:for-each>)
            ,'fieldlist' => array( <xsl:for-each select="structure/field[ @overview = 'true' ]">'<xsl:value-of select="@name" />'<xsl:if test="position() != last()">,</xsl:if>
               </xsl:for-each>)
        ));
    if ( empty($objects) ) return;

    $data['objects_props']  =&amp; $objects->getProperties();
    $data['objects_values'] =&amp; $objects->items;
    $data['itemtype'] = <xsl:value-of select="@itemtype" />;
    $data['pager'] = xarTplGetPager(
        $startnum
        ,xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'count'
            ,array( 'itemtype' => <xsl:value-of select="@itemtype" /> ))
        ,xarModURL(
            '<xsl:value-of select="$module_prefix" />'
            ,$type
            ,'view'
            ,array(
                'startnum'  => '%%'
                ,'itemtype' => <xsl:value-of select="@itemtype" /> ))
        ,$itemsperpage );

    return $data;
}
</xsl:template>


</xsl:stylesheet>
