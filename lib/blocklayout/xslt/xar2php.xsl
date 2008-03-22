<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [<!ENTITY nl "&#xd;&#xa;">]>
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
    The defaults for html type stuff
  -->
  <xsl:import href="defaults/html.xsl" />

  <!--
    Debugging templates, this (tries to) be more verbose.

    This allow to set the modus operandi to copy-through unknown tags, which
    is more common in these types of solutions. We import it as the last one
    so we can override just by inserting, instead of messing around with
    priorities.

    This include should by default be commented out.
  -->
  <!-- <xsl:import href="default/debug" /> -->

  <!--
    We produce an UTF-8 encoded XML document as output. As we compile the
    each document to a (hopefully valid) php script ultimately. We leave out
    the xml declaration, as PHP interprets that as the start of a PHP block.
  -->
  <xsl:output  method="xml" omit-xml-declaration="yes" indent="yes" encoding="UTF-8"/>

  <!--
    Parameters, we'd like as few as possible
  -->
  <xsl:param name="bl_dirname"/>
  <xsl:param name="bl_filename"/>

  <!--
    Spacing

    We want our output as compact as possible, so we start by stripping
    all non-significant whitespace (which will also collapse empty tags)
    and then correct for the elements which cause us trouble. In theory
    there shouldnt be any, but alas.
  -->
  <xsl:strip-space elements="*" />

  <!--
    Start of the transform usually starts with matching the root, so do we
  -->
  <xsl:template match="/"><xsl:apply-templates /></xsl:template>

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
  <!-- xar:block -->
  <xsl:include href="tags/block.xsl"/>
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
  <!-- xar:data-view/output/label etc. -->
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

  <!-- MLS functionality -->
  <xsl:include href="tags/mls.xsl"/>

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

  <!-- Others -->
  <xsl:include href="tags/element.xsl"/>

<!--
    Utility template for resolving text nodes. It recursively resolves
    #-pairs from left to right. Pre- and Post- hash content are treated
    as text.

    The param $expr contains the  value of a text node holding the expression
    to resolve.
    @todo leave #(1) constructs alone?
-->
<xsl:template name="resolveText" >
  <xsl:param name="expr"/>

  <!-- 
    <xsl:text>[EXPR]</xsl:text><xsl:value-of select="$expr"/><xsl:text>[END EXPR]</xsl:text>
  -->
  <xsl:variable name="nrOfHashes"
      select="string-length($expr) - string-length(translate($expr, '#', ''))"/>

  <xsl:choose>
    <!-- If we have zero or one hash, just output the text node -->
    <xsl:when test="$nrOfHashes &lt; 2">
      <!-- Escape any quote marks in the text and output -->
      <xsl:call-template name="replace">
        <xsl:with-param name="source" select="$expr"/>
      </xsl:call-template>
    </xsl:when>

    <!-- Resolve left to right -->
    <xsl:otherwise>
      <!-- two or more, so in general ....#....#....#....#.... etc. -->

      <!-- find the text up to the first "real" delimiter -->
      <xsl:variable name="delimiter-position">
        <xsl:call-template name="return-delimiter-position">
            <xsl:with-param name="expr" select="$expr"/>
        </xsl:call-template>
      </xsl:variable>

      <!-- [....]#....#.... : get the first part out of the way -->

      <xsl:value-of select="substring($expr,0,$delimiter-position)"/>

      <!-- Resolve the part in between -->
      <!-- Left at this point: ....#[....]#.... -->
      <xsl:variable name="expr-after" select="substring($expr,$delimiter-position + 1)"/>
      <xsl:if test="substring-before($expr-after,'#') !=''">
          <xsl:text>'.</xsl:text>
          <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="substring-before($expr-after,'#')"/>
          </xsl:call-template>
          <xsl:text>.'</xsl:text>
      </xsl:if>

      <!-- ....#....#[....#....#....etc.] -->
      <xsl:call-template name="resolveText">
        <xsl:with-param name="expr" select="substring-after($expr-after,'#')"/>
      </xsl:call-template>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!--
  For all text nodes, resolve expressions within
-->
<xsl:template match="text()">
  <xsl:call-template name="translateText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
</xsl:template>

<xsl:template name="translateText">
  <xsl:param name="expr" />
  <xsl:choose>
    <!-- We have an empty node -->
    <!-- Not real elegant. how to do better? (random) -->
    <xsl:when test="normalize-space($expr) = '&#160;'">
      <xsl:copy />
    </xsl:when>
    <xsl:when test="normalize-space($expr) = ' '">
      <xsl:copy />
    </xsl:when>
    <xsl:when test="normalize-space($expr) = ''">
      <xsl:copy />
    </xsl:when>

    <!-- We have a non-empty node -->
    <xsl:otherwise>
      <xsl:processing-instruction name="php">
        <xsl:text>echo xarML('</xsl:text>
        <xsl:call-template name="resolveText">
          <xsl:with-param name="expr" select="$expr"/>
        </xsl:call-template>
        <xsl:text>');</xsl:text>
      </xsl:processing-instruction>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- Stuff in pre tags should not be translated -->
<xsl:template match="pre/text()">
  <xsl:value-of select="."/>
</xsl:template>

<!-- Stuff in ml tags is laready in PHP mode -->
<xsl:template match="xar:ml/text()">
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
  Utility template to replace a string with another.

  The default is to replace ' with \', but
  by specifying the parameters, other replacements can be performed
  as well

  @param  string source contains the source string in which replacements are needed
  @param  string from   contains what needs to be replaced
  @param  string to     contains what will be the replacement.
  @return string with the replacements done.
-->
<xsl:template name="replace" >
  <!-- Specifiy the parameters -->
  <xsl:param name="source"/>
  <xsl:param name="from" select="&quot;'&quot;"/>
  <xsl:param name="to"   select="&quot;\'&quot;"/>

  <!-- Make it safe when there is no such character -->
  <xsl:choose>
    <xsl:when test="contains($source,$from)">
      <xsl:value-of select="substring-before($source,$from)"/>
      <xsl:value-of select="$to"/>
      <!-- Recurse -->
      <xsl:call-template name="replace">
        <xsl:with-param name="source" select="substring-after($source,$from)"/>
        <xsl:with-param name="from"   select="$from"/>
        <xsl:with-param name="to"     select="$to"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$source"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!--
  Utility template which takes a set of attribute nodes and creates a dd
  common array $key / $value style out of it.

  @param  nodeset a nodeset of attributes (usually from the current node, filtered)
  @return string array with key/value pairs representing each attribute=value pair
  @todo   hackery, prevents proper expression support.
-->
<xsl:template name="atts2args">
  <xsl:param name="nodeset"/>
  <xsl:text>array(</xsl:text>
  <xsl:if test="$nodeset">
    <xsl:for-each select="$nodeset">
      <xsl:text>'</xsl:text><xsl:value-of select="name()"/><xsl:text>' =&gt;</xsl:text>
      <xsl:choose>
        <xsl:when test="starts-with(normalize-space(.),'$') or not(string(number(.))='NaN')">
          <xsl:value-of select="."/><xsl:text>,</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>'</xsl:text><xsl:value-of select="."/><xsl:text>',</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:for-each>
  </xsl:if>
  <xsl:text>)</xsl:text>
</xsl:template>

  <xsl:template name="return-delimiter-position">
    <xsl:param name="expr"/>
    <xsl:param name="delimiter" select="'#'"/>
    <xsl:choose>
      <xsl:when test="contains($expr,$delimiter) = 0">
        <xsl:value-of select="string-length($expr) + 1" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:variable name="initial-position">
          <xsl:value-of select="string-length(substring-before($expr,$delimiter)) + 1" />
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="starts-with(substring-after($expr,$delimiter),$delimiter)">
            <xsl:variable name="add-on-position">
              <xsl:call-template name="return-delimiter-position">
                <xsl:with-param name="expr" select="substring($expr,$initial-position + 2)"/>
              </xsl:call-template>
            </xsl:variable>
            <xsl:value-of select="$initial-position + 1 + $add-on-position" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$initial-position" />
          </xsl:otherwise>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
