<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xaritemtypeapi_confirmdelete">

    <xsl:variable name="itemtype" select="@name" />

    <xsl:message>      * <xsl:value-of select="$itemtype" />api/confirmdelete.php</xsl:message>

    <xsl:document href="{$output}/xar{$itemtype}api/confirmdelete.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xar<xsl:value-of select="$itemtype" />api/confirmdelete.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaritemtypeapi_confirmdelete_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- =========================================================================

    MODE: xaritemtypeapi_confirmdelete              MATCH:

-->
<xsl:template mode="xaritemtypeapi_confirmdelete_func" match="table">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Confirm the deletion of a <xsl:value-of select="@name" /> object.
 *
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />api_confirmdelete( $args ) {

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
    $data = xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'private'
        ,'common'
        ,array(
            'title' => 'Delete <xsl:value-of select="label" /> ' .$item_title
            ,'type' => 'admin'
            ));

    /*
     * Compose the data for the template
     */
    $data['object'] = $object;
    $data['itemid'] = $itemid;
    $data['action'] = xarModURL(
        '<xsl:value-of select="$module_prefix" />'
        ,'admin'
        ,'delete'
        ,array(
            'itemtype'  => <xsl:value-of select="@itemtype" />
            ,'itemid'   => $itemid ));
    $data['authid'] = xarSecGenAuthKey();

    return $data;

}
</xsl:template>

</xsl:stylesheet>
