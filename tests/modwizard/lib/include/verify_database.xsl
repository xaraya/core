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

    verify_database.xsl
    ================

    VERIFY THAT THE XML FILE IS VALID.

-->



<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="verify" xml:space="default">
    verifying database ... <xsl:apply-templates mode="verify_database" select="xaraya_module" /> ... finished
</xsl:template>


<!-- =========================================================================

    MODE: verify_database                   MATCH: xaraya_module

-->
<xsl:template mode="verify_database" match="xaraya_module">

    <xsl:for-each select="database/table">

        <xsl:if test="count ( structure/field[@primary_key = 'true'] ) != 1">
            <xsl:message terminate="yes">
    You must specify exactly one primary_key field for table <xsl:value-of select="@name" /> !!
            </xsl:message>
        </xsl:if>

    </xsl:for-each>

    <xsl:for-each select="database/table">
        <xsl:if test="count ( structure/field[@overview = 'true'] ) = 0">
            <xsl:message terminate="yes">
    You should at least specify one field of table <xsl:value-of select="@name" /> with overview="true" !!
            </xsl:message>
        </xsl:if>

    </xsl:for-each>

</xsl:template>


</xsl:stylesheet>

