<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:include href="xarprivateapi/adminconfigmenu.xsl" />
<xsl:include href="xarprivateapi/common.xsl" />

<xsl:template match="xaraya_module" mode="xarprivateapi" xml:space="default">

    <xsl:message>
### Generating private api</xsl:message>

    <xsl:apply-templates mode="xarprivateapi_adminconfigmenu" select="." />

    <xsl:apply-templates mode="xarprivateapi_common" select="." />

</xsl:template>

</xsl:stylesheet>
