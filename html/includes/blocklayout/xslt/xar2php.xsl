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
    See DEVblocklayout.txt in the repository for DEV notes.
  -->

  <!--
    Imports

    Imports are like default templates for processing. They get a lower priority
    than anything in this file, and as such can be overridden without anything
    other than just defining the transform templates here.

    This ALSO means that by making the BL compiler accept a custom xsl file
    which imports THIS file has the same effect as having your own customisation
    file in one place, by inserting your templates in that file. :-) :-)

    Nuf said, lets begin.
  -->

  <!--
    The default identity transform which will just copy over anything we dont match
  -->
  <xsl:import href="defaults/identity.xsl"/>

  <!--
    Parameters, we'd like as few as possible
  -->
  <xsl:param name="bl_dirname"/>
  <xsl:param name="bl_filename"/>


  <!--
    We view php as one large processing instruction of xml without the xml
    declaration
  -->
  <xsl:output method="xml" omit-xml-declaration="yes" indent="yes" />

  <!--
    Spacing

    We want our output as compact as possible, so we start by stripping
    all non-significant whitespace (which will also collapse empty tags)
    and then correct for the elements which cause us trouble. In theory
    there shouldnt be any, but alas.
  -->
  <xsl:strip-space elements="*" />
  <!--
    Problematic elements

    - empty div/ elements bork everything
  -->
  <xsl:preserve-space elements="div"/>

  <!--
    Start of the transform usually starts with matching the root, so do we
  -->
  <xsl:template match="/">
    <xsl:apply-templates />
  </xsl:template>

  <!--
    First we do some simple stuff to get rid of things we dont really care
    about yet
  -->
  <!-- xar processing instructions, ignore them for now -->
  <xsl:template match="/processing-instruction('xar')"/>
  <!-- xml comments are ignored -->
  <xsl:template match="comment()"/>

  <!--
    Xaraya specific tag implementations are brought in here.

    @todo: should we use import or include here?
  -->
  <!-- xar:additional-styles -->
  <xsl:include href="tags/additional-styles.xsl"/>
  <!-- xar:base-include-javascript -->
  <xsl:include href="tags/base-include-javascript.xsl"/>
  <!-- xar:base-render-javascript -->
  <xsl:include href="tags/base-render-javascript.xsl"/>
  <!-- xar:blockgroup -->
  <xsl:include href="tags/blockgroup.xsl"/>
  <!-- xar:blocklayout -->
  <xsl:include href="tags/blocklayout.xsl"/>
  <!-- xar:break -->
  <xsl:include href="tags/break.xsl"/>
  <!-- xar:comment -->
  <xsl:include href="tags/comment.xsl"/>
  <!-- xar:continue -->
  <xsl:include href="tags/continue.xsl"/>

  <!-- TODO: organize this -->
  <!-- xar:data-view/output/label -->
  <xsl:include href="tags/data.xsl"/>

  <!-- xar:else -->
  <xsl:include href="tags/else.xsl"/>
  <!-- xar:elseif -->
  <xsl:include href="tags/elseif.xsl"/>
  <!-- xar:event -->
  <xsl:include href="tags/event.xsl"/>
  <!-- xar:for -->
  <xsl:include href="tags/for.xsl"/>
  <!-- xar:foreach -->
  <xsl:include href="tags/foreach.xsl"/>
  <!-- xar:if -->
  <xsl:include href="tags/if.xsl"/>
  <!-- xar:loop -->
  <xsl:include href="tags/loop.xsl"/>
  <!-- xar:ml -->
  <xsl:include href="tags/ml.xsl"/>
  <!-- xar:mlstring -->
  <xsl:include href="tags/mlstring.xsl"/>
  <!-- xar:mlvar -->
  <xsl:include href="tags/mlvar.xsl"/>
  <!-- xar:module -->
  <xsl:include href="tags/module.xsl"/>
  <!-- xar:sec -->
  <xsl:include href="tags/sec.xsl"/>
  <!-- xar:set -->
  <xsl:include href="tags/set.xsl"/>
  <!-- xar:style -->
  <xsl:include href="tags/style.xsl"/>
  <!-- xar:template -->
  <xsl:include href="tags/template.xsl"/>
  <!-- xar:var -->
  <xsl:include href="tags/var.xsl" />
  <!-- xar:while -->
  <xsl:include href="tags/while.xsl"/>

<!-- text nodes in php mode -->
<xsl:template match="xar:set/text()">
    <xsl:choose>
      <xsl:when test="substring(normalize-space(.),1,1) = '#'">
        <!-- The string starts with # so, let's resolve it -->
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="normalize-space(.)"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <!-- This is the point where we can do automatic translation
             of textnodes without requiring xar:mlstring
             Erm, no its not, the xsl changed, need to re-arrange this.
        -->
        <xsl:copy/>
      </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<!--
    Utility template, taks a parameter 'expr' which contains the
    value of a text node. It recursively resvolves #-pairs from left
    to right. Pre- and Post- hash content are treated as text.
-->
<xsl:template name="resolveText" >
  <xsl:param name="expr"/>

  <xsl:variable name="nrOfHashes"
      select="string-length($expr) - string-length(translate($expr, '#', ''))"/>

  <xsl:choose>
    <!-- If we have zero or one hash, just output the text node -->
    <xsl:when test="$nrOfHashes &lt; 2">
        <xsl:value-of select="$expr"/>
    </xsl:when>
    <!-- Resolve left to right -->
    <xsl:otherwise>
      <!-- more than two, so in general ....#....#....#....#.... etc. -->

      <!-- [....]#....#.... : get the first part out of the way -->
      <xsl:value-of select="substring-before($expr,'#')"/>

      <!-- Resolve the part in between -->
      <!-- Left at this point: ....#[....]#.... -->
      <xsl:if test="substring-before(substring-after($expr,'#'),'#') !=''">
        <xsl:processing-instruction name="php">
          <xsl:text>echo </xsl:text>
          <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="substring-before(substring-after($expr,'#'),'#')"/>
          </xsl:call-template>
          <xsl:text>;</xsl:text>
        </xsl:processing-instruction>
      </xsl:if>

      <!-- ....#....#[....#....#....etc.] -->
      <xsl:call-template name="resolveText">
        <xsl:with-param name="expr" select="substring-after(substring-after($expr,'#'),'#')"/>
      </xsl:call-template>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!--
  For all text nodes, resolve expressions within
-->
<xsl:template match="text()">
  <xsl:call-template name="resolveText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
</xsl:template>

<!-- Expression resolving in nodes-->
<xsl:template name="resolvePHP">
  <xsl:param name="expr"/>
  <xsl:value-of
        select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string($expr))"
        disable-output-escaping="yes"/>
</xsl:template>

<!--
  Any xar tag we dont match, we highlight in the output, i.e. turn it into a text node
-->
<xsl:template match="xar:*">
  <pre class="xsltdebug">
    <xsl:text>MISSING TAG IMPLEMENTATION:
&lt;</xsl:text>
    <xsl:value-of select="name()"/>
    <xsl:text> </xsl:text>
    <xsl:for-each select="@*">
      <xsl:value-of select="name()"/>
      <xsl:text>="</xsl:text>
      <xsl:value-of select="."/>
      <xsl:text>" </xsl:text>
    </xsl:for-each>
    <xsl:text>/&gt;</xsl:text>
    <xsl:apply-imports />
  </pre>

</xsl:template>

</xsl:stylesheet>
