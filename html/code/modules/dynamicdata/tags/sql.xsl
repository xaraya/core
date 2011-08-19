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
  <xsl:template match="xar:select">
    <xsl:text>sys::import('xaraya.structures.query')</xsl:text>
    <xsl:text>=new Query()</xsl:text>
  </xsl:template>
-->

  <xsl:template match="xar:data-getitems/xar:select">
    <!-- Get the object dataquery -->
    <xsl:text>$__q=$__object->dataquery;</xsl:text>

    <!-- Process anything below -->
    <xsl:apply-templates />
  </xsl:template>

  <xsl:template match="xar:select">
    <xsl:processing-instruction name="php">
      <!-- First get the object whose the query we want to select from-->
      <xsl:text>$__object=</xsl:text>
      <xsl:choose>
        <xsl:when test="@object">
          <xsl:value-of select="@object"/>
          <xsl:text>;</xsl:text>
        </xsl:when>
        <xsl:when test="@objectname">
         <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
          <xsl:text>$__object=DataObjectMaster::getObjectList(array('name'=>'</xsl:text>
          <xsl:value-of select="@objectname"/>
          <xsl:text>'));</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>throw new Exception('An object or objectname is required');</xsl:text>
        </xsl:otherwise>
      </xsl:choose>

      <!-- Get the object dataquery -->
      <xsl:text>$__q=$__object->dataquery;</xsl:text>

      <!-- Process anything below -->
      <xsl:apply-templates />

      <!-- Assign the object dataquery to a name if one is passed-->
      <xsl:if test="@name">
        <xsl:text>$</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>=&amp;$__q;</xsl:text>
      </xsl:if>

      <!-- If we have an $items var run the query -->
      <xsl:if test="@items">
        <xsl:text>$__q->run();</xsl:text>
        <xsl:value-of select="@items"/>
        <xsl:text>=$__q->output();</xsl:text>
      </xsl:if>
    </xsl:processing-instruction>
  </xsl:template>

  <xsl:template name="xar:andconditions">
      <!-- Set up the array to hold the conditions -->
      <xsl:text>$__conds[$i]=array();</xsl:text>

      <!-- Process the conditions -->
      <xsl:apply-templates />

      <!-- Assign to the dataquery -->
      <xsl:text>if($i>1)$__conds[$i-1][]=$__q->pqand($__conds[$i]);else$__q->qand($__conds[$i]);</xsl:text>
  </xsl:template>

  <xsl:template name="xar:orconditions">
      <!-- Set up the array to hold the conditions -->
      <xsl:text>$__conds[$i]=array();</xsl:text>

      <!-- Process the conditions -->
      <xsl:apply-templates />

      <!-- Assign to the dataquery -->
      <xsl:text>if($i>1)$__conds[$i-1][]=$__q->pqor($__conds[$i]);else$__q->qor($__conds[$i]);</xsl:text>
  </xsl:template>

  <xsl:template match="xar:select/xar:andconditions">
    <xsl:text>$i=1;</xsl:text>
    <xsl:call-template name="xar:andconditions"/>
  </xsl:template>
  <xsl:template match="xar:orconditions/xar:andconditions">
    <xsl:text>$i++;</xsl:text>
    <xsl:call-template name="xar:andconditions"/>
    <xsl:text>$i--;</xsl:text>
  </xsl:template>
  <xsl:template match="xar:select/xar:orconditions">
    <xsl:text>$i=1;</xsl:text>
    <xsl:call-template name="xar:orconditions"/>
  </xsl:template>
  <xsl:template match="xar:andconditions/xar:orconditions">
    <xsl:text>$i++;</xsl:text>
    <xsl:call-template name="xar:orconditions"/>
    <xsl:text>$i--;</xsl:text>
  </xsl:template>

  <xsl:template name="xar:condition">
      <xsl:text>$__conds[$i][]=$__q-></xsl:text>
      <xsl:choose>
        <xsl:when test="@operator='=' or @operator='eq'">
          <xsl:text>peq</xsl:text>
        </xsl:when>
        <xsl:when test="@operator='!=' or @operator='ne'">
          <xsl:text>pne</xsl:text>
        </xsl:when>
        <xsl:when test="@operator='>' or @operator='gt'">
          <xsl:text>pgt</xsl:text>
        </xsl:when>
        <xsl:when test="@operator='>=' or @operator='ge'">
          <xsl:text>pge</xsl:text>
        </xsl:when>
        <xsl:when test="@operator='&lt;' or @operator='lt'">
          <xsl:text>plt</xsl:text>
        </xsl:when>
        <xsl:when test="@operator='&lt;=' or @operator='le'">
          <xsl:text>ple</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>p</xsl:text>
          <xsl:value-of select="@operator"/>
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text>($__object->properties['</xsl:text>
      <xsl:value-of select="@property"/>
      <xsl:text>']->source,</xsl:text>
      <xsl:value-of select="current()"/>
      <xsl:text>);</xsl:text>
  </xsl:template>

  <xsl:template match="xar:andconditions/xar:condition">
    <xsl:call-template name="xar:condition"/>
  </xsl:template>

  <xsl:template match="xar:orconditions/xar:condition">
    <xsl:call-template name="xar:condition"/>
  </xsl:template>

</xsl:stylesheet>