<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xd_user-main">

    <xsl:message>      * xartemplates/user-main.xd</xsl:message>
    <xsl:apply-templates select="." mode="xd_user-main_file" />

</xsl:template>



<xsl:template match="xaraya_module" mode="xd_user-main_file">

<xsl:document href="{$output}/xartemplates/user-main.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">
    <xar:template file="header" type="module" />

<div class="xar-mod-body">
    <div style="padding: 1px;" class="xar-norm-outline">

        <div class="xar-mod-title xar-norm-outline" style="margin-top: 1em; margin-left: 1em; margin-right: 1em; width: auto; border-style: none none dotted none;">
            <p><xar:mlstring>Add your content here</xar:mlstring></p>
        </div>
        <div style="margin-left: 1em; margin-right: 1em; text-align:left;">
            <p><xar:mlstring>Add the content for your module here. I have not worked hard on the layout here assuming this is your part :)</xar:mlstring></p>
        </div>
    </div>
</div>

</xsl:document>

</xsl:template>

</xsl:stylesheet>
