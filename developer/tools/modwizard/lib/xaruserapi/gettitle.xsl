<!DOCTYPE xsl:stylesheet [
        <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:html="http://www.w3.org/TR/xhtml1/strict"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xaruserapi_gettitle">

    <xsl:message>      * xaruserapi/gettitle.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/gettitle.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/gettitle.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_gettitle_func" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<!-- ========================================================================

        MODE: xaruserapi_gettitle           MATCH:  table
-->
<xsl:template mode="xaruserapi_gettitle_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 *
 * @param $args['item'] item
 * @param $args['itemtype'] itemtyp
 */
function <xsl:value-of select="$module_prefix" />_userapi_gettitle( $args ) 
{
    extract( $args );

    if ( empty( $itemtype ) ) return 'Itemtype missing';

    if ( isset( $item ) ) {
        switch ( $itemtype ) {
        <xsl:for-each select="database/table">
            <xsl:if test="boolean( labelfields )">
            case <xsl:value-of select="@itemtype" />:
                return <xsl:for-each select="labelfields/field">$item['<xsl:value-of select="@name" />']<xsl:if test="last() != position()"> .
                       '<xsl:value-of select="../@separator" />' . </xsl:if></xsl:for-each>;
                break;
            </xsl:if>
        </xsl:for-each>
        }

    } else if ( isset( $object ) ) {

        switch ( $itemtype ) {
        <xsl:for-each select="database/table">
            <xsl:if test="boolean( labelfields )">
            case <xsl:value-of select="@itemtype" />:
                return <xsl:for-each select="labelfields/field">$object->properties['<xsl:value-of select="@name" />']->getValue()<xsl:if test="last() != position()"> .
                       '<xsl:value-of select="../@separator" />' . </xsl:if></xsl:for-each>;
                break;
            </xsl:if>
        </xsl:for-each>
        }
    }

    return 'Unknown Itemtype';
}
</xsl:template>

</xsl:stylesheet>
