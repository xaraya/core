<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-getitems">
    <xsl:processing-instruction name="php">
      <xsl:choose>
        <xsl:when test="not(@object) and not(@objectname)">
            <!-- No object gotta make one -->
            <xsl:text>list(</xsl:text>
            <xsl:text>$</xsl:text>
            <xsl:value-of select="@properties"/>
            <xsl:text>,$</xsl:text>
            <xsl:value-of select="@values"/>
            <xsl:text>) = xarMod::apiFunc('dynamicdata','user','getitemsforview',</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='values']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:choose>
              <xsl:when test="substring(@object,1,1) = '$'">
                <!-- This a variable. we assume it's an object -->
                <xsl:text>$__items=</xsl:text>
                <xsl:value-of select="@object"/><xsl:text>-&gt;getItems(</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='values' and name()!='object']"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
                <xsl:if test="@values">
                  <xsl:value-of select="@values"/><xsl:text>=$__items;</xsl:text>
                </xsl:if>
                
                <xsl:if test="@properties">
                  <xsl:value-of select="@properties"/><xsl:text>=</xsl:text>
                  <xsl:value-of select="@object"/><xsl:text>->getProperties();</xsl:text>
                </xsl:if>
              </xsl:when>
              <xsl:otherwise>
                <!-- This a string. we assume it's an object name -->
                <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
                <xsl:text>$__object</xsl:text>
                <xsl:text>=DataObjectMaster::getObjectList(array('name'=>'</xsl:text>
                <xsl:value-of select="@objectname"/>
                <xsl:text>'));</xsl:text>
                
                <xsl:text>$__items=$__object</xsl:text>
                <xsl:text>-&gt;getItems(</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'properties' and name()!='values' and name()!='objectname']"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
                <xsl:if test="@values">
                  <xsl:value-of select="@values"/><xsl:text>=$__items;</xsl:text>
                </xsl:if>

                <xsl:if test="@properties">
                  <xsl:value-of select="@properties"/><xsl:text>=</xsl:text>
                  <xsl:text>$__object</xsl:text>
                  <xsl:text>->getProperties();</xsl:text>
                </xsl:if>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:otherwise>
      </xsl:choose>
    </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>