<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">



<xsl:include href="xaruserapi/count.xsl" />
<xsl:include href="xaruserapi/decode_shorturl.xsl" />
<xsl:include href="xaruserapi/encode_shorturl.xsl" />
<xsl:include href="xaruserapi/getall.xsl" />
<xsl:include href="xaruserapi/getitemlinks.xsl" />
<xsl:include href="xaruserapi/getmenulinks.xsl" />
<xsl:include href="xaruserapi/gettitle.xsl" />
<xsl:include href="xaruserapi/get.xsl" />

<xsl:template match="xaraya_module" mode="xaruserapi">

    <xsl:message>
### Generating user api</xsl:message>

    <!-- UTILITY FUNCTIONS
    -->

    <xsl:apply-templates select="." mode="xaruserapi_getmenulinks" />


    <!-- // FUNC // ShortURLSupport

         create the following functions only if the user enabled short url
         support
    -->
    <xsl:if test="not( boolean( configuration/capabilities/supportshorturls ) ) or configuration/capabilities/supportshorturls/text() = 'yes'">

            <xsl:apply-templates select="." mode="xaruserapi_encode_shorturl" />
            <xsl:apply-templates select="." mode="xaruserapi_decode_shorturl" />

    </xsl:if>

    <!-- EVENT FUNCTIONS
    -->

    <!-- GENERIC DATA ACCESS FUNCTIONS
    -->
    <xsl:if test="boolean( database/table[@user='true'] )">

        <xsl:apply-templates mode="xaruserapi_count" select="." />
        <xsl:apply-templates mode="xaruserapi_getall" select="." />
        <xsl:apply-templates mode="xaruserapi_get" select="." />
        <xsl:apply-templates mode="xaruserapi_gettitle" select="." />
        <xsl:apply-templates select="." mode="xaruserapi_getitemlinks" />

    </xsl:if>


</xsl:template>

</xsl:stylesheet>
