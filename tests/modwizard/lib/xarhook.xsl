<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xarhook/item_new.xsl" />
<xsl:include href="xarhook/item_modify.xsl" />
<xsl:include href="xarhook/item_display.xsl" />

<xsl:include href="xarhook/module_modifyconfig.xsl" />

<xsl:include href="xartemplates/hook-item_display.xsl"  />
<xsl:include href="xartemplates/hook-item_modify.xsl"   />
<xsl:include href="xartemplates/hook-item_new.xsl"      />
<xsl:include href="xartemplates/hook-module_modifyconfig.xsl" />


<xsl:template match="xaraya_module" mode="xarhook">

        <!-- MODULE HOOKS -->
        <xsl:if test="configuration/capabilities/module_hooks/text() = 'yes'">

        <xsl:message>
### Generating hook GUI</xsl:message>

            <xsl:apply-templates mode="xarhook_module_modifyconfig" select="." />
            <xsl:apply-templates mode="xd_hook_module_modifyconfig" select="." />
        </xsl:if>

        <!-- ITEM HOOKS -->
        <xsl:if test="configuration/capabilities/item_hooks/text() = 'yes'" >
            <xsl:apply-templates mode="xarhook_item_new"     select="." />
            <xsl:apply-templates mode="xd_hook-item_new"     select="." />
            <xsl:apply-templates mode="xarhook_item_modify"  select="." />
            <xsl:apply-templates mode="xd_hook-item_modify"  select="." />
            <xsl:apply-templates mode="xarhook_item_display" select="." />
            <xsl:apply-templates mode="xd_hook-item_display" select="." />
        </xsl:if>

        <xsl:if test="configuration/capabilities/waiting_content_hook/text() = 'yes'" >
            <xsl:message terminate="yes">
              SORRY: waiting_content_hook is not yet implemented.
            </xsl:message>
        </xsl:if>

        <xsl:if test="configuration/capabilities/user_menu_hook/text() = 'yes'" >
            <xsl:message terminate="yes">
              SORRY: user_menu_hook is not yet implemented.
            </xsl:message>
        </xsl:if>

        <xsl:if test="configuration/capabilities/search_hook/text() = 'yes'" >
            <xsl:message terminate="yes">
              SORRY: user_menu_hook is not yet implemented.
            </xsl:message>
        </xsl:if>

</xsl:template>


</xsl:stylesheet>
