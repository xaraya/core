<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xaradminapi/getmenulinks.xsl" />


<xsl:template match="xaraya_module" mode="xaradminapi" xml:space="default">

    <xsl:message>
### Generating adminstration api</xsl:message>

    <xsl:if test="configuration/capabilities/gui[@type='admin']/text() = 'yes' ">

        <xsl:apply-templates select="." mode="xaradminapi_getmenulinks" />

    </xsl:if>

    <xsl:for-each select="database/table">

        <!-- NOTHING TO DO -->

    </xsl:for-each>


</xsl:template>

</xsl:stylesheet>
