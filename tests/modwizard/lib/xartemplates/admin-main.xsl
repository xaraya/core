<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_admin-main">
    generating xartemplates/admin-main.xd <xsl:apply-templates mode="xd_admin-main" select="xaraya_module" /> finished
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_admin-main">
<xsl:document href="{$output}/xartemplates/admin-main.xd" format="text" omit-xml-declaration="yes" >

    <xar:template file="admin-header" type="module" />

<h3>Welcome to the administration of <xsl:value-of select="about/name" /></h3>
<span>
    <h3>What is it</h3>
    <h3>How to use it</h3>
    <h3>Included Blocks</h3>
    <h3>Included Hooks</h3>
</span>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
