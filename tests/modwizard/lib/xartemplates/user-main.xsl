<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_user-main">

    generating xartemplates/user-main.xd ...<xsl:apply-templates select="xaraya_module" mode="xd_user-main" />... finished

</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_user-main">
<xsl:document href="{$output}/xartemplates/user-main.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">
    <xar:template file="header" type="module" />
</xsl:document>
</xsl:template>
</xsl:stylesheet>
