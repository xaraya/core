<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!--

    xarobject.xml
    =============

-->

<xsl:template match="/" mode="xarobject">
    generating xarobject.xml ... <xsl:apply-templates mode="xarobject" select="xaraya_module" /> ... finished
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xarobject">
<xsl:document href="{$output}/xarobject.xml" format="text" omit-xml-declaration="yes">

    <xsl:apply-templates select="database/table" mode="xarobject" />

</xsl:document>
</xsl:template>


<!-- =========================================================================

    MODE: xarobject                         MATCH: table

-->
<xsl:template match="table" mode="xarobject" xml:space="preserve">
<xsl:element name="object" xml:space="preserve"><xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>

    <objectid>0</objectid>
    <label><xsl:value-of select="label" /></label>
    <moduleid><xsl:value-of select="../../registry/id" /></moduleid>
    <itemtype><xsl:value-of select="@itemtype" /></itemtype>
    <urlparam>itemid</urlparam>
    <maxid>0</maxid>
    <config></config>
    <isalias>1</isalias>
    <properties>
        <xsl:apply-templates mode="xarobject" select="structure/field" />
    </properties>

</xsl:element>
</xsl:template>

<xsl:template mode="xarobject_proptype" match="field[ dd_type ]">
    <xsl:choose>
        <xsl:when test="dd_type/text() = 'calendar'">8</xsl:when>
        <xsl:when test="dd_type/text() = 'email'">26</xsl:when>
        <xsl:when test="dd_type/text() = 'floatbox'">17</xsl:when>
        <xsl:when test="dd_type/text() = 'textbox'">2</xsl:when>
        <xsl:when test="dd_type/text() = 'image'">12</xsl:when>
        <xsl:when test="dd_type/text() = 'imagelist'">35</xsl:when>
        <xsl:when test="dd_type/text() = 'integerbox'">15</xsl:when>
        <xsl:when test="dd_type/text() = 'textarea_large'">5</xsl:when>
        <xsl:when test="dd_type/text() = 'textarea_medium'">4</xsl:when>
        <xsl:when test="dd_type/text() = 'textarea_small'">3</xsl:when>
        <xsl:when test="dd_type/text() = 'username'">7</xsl:when>
        <xsl:when test="dd_type/text() = 'url'">11</xsl:when>
        <xsl:when test="dd_type/text() = 'categories'">100</xsl:when>
        <xsl:when test="dd_type/text() = 'hidden'">18</xsl:when>
        <xsl:when test="dd_type/text() = 'webpage'">13</xsl:when>
        <xsl:otherwise>
            <xsl:message terminate="yes">
                Unknown DD property for <xsl:value-of select="@name" />
            </xsl:message>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>


<xsl:template mode="xarobject_proptype" match="field [ @primary_key = 'true' ]">21</xsl:template>
<xsl:template mode="xarobject_proptype" match="field">2</xsl:template>


<!-- =========================================================================

    MODE: xarobject                         MATCH: field

-->
<xsl:template match="field" mode="xarobject" xml:space="preserve">
        <xsl:variable name="module_prefix" select="../../../../registry/name" />
        <xsl:variable name="table_name" select="../../@name" />
        <xsl:element name="property" xml:space="preserve"><xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
            <id>0</id>
            <!-- Take label if specified else the name -->
            <label><xsl:choose xml:space="default"><xsl:when test="boolean( label )"><xsl:value-of select="label" /></xsl:when>
                               <xsl:otherwise><xsl:value-of select="@name" /></xsl:otherwise>
                </xsl:choose></label>
            <type><xsl:apply-templates mode="xarobject_proptype" select="." /></type>
            <default><xsl:value-of select="@default" /></default>
            <source><xsl:value-of select="$module_prefix" />_<xsl:value-of select="$table_name" />.<xsl:value-of select="@name" /></source>
            <status></status>
            <order></order>
            <validation><xsl:value-of select="dd_validation" /></validation>
        </xsl:element>
</xsl:template>
</xsl:stylesheet>
