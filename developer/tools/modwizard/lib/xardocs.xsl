<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xardocs/credits.xsl" />
<xsl:include href="xardocs/help.xsl" />
<xsl:include href="xardocs/changelog.xsl" />
<xsl:include href="xardocs/license.xsl" />

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="xaraya_module" mode="xardocs" xml:space="default">

        <xsl:message>
### Generating documentation files</xsl:message>

        <xsl:apply-templates mode="xardocs_credits"     select="." />
        <xsl:apply-templates mode="xardocs_help"        select="." />
        <xsl:apply-templates mode="xardocs_changelog"   select="." />
        <xsl:apply-templates mode="xardocs_license"     select="." />

</xsl:template>

</xsl:stylesheet>
