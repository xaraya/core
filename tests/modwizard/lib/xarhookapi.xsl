<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:include href="xarhookapi/item_new.xsl" />
<xsl:include href="xarhookapi/item_modify.xsl" />
<xsl:include href="xarhookapi/item_create.xsl" />
<xsl:include href="xarhookapi/item_delete.xsl" />
<xsl:include href="xarhookapi/item_update.xsl" />

<xsl:include href="xarhookapi/item_transform-input.xsl" />
<xsl:include href="xarhookapi/item_transform.xsl" />

<xsl:include href="xarhookapi/module_remove.xsl" />
<xsl:include href="xarhookapi/module_modifyconfig.xsl" />
<xsl:include href="xarhookapi/module_updateconfig.xsl" />


<xsl:template match="xaraya_module" mode="xarhookapi">

        <xsl:message>
### Generating hook API</xsl:message>

        <!-- ITEM HOOKS -->

        <xsl:apply-templates mode="xarhookapi_item_new" select="." />
        <xsl:apply-templates mode="xarhookapi_item_modify" select="." />
        <xsl:apply-templates mode="xarhookapi_item_create" select="." />
        <xsl:apply-templates mode="xarhookapi_item_delete" select="." />
        <xsl:apply-templates mode="xarhookapi_item_update" select="." />

        <!-- TRANSFORM HOOKS -->
        <xsl:apply-templates mode="xarhookapi_item_transform" select="." />
        <xsl:apply-templates mode="xarhookapi_item_transform-input" select="." />

        <!-- MODULE HOOKS -->
        <xsl:apply-templates mode="xarhookapi_module_updateconfig" select="." />
        <xsl:apply-templates mode="xarhookapi_module_modifyconfig" select="." />
        <xsl:apply-templates mode="xarhookapi_module_remove" select="." />

</xsl:template>


</xsl:stylesheet>
