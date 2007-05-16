<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xarables.xsl
    =============

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xartables" xml:space="default">
    generating xartables.php ... <xsl:apply-templates mode="xartables" select="xaraya_module" /> ... finished
</xsl:template>



<!-- MODULE POINT

     Create a new file called xartables.php.

-->
<xsl:template match="xaraya_module" mode="xartables">
<xsl:document href="{$output}/xartables.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

    <!-- call template for file header -->
    <xsl:call-template name="xaraya_standard_php_file_header" select=".">
        <xsl:with-param name="filename">xartables.php</xsl:with-param>
    </xsl:call-template>

    <!-- call template for module_xartables() function -->
    <xsl:apply-templates mode="xartables_xartables" select="database" />

    <!-- call template for file footer -->
    <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

</xsl:processing-instruction></xsl:document>
</xsl:template>



<!-- FUNCTION module_xartables() -->
<xsl:template match="database" mode="xartables_xartables">
    <xsl:variable name="module_prefix" select="../registry/name" />
/**
 * Return <xsl:value-of select="../about/name" /> table names to xaraya
 *
 * This function is called internally by the core whenever the module is
 * loaded.  It is loaded by xarMod__loadDbInfo().
 *
 * @access private
 * @return array
 */
function <xsl:value-of select="$module_prefix" />_xartables()
{
    // Initialise table array
    $xartables = array();
    <xsl:for-each select="table">
    // Get the name for the <xsl:value-of select="@name" /> table.
    // This is not necessary but helps in the following statements and
    // keeps them readable
    $<xsl:value-of select="@name" />table = xarDB::getPrefix() . '_<xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />';

    // Set the table name
    $xartables['<xsl:value-of select="@name" />'] = $<xsl:value-of select="@name" />table;
    </xsl:for-each>
    // Return the table information
    return $xartables;
}
</xsl:template>

</xsl:stylesheet>
