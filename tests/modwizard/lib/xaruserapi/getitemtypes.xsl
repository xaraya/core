<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xaruserapi_getitemtypes" xml:space="default">

    <xsl:message>      * xaruserapi/getitemtypes.php</xsl:message>

    <xsl:document href="{$output}/xaruserapi/getitemtypes.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapi/getitemtypes.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xaruserapi_getitemtypes_func" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>


<xsl:template mode="xaruserapi_getitemtypes_func" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @returns array
 * @return array containing the item types and their description
 */
function <xsl:value-of select="$module_prefix" />_userapi_getitemtypes() {

    $itemtypes = array();

    <xsl:for-each select="database/table">
    $itemtypes[ <xsl:value-of select="@itemtype" /> ] = array(
        'label' =>  '<xsl:value-of select="label/text()" />'
        ,'titel' =>  'Itemtype <xsl:value-of select="label/text()" />'
        ,'url'   =>  xarModURL(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'view'
            ,array( 'itemtype' => <xsl:value-of select="@itemtype" /> ) )
    );
    </xsl:for-each>

    return $itemtypes;

}
</xsl:template>

</xsl:stylesheet>
