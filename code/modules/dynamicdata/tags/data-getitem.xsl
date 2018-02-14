<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-getitem">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="not(@object) and not(@objectname)">
        <!-- No object, gotta make one -->
        <xsl:text>$object = xarMod::apiFunc('dynamicdata','user','getitem',</xsl:text>
        <xsl:text>array_merge(array('getobject'=&gt;1),</xsl:text>
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" select="@*[name() != 'properties']"/>
        </xsl:call-template>
        <xsl:text>));</xsl:text>
        <xsl:text>$object-&gt;getItem(</xsl:text>
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" selec="@*[name() != 'properties']"/>
        </xsl:call-template>
        <xsl:text>);</xsl:text>
        <!-- the name attribute holds a variable name, not good, but it is like that -->
        <xsl:text>$</xsl:text>
        <xsl:value-of select="@properties"/><xsl:text>= $object-&gt;getProperties(</xsl:text>
          <xsl:call-template name="atts2args">
            <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='object']"/>
          </xsl:call-template>
        <xsl:text>);</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="substring(@object,1,1) = '$'">
            <!-- This a variable. we assume it's an object -->
            <xsl:value-of select="@object"/><xsl:text>-&gt;getItem(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:value-of select="@properties"/><xsl:text>=</xsl:text>
            <xsl:value-of select="@object"/><xsl:text>->getProperties(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='object']"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <!-- This a string. we assume it's an object name -->
            <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text> = DataObjectMaster::getObject(array('name' => '</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>'));</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>->getItem(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='objectname']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:value-of select="@properties"/>
            <xsl:text> = </xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>->getProperties(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='objectname']"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>