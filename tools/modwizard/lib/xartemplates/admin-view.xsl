<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="xaraya_module" mode="xd_admin-view">

    <xsl:message>      * xartemplates/admin-view.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/admin-view.xd" format="text" omit-xml-declaration="yes" >

    <xar:template file="header" type="module" />

<div class="xar-mod-body">
    <div style="padding: 1px;" class="xar-norm-outline">
        <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
            <p><xar:mlstring>Add the default view of your module here</xar:mlstring></p>
        </div>
        <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
            <p><xar:mlstring>Add the startpage of your module here. This is the default view. You can find this text in "xartemplates/admin-view.xd". </xar:mlstring></p>
        </div>
    </div>
</div>
</xsl:document>
</xsl:template>
</xsl:stylesheet>
