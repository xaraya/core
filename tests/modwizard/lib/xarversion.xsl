<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xarversion">

    <xsl:message>
### Generating xarversion.php</xsl:message>

<xsl:document href="{$output}/xarversion.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

    <!-- call template for file header -->
    <xsl:call-template name="xaraya_standard_php_file_header" select=".">
        <xsl:with-param name="filename">xarversion.php</xsl:with-param>
    </xsl:call-template>

    <xsl:apply-templates mode="xarversion_vars" select="." />

    <!-- call template for file footer -->
    <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

</xsl:processing-instruction></xsl:document>
</xsl:template>



<!-- FUNCTION module_xarversion() -->
<xsl:template match="xaraya_module" mode="xarversion_vars">
    <xsl:variable name="module_prefix" select="registry/name" />

$modversion['name']           = '<xsl:value-of select="$module_prefix" />';
$modversion['id']             = '<xsl:value-of select="registry/id" />';
$modversion['version']        = '0.0.1';
$modversion['description']    = '<xsl:value-of select="about/description/short" />';
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = 'xardocs/help.txt';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = false;
$modversion['author']         = '<xsl:value-of select="about/author/name" />';
$modversion['contact']        = '<xsl:value-of select="about/author/email" />';
$modversion['admin']          = 1;
$modversion['user']           = 1;
$modversion['securityschema'] = array();
$modversion['class']          = 'Complete';
$modversion['category']       = 'Content';
</xsl:template>

</xsl:stylesheet>
