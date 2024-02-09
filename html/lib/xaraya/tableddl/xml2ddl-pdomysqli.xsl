<?xml version="1.0" encoding="utf-8"?>
  <!--
    XSLT to create a DDL fragment which represents the same
    information as the ddl XML
  -->
  <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
    <!--
        Import common templates, we use import instead of include so the
        imported templates get a lower priority than the ones in this file,
        giving the ability here to override the imports
    -->
    <xsl:import href="xml2ddl-base.xsl"/>

  <!-- Things to do before we start handling elements -->
  <xsl:template match="/">

    <xsl:choose>
      <xsl:when test="$action = 'display'">
          <xsl:call-template name="topheader">
            <xsl:with-param name="dbname">
              <xsl:value-of select="/schema/@name"/>
            </xsl:with-param>
            <xsl:with-param name="remarks">
            - reference: http://dev.mysql.com/doc/refman/5.0/en/index.html
            - assuming for now we want to drop before create
            </xsl:with-param>
          </xsl:call-template>

        <xsl:text>/* Disable foreign key checks until we're done */</xsl:text>
        <xsl:value-of select="$CR"/>
        <xsl:text>SET FOREIGN_KEY_CHECKS = 0;</xsl:text>
        <xsl:value-of select="$CR"/>
        <xsl:apply-templates/>
        <xsl:text>SET FOREIGN_KEY_CHECKS = 1;</xsl:text>
        <xsl:value-of select="$CR"/>
      </xsl:when>
      <xsl:when test="$action = 'create'">
        <xsl:apply-templates select="/schema/table"/>
      </xsl:when>
    </xsl:choose>

  </xsl:template>

  <xsl:template match="table">
    <xsl:if test="$action = 'display'">
      <xsl:call-template name="dynheader"/>
    </xsl:if>
    <xsl:text>DROP TABLE IF EXISTS </xsl:text>
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="@name"/><xsl:text>;</xsl:text>
    <xsl:value-of select="$CR"/>
    <xsl:text>CREATE TABLE </xsl:text>
    <xsl:if test="$tableprefix != ''">
      <xsl:value-of select="$tableprefix"/>
      <xsl:text>_</xsl:text>
    </xsl:if>
    <xsl:value-of select="@name"/>
    <xsl:text>(</xsl:text>
    <xsl:value-of select="$CR"/>
    <xsl:apply-templates select="column"/>
    <xsl:text>) </xsl:text>

   <!-- @todo how does mysql handles altering the table comments on any other operation?  -->
    <xsl:if test="description">
      <xsl:text>COMMENT='</xsl:text>
      <xsl:value-of select="description"/>
      <xsl:text>'</xsl:text>
    </xsl:if>

    <xsl:text>;</xsl:text>
    <xsl:value-of select="$CR"/>
    <!-- @todo ignore primary key here here until we handle auto increment seperately (ALTER TABLE?) -->
    <xsl:apply-templates select="constraints/index | constraints/unique | constraints/primary"/>
    <xsl:value-of select="$CR"/>
  </xsl:template>

  <xsl:template match="column">
    <xsl:text>  </xsl:text>
    <xsl:value-of select="@name"/><xsl:text> </xsl:text>
    <xsl:call-template name="columnattributes">
      <xsl:with-param name="ignoreauto">true</xsl:with-param>
    </xsl:call-template>
    <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
    <xsl:value-of select="$CR"/>
  </xsl:template>

  <xsl:template name="columnattributes">
    <xsl:param name="ignoreauto" value="false"/>
    <!-- @todo move the specific types into their own templates -->
    <xsl:choose>
      <xsl:when test="number">
        <xsl:choose>
          <xsl:when test="*[@size != '']">
            <xsl:choose>
              <xsl:when test="*[@size > 3]">
                <xsl:text>INTEGER</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>TINYINT</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>INTEGER</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:when test="text">
        <xsl:choose>
          <xsl:when test="*[@size != '']">
              <xsl:text>VARCHAR</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>TEXT</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:when test="long">
        <xsl:text>LONGTEXT</xsl:text>
      </xsl:when>
      <xsl:when test="medium">
        <xsl:text>MEDIUMTEXT</xsl:text>
      </xsl:when>
      <xsl:when test="binary">
        <xsl:text>BLOB</xsl:text>
      </xsl:when>
      <xsl:when test="binarylong">
        <xsl:text>LONGBLOB</xsl:text>
      </xsl:when>
      <xsl:when test="boolean">
        <xsl:text>BOOLEAN</xsl:text>
      </xsl:when>
      <xsl:when test="decimal">
        <xsl:text>DECIMAL</xsl:text>
      </xsl:when>
      <xsl:when test="float">
        <xsl:text>FLOAT</xsl:text>
      </xsl:when>
      <!-- @todo add support for time --> 
      <xsl:otherwise>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="*[@size != '']">(<xsl:value-of select="*/@size"/>)</xsl:if>
    <xsl:if test="*[@unsigned = 'true']">
        <xsl:text> UNSIGNED</xsl:text>
    </xsl:if>
    <xsl:if test="*[@charset]">
        <xsl:text> CHARACTER SET </xsl:text>
        <xsl:value-of select="*/@charset"/>
    </xsl:if>
    <xsl:if test="@required = 'true'"> NOT NULL</xsl:if>
    <!--  @todo this won't work with  the current exported ddl -->
    <xsl:if test="*[@default]">
        <xsl:text> DEFAULT</xsl:text>
        <xsl:choose>
          <xsl:when test="*/@default = 'null'">
            <xsl:text> NULL</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text> '</xsl:text>
            <xsl:value-of select="*/@default"/>
            <xsl:text>'</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
    </xsl:if>
    <xsl:if test="$ignoreauto = 'false'">
      <xsl:if test="@auto ='true'"> AUTO_INCREMENT</xsl:if>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>