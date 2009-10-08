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

    <xsl:message>      * Hooks</xsl:message>

    <xsl:if test="database/table/structure/field[@transform = 'true' and @overview = 'true']">
        <xsl:message>
WARNING: you have configured fields to show in overview and also as subject to hook
transformation. This is currently not implemented. Transform hooks are only
called for dispay() not for view():</xsl:message>
    </xsl:if>

</xsl:template>


</xsl:stylesheet>
