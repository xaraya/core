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

    verify_hooks.xsl
    ================

    VERIFY THAT THE XML FILE IS VALID.

-->

<xsl:template mode="verify" match="xaraya_module">

    <xsl:message>      * Database</xsl:message>

    <xsl:if test="configuration/hooks/@enable = 'no' and count(configuration/hooks/hook) > 0 ">hooks disabled but configured !!!
        <xsl:message terminate="yes" />
    </xsl:if>

</xsl:template>


</xsl:stylesheet>
