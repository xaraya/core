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
<xsl:include href="xaruserapi/getitemtypes.xsl" />
<xsl:include href="xaruserapi/getmenulinks.xsl" />
<xsl:include href="xaruserapi/gettitle.xsl" />
<xsl:include href="xaruserapi/get.xsl" />

<xsl:template match="xaraya_module" mode="xaruserapi">

    <xsl:message>
### Generating user api</xsl:message>

    <xsl:if test="configuration/capabilities/gui[ @type = 'user']/text() = 'yes' ">

        <!-- // FUNC // ShortURLSupport

             create the following functions only if the user enabled short url
             support. Short URL are only supported for user gui, So skip this
             when no user gui.
        -->
        <xsl:if test="not( boolean( configuration/capabilities/supportshorturls ) ) or configuration/capabilities/supportshorturls/text() = 'yes'">

                <xsl:apply-templates select="." mode="xaruserapi_encode_shorturl" />
                <xsl:apply-templates select="." mode="xaruserapi_decode_shorturl" />

        </xsl:if>

        <!-- GENERIC DATA ACCESS FUNCTIONS
        -->
        <xsl:apply-templates mode="xaruserapi_getmenulinks" select="." />

    </xsl:if>

    <xsl:if test="boolean( database/table[@user='true'] )">

        <xsl:if test="count( database/table ) > 0">
            <xsl:apply-templates mode="xaruserapi_count"    select="." />
            <xsl:apply-templates mode="xaruserapi_getall"   select="." />
            <xsl:apply-templates mode="xaruserapi_get"      select="." />
            <xsl:apply-templates mode="xaruserapi_gettitle" select="." />
            <xsl:apply-templates mode="xaruserapi_getitemlinks" select="." />
        </xsl:if>

    </xsl:if>


    <!-- if more than 2 itemtype are generated ... create this function to let
         the end user turn on hooks based on itemtypes -->
    <xsl:if test="count( database/table ) > 1">
        <xsl:apply-templates mode="xaruserapi_getitemtypes"    select="." />
    </xsl:if>

</xsl:template>

</xsl:stylesheet>
