<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xd_admin-main">

    <xsl:message>      * xartemplates/admin-main.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/admin-main.xd" format="text" omit-xml-declaration="yes" >
    <xsl:variable name="module_prefix" select="registry/name" />

    <xar:template file="header" type="module" />

<div class="xar-mod-body">
    <div style="padding: 1px;" class="xar-norm-outline">
        <div style="float:right;padding:10px;">
            <xar:if condition="file_exists('modules/modules/xarimages/admin.gif')" >
                <img src="modules/modules/xarimages/admin.gif" alt="official icon" width="96" height="96" />
            <xar:else />
                <img src="modules/modules/xarimages/admin_generic.gif" alt="official icon" width="96" height="96" />
            </xar:if>
        </div>
        <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
            <p><xar:mlstring>What is it?</xar:mlstring></p>
        </div>
        <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
            <p><xar:mlstring>Describe your module here.</xar:mlstring></p>
        </div>
        <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
            <p><xar:mlstring>How to use it?</xar:mlstring></p>
        </div>
        <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
                <p><xar:mlstring>
                Describe the usage of your module here.
                </xar:mlstring></p>
        </div>
        <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
            <p><xar:mlstring>Included Blocks</xar:mlstring></p>
        </div>
        <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
        <p><xar:mlstring>Describe the block included with your module here.</xar:mlstring></p>
        </div>
        <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
            <p><xar:mlstring>Included Hooks</xar:mlstring></p>
        </div>
        <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
            <p><xar:mlstring>Describe the provided hooks or delete this section.</xar:mlstring></p>
        </div>
        <div class="xar-norm-outline xar-accent" style="text-align: center; padding: 0.5em 1em 0.5em 1em; margin-top: 5px;">
            <p><xar:mlstring>Extended information about this module can be found here. [modules module]</xar:mlstring></p>
        </div>
    </div>
</div>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
