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

    xaritemtypeapi.xsl
    ==================

-->

<xsl:include href="xartemplates/user-display-itemtype.xsl" />
<xsl:include href="xartemplates/user-view-itemtype.xsl" />

<xsl:include href="xartemplates/admin-view-itemtype.xsl" />
<xsl:include href="xartemplates/admin-modify-itemtype.xsl" />
<xsl:include href="xartemplates/admin-config-itemtype.xsl" />
<xsl:include href="xartemplates/admin-delete-itemtype.xsl" />
<xsl:include href="xartemplates/admin-new-itemtype.xsl" />

<xsl:include href="xaritemtypeapi/create.xsl" />
<xsl:include href="xaritemtypeapi/config.xsl" />
<xsl:include href="xaritemtypeapi/confirmdelete.xsl" />
<xsl:include href="xaritemtypeapi/delete.xsl" />
<xsl:include href="xaritemtypeapi/display.xsl" />
<xsl:include href="xaritemtypeapi/modify.xsl" />
<xsl:include href="xaritemtypeapi/new.xsl" />
<xsl:include href="xaritemtypeapi/update.xsl" />
<xsl:include href="xaritemtypeapi/view.xsl" />


<xsl:template match="xaraya_module" mode="xaritemtypeapi" xml:space="default">

    <xsl:if test="count( database/table ) > 0">

        <xsl:message>
### Generating itemtype apis</xsl:message>

    </xsl:if>

    <xsl:for-each select="database/table[ @user='true' or @admin='true' ]">

        <xsl:if test="@admin = 'true'">

            <xsl:apply-templates mode="xaritemtypeapi_new" select="." />
            <xsl:apply-templates mode="xaritemtypeapi_modify" select="." />
            <xsl:apply-templates mode="xaritemtypeapi_update" select="." />
            <xsl:apply-templates mode="xaritemtypeapi_delete" select="." />
            <xsl:apply-templates mode="xaritemtypeapi_confirmdelete" select="." />
            <xsl:apply-templates mode="xaritemtypeapi_create" select="." />
            <xsl:apply-templates mode="xaritemtypeapi_config" select="." />

            <xsl:apply-templates mode="xd_admin-view-itemtype"   select="." />
            <xsl:apply-templates mode="xd_admin-config-itemtype" select="." />
            <xsl:apply-templates mode="xd_admin-delete-itemtype" select="." />
            <xsl:apply-templates mode="xd_admin-new-itemtype"    select="." />
            <xsl:apply-templates mode="xd_admin-modify-itemtype" select="." />

        </xsl:if>

        <xsl:if test="@user = 'true' or @admin='true'">

            <xsl:apply-templates mode="xaritemtypeapi_display" select="." />
            <xsl:apply-templates mode="xd_user-display-itemtype" select="." />

            <xsl:apply-templates mode="xaritemtypeapi_view" select="." />
            <xsl:apply-templates mode="xd_user-view-itemtype"    select="." />

        </xsl:if>

    </xsl:for-each>

</xsl:template>


























</xsl:stylesheet>
