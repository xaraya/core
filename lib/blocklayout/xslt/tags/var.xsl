<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<!--
  Informal specification:

  Get the value of a variable:
    <xar:var @name=string [@scope=(local|user|config|session|request)]/>
    <xar:var @name=string @scope=module @module=string />

  Set the value of a variable:
    <xar:var [@name=string] [@scope=local]>
      node-set
    </xar:var>

    <xar:var @name=string @scope=(user|session)>
      node-set
    </xar:var>

    <xar:var @name=string @scope=module @module=string>
      node-set
    </xar:var>

-->
<xsl:template match="xar:set/xar:var">
    <xsl:if test="@name !=''">
      <xsl:call-template name="xarvar_getcode"/>
    </xsl:if>
</xsl:template>

<xsl:template name="xar-var" match="xar:var">
  <xsl:choose>
    <xsl:when test="not(node())">
      <!-- Empty form, getting a value -->
      <xsl:if test="@name != ''">
        <xsl:processing-instruction name="php">
          <xsl:text>echo </xsl:text>
          <xsl:call-template name="xarvar_getcode"/>
          <xsl:text>;</xsl:text>
        </xsl:processing-instruction>
      </xsl:if>
    </xsl:when>
    <xsl:otherwise>
      <!-- Open form, setting a value -->
      <xsl:if test="@scope = 'local' or not(@scope)">
        <xsl:processing-instruction name="php">
          <xsl:choose>
            <xsl:when test="not(@name) or @name = ''">
              <!-- No name specified, generate one -->
              <xsl:text>$var</xsl:text>
              <xsl:value-of select="generate-id()"/>
            </xsl:when>
            <xsl:otherwise>
              <!-- Name specified, use it -->
              <xsl:text>$</xsl:text>
              <xsl:value-of select="@name"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>=</xsl:text>
          <xsl:call-template name="xarvar_setcode"/>
          <xsl:text>;</xsl:text>
        </xsl:processing-instruction>
      </xsl:if>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!--
  if xar:var contains a textnode, we're setting a var, hack the special
  treatment in for now until we have other things in place
-->
<xsl:template match="xar:var/text()">
    <xsl:choose>
      <xsl:when test="substring(normalize-space(.),1,1) = '#'">
        <!-- The string starts with # so, let's resolve it -->
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="normalize-space(.)"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <!-- No start with #, just copy it -->
        <xsl:text>'</xsl:text>
        <xsl:call-template name="replace">
          <xsl:with-param name="source" select="normalize-space(.)"/>
        </xsl:call-template>
        <xsl:text>'</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template name="xarvar_setcode">
  <xsl:choose>
    <xsl:when test="@scope = 'local' or not(@scope)">
      <xsl:apply-templates />
    </xsl:when>
  </xsl:choose>
</xsl:template>

<xsl:template name="xarvar_getcode">
    <xsl:choose>
      <!-- Modvars -->
      <xsl:when test="@scope = 'module'">
        <xsl:text>xarModVars::get('</xsl:text>
        <xsl:value-of select="@module"/>
        <xsl:text>', '</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>')</xsl:text>
      </xsl:when>
      <!-- Local vars -->
      <xsl:when test="@scope = 'local' or not(@scope)">
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="concat('$',@name)"/>
        </xsl:call-template>
      </xsl:when>
      <!-- User vars -->
      <xsl:when test="@scope = 'user'">
        <xsl:text>xarUserGetVar('</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>',</xsl:text>
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="@user"/>
        </xsl:call-template>
        <xsl:text>)</xsl:text>
      </xsl:when>
      <!-- Config vars -->
      <xsl:when test="@scope = 'config'">
        <xsl:text>xarConfigVars::get(null,'</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>')</xsl:text>
      </xsl:when>
      <!-- Session vars -->
      <xsl:when test="@scope = 'session'">
        <xsl:text>xarSessionGetVar('</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>')</xsl:text>
      </xsl:when>
      <!-- Request vars -->
      <xsl:when test="@scope = 'request'">
        <xsl:text>xarRequestGetVar('</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>')</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>''</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
</xsl:template>
</xsl:stylesheet>