<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_admin-view">
    generating xartemplates/admin-view.xd <xsl:apply-templates mode="xd_admin-view" select="xaraya_module" /> finished
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_admin-view">
<xsl:document href="{$output}/xartemplates/admin-view.xd" format="text" omit-xml-declaration="yes" >
    <xar:template file="header" type="module" />

</xsl:document>
</xsl:template>
</xsl:stylesheet>
