<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_delete">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * xar<xsl:value-of select="$itemtype" />api/delete.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/delete.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/delete.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_delete_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================

    MODE: xaritemtypeapi_delete              MATCH:

-->
<xsl:template mode="xaritemtypeapi_delete_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Delete a <xsl:value-of select="@name" /> object.
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_delete( $args ) {

    if (!xarSecurityCheck( 'Delete<xsl:value-of select="$module_prefix" />')) return;

    list ( $itemid ) = xarVarCleanFromInput( 'itemid' );
    extract($args);

    // Retrieve the object
    $object =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'get'
        ,array(
             'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'    => $itemid
        ));
    if ( empty( $object ) ) return;

    $item_title = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'gettitle'
        ,array(
            'object'    =>  $object
            ,'itemtype' =>  <xsl:value-of select="@itemtype" /> ));

    /*
     * The user confirmed the deletion so let's go.
     */
    // The 'api:delete' hook is called from dynamic data during createItem()
    $itemid = $object->deleteItem();
    if ( empty( $itemid ) ) return;

    /*
     * Set the status message
     */
    xarSessionSetVar(
        '<xsl:value-of select="$module_prefix" />_statusmsg'
        ,xarML( 'Deleted <xsl:value-of select="label" /> [#(1)] - "#(2)"', $itemid, $item_title  ) );

    return;
}
</xsl:template>


</xsl:stylesheet>
