<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

  <xsl:template match="xar:calendar-decorator">
    <xsl:processing-instruction name="php">
      <xsl:text>$class = 'Calendar_Decorator_</xsl:text>
      <xsl:value-of select="@decorator"/>
      <xsl:text>';</xsl:text>
      <xsl:text>if(!class_exists($class)) {</xsl:text>
      <xsl:text>$file = CALENDAR_ROOT."Decorator/</xsl:text>
      <xsl:value-of select="@decorator"/>
      <xsl:text>.php";</xsl:text>
      <xsl:text>require_once($file);</xsl:text>
      <xsl:text>}$</xsl:text>
      <xsl:value-of select="@name"/>
      <xsl:text>=new $class($object);</xsl:text>
    </xsl:processing-instruction>
  </xsl:template>

</xsl:stylesheet>