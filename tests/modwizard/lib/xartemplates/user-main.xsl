<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="xaraya_module" mode="xd_user-main">

    <xsl:message>      * user-main.xd</xsl:message>
    <xsl:apply-templates select="." mode="xd_user-main_file" />

</xsl:template>



<xsl:template match="xaraya_module" mode="xd_user-main_file">

<xsl:document href="{$output}/xartemplates/user-main.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">
    <xar:template file="header" type="module" />
</xsl:document>

</xsl:template>

</xsl:stylesheet>
