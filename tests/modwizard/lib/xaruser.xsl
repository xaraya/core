<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xaruser/main.xsl" />
<xsl:include href="xaruser/display.xsl" />
<xsl:include href="xaruser/view.xsl" />

<xsl:include href="xartemplates/user-main.xsl" />


<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="xaraya_module" mode="xaruser" xml:space="default">

        <xsl:message>
### Generating user GUI</xsl:message>

        <xsl:apply-templates mode="xaruser_main"    select="." />
        <xsl:apply-templates mode="xd_user-main"    select="." />

        <xsl:apply-templates mode="xaruser_display" select="." />
        <xsl:apply-templates mode="xaruser_view"    select="." />


</xsl:template>

</xsl:stylesheet>
