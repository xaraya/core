<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xarstyles/navbar.xsl" />


<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="xaraya_module" mode="xarstyles" xml:space="default">

        <xsl:message>
### Generating css styles</xsl:message>

        <xsl:apply-templates mode="xarstyles_navbar"    select="." />

</xsl:template>
</xsl:stylesheet>
