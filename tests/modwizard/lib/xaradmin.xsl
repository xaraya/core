<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xartemplates/admin-config.xsl" />
<xsl:include href="xartemplates/admin-main.xsl" />
<xsl:include href="xartemplates/admin-view.xsl" />

<xsl:include href="xaradmin/config.xsl" />
<xsl:include href="xaradmin/main.xsl" />
<xsl:include href="xaradmin/delete.xsl" />
<xsl:include href="xaradmin/modify.xsl" />
<xsl:include href="xaradmin/new.xsl" />
<xsl:include href="xaradmin/view.xsl" />


<xsl:template match="xaraya_module" mode="xaradmin" xml:space="default">

    <xsl:message>
### Generating admin interfaces</xsl:message>

    <xsl:if test="configuration/capabilities/gui[@type='admin']/text() = 'yes' ">

        <xsl:apply-templates mode="xaradmin_main"   select="." />
        <xsl:apply-templates mode="xaradmin_view"   select="." />
        <xsl:apply-templates mode="xaradmin_config" select="." />
        <xsl:apply-templates mode="xd_admin-main"         select="." />
        <xsl:apply-templates mode="xd_admin-view"         select="." />
        <xsl:apply-templates mode="xd_admin-config"       select="." />

        <xsl:if test="count( database/table ) > 0">
            <xsl:apply-templates mode="xaradmin_new"    select="." />
            <xsl:apply-templates mode="xaradmin_modify" select="." />
            <xsl:apply-templates mode="xaradmin_delete" select="." />
        </xsl:if>

    </xsl:if>

</xsl:template>


</xsl:stylesheet>
