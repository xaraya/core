<?xml version="1.0"?>
<xsl:stylesheet version="1.0" 
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:xar="http://xaraya.com/xml2ddl"
                exclude-result-prefixes="xar">
  <!-- DDL is no XML -->
  <xsl:output method="text" />
  <xsl:strip-space elements="*"/>

  <!--
      We probably want to specify parameters at some point like:
      - vendor      - generate ddl compatible with $vendor backend
      - version     - generate ddl compatible with $vendor-$version backend
      - drop4create - drop tables before creating them
      - createdb    - create the database too
      - tableprefix - self explanatory
      - etc.
  -->
  <xsl:param name="vendor"  />
  <xsl:param name="version" />
  <xsl:param name="dbcreate"/>
  <xsl:param name="drop4create"/>
  <xsl:param name="to" />

  <!-- Variables, xslt style -->
  <xsl:variable name="CR">
<xsl:text>
</xsl:text>
  </xsl:variable>


  <xsl:template match="*">
    <xsl:message>
      <xsl:text />Updating schema <xsl:value-of select="/schema/@name"/> to '<xsl:value-of select="$to"/>
      <xsl:text>'.</xsl:text>
    </xsl:message>
    <xsl:if test="string($to)=''">
      <xsl:message terminate="yes">
        <xsl:text>No input file specified (parameter 'to')</xsl:text>
      </xsl:message>
    </xsl:if>

    <xsl:call-template name="xar:update">
      <xsl:with-param name="from" select="/node()" />
      <xsl:with-param name="to" select="document($to,/*)/node()" />
    </xsl:call-template>
  </xsl:template>


  <xsl:template name="xar:update">
    <xsl:param name="from" />
    <xsl:param name="to" />

    <xsl:call-template name="xar:checktable">
      <xsl:with-param name="from" select="$from/table" />
      <xsl:with-param name="to" select="$to/table" />
    </xsl:call-template>

  </xsl:template>


  <xsl:template name="xar:checktable">
    <xsl:param name="from" />
    <xsl:param name="to" />
    <xsl:for-each select="$to">
      <xsl:variable name="name"><xsl:value-of select="@name"/></xsl:variable>
      <xsl:choose>
        <xsl:when test="not($from[@name = $name])">
          <xsl:text>* Create: </xsl:text> 
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="xar:updatetable">
            <xsl:with-param name="from" select="$from[@name = $name]" />
            <xsl:with-param name="to" select="." />
          </xsl:call-template>          

          <xsl:call-template name="xar:checkconstraints">
            <xsl:with-param name="from" select="$from/constraints" />
            <xsl:with-param name="to" select="$to/constraints" />
          </xsl:call-template>

	  <xsl:text>* Exists: </xsl:text> 
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text>Table </xsl:text>
      <xsl:value-of select="@name"/>
      <xsl:value-of select="$CR"/>
    </xsl:for-each>

    <xsl:for-each select="$from">
      <xsl:variable name="name"><xsl:value-of select="@name"/></xsl:variable>
      <xsl:if test="not($to[@name = $name])">
        <xsl:text>* Remove: Table </xsl:text> 
        <xsl:value-of select="@name"/>
        <xsl:value-of select="$CR"/>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>


  <xsl:template name="xar:updatetable">
    <xsl:param name="from" />
    <xsl:param name="to" />

    <xsl:for-each select="$to/column">
      <xsl:variable name="name"><xsl:value-of select="@name"/></xsl:variable>
      <xsl:choose>
        <xsl:when test="not($from/column[@name = $name])">	
          <xsl:text> -> Create: </xsl:text> 
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="xar:updatecolumn">
            <xsl:with-param name="from" select="$from/column[@name = $name]" />
            <xsl:with-param name="to" select="." />
          </xsl:call-template>
          <xsl:variable name="differ">
            <xsl:call-template name="xar:compare-nodes">
              <xsl:with-param name="node1" select="$from/column[@name = $name]" />
              <xsl:with-param name="node2" select="." />
            </xsl:call-template>
          </xsl:variable>
          <xsl:choose>
            <xsl:when test="$differ='!'">
              <xsl:text> -> Update: </xsl:text> 
            </xsl:when>
            <xsl:otherwise>
              <xsl:text> -> Exists: </xsl:text> 
            </xsl:otherwise>
          </xsl:choose>
        </xsl:otherwise>
      </xsl:choose>

      <xsl:text>Column </xsl:text>
      <xsl:value-of select="@name"/>
      <xsl:value-of select="$CR"/>
    </xsl:for-each>

    <xsl:for-each select="$from/column">
      <xsl:variable name="name"><xsl:value-of select="@name"/></xsl:variable>
      <xsl:if test="not($to/column[@name = $name])">
        <xsl:text> -> Remove: Column </xsl:text> 
        <xsl:value-of select="@name"/>
        <xsl:value-of select="$CR"/>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>


  <xsl:template name="xar:updatecolumn">
    <xsl:param name="from" />
    <xsl:param name="to" />

    <xsl:for-each select="$to/*">
      <xsl:variable name="name"><xsl:value-of select="name()"/></xsl:variable>
      <xsl:variable name="differ">
        <xsl:call-template name="xar:compare-trees">
          <xsl:with-param name="node1" select="$from/*[name() = $name]" />
          <xsl:with-param name="node2" select="." />
        </xsl:call-template>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$differ='!'">
          <xsl:text>   - Update: </xsl:text> 
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>   - Exists: </xsl:text> 
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text>Field </xsl:text>
      <xsl:value-of select="$name"/>
      <xsl:value-of select="$CR"/>
    </xsl:for-each>

    <xsl:for-each select="$from/*">
      <xsl:variable name="name"><xsl:value-of select="name()"/></xsl:variable>
      <xsl:if test="not($to/*[name() = $name])">
        <xsl:text>   - Remove: Field </xsl:text> 
        <xsl:value-of select="$name"/>
        <xsl:value-of select="$CR"/>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>


  <xsl:template name="xar:checkconstraints">
    <xsl:param name="from" />
    <xsl:param name="to" />

    <xsl:for-each select="$to/*">
      <xsl:variable name="name"><xsl:value-of select="@name"/></xsl:variable>
      <xsl:variable name="differ">
        <xsl:call-template name="xar:compare-trees">
          <xsl:with-param name="node1" select="$from/*[@name = $name]" />
          <xsl:with-param name="node2" select="." />
        </xsl:call-template>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$differ='!' and not($from/*[@name = $name])">
          <xsl:text> x Create: </xsl:text> 
        </xsl:when>
        <xsl:when test="$differ='!' and $from/*[@name = $name]">
          <xsl:text> x Update: </xsl:text> 
        </xsl:when>
        <xsl:otherwise>
          <xsl:text> x Exists: </xsl:text> 
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text>Constraint </xsl:text>
      <xsl:value-of select="$name"/>
      <xsl:value-of select="$CR"/>
    </xsl:for-each>

    <xsl:for-each select="$from/*">
      <xsl:variable name="name"><xsl:value-of select="@name"/></xsl:variable>
      <xsl:if test="not($to/*[@name = $name])">
        <xsl:text> x Remove: Constraint </xsl:text> 
        <xsl:value-of select="$name"/>
        <xsl:value-of select="$CR"/>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>


<xsl:template name="xar:compare-trees">
   <xsl:param name="node1" />
   <xsl:param name="node2" />

   <xsl:variable name="differ-root">
     <xsl:call-template name="xar:compare-nodes">
       <xsl:with-param name="node1" select="$node1" />
       <xsl:with-param name="node2" select="$node2" />
     </xsl:call-template>
   </xsl:variable>

   <xsl:variable name="differ-nodes">
     <xsl:if test="$differ-root='='">
       <xsl:for-each select="$node2/node()">
         <xsl:variable name="position" select="position()"/>
         <xsl:call-template name="xar:compare-trees">
           <xsl:with-param name="node1" select="$node1/node()[position() = $position]" />
           <xsl:with-param name="node2" select="." />
         </xsl:call-template>
       </xsl:for-each>
     </xsl:if>
   </xsl:variable>
   
<xsl:choose>
     <xsl:when test="$differ-root='!' or contains($differ-nodes,'!')">!</xsl:when>
     <xsl:otherwise>=</xsl:otherwise>
   </xsl:choose>
</xsl:template>


<!-- Comparing single nodes: 
     if $node1 and $node2 are equivalent then the template creates a 
     text node "=" otherwise a text node "!" 
     LGPL (c) Oliver Becker, 2002-07-05
     obecker@informatik.hu-berlin.de
     http://www2.informatik.hu-berlin.de/~obecker/XSLT/merge/merge.xslt.html
-->
<xsl:template name="xar:compare-nodes">
   <xsl:param name="node1" />
   <xsl:param name="node2" />
   <xsl:variable name="type1">
      <xsl:apply-templates mode="xar:detect-type" select="$node1" />
   </xsl:variable>
   <xsl:variable name="type2">
      <xsl:apply-templates mode="xar:detect-type" select="$node2" />
   </xsl:variable>

   <xsl:choose>
      <!-- Are $node1 and $node2 element nodes with the same name? -->
      <xsl:when test="$type1='element' and $type2='element' and local-name($node1)=local-name($node2) and namespace-uri($node1)=namespace-uri($node2)">
         <!-- Comparing the attributes -->
         <xsl:variable name="diff-att">
            <!-- same number ... -->
            <xsl:if test="count($node1/@*)!=count($node2/@*)">.</xsl:if>
            <!-- ... and same name/content -->
            <xsl:for-each select="$node1/@*">
               <xsl:if test="not($node2/@* [local-name()=local-name(current()) and namespace-uri()=namespace-uri(current()) and .=current()])">.</xsl:if>
            </xsl:for-each>
         </xsl:variable>
         <xsl:choose>
            <xsl:when test="string-length($diff-att)!=0">!</xsl:when>
            <xsl:otherwise>=</xsl:otherwise>
         </xsl:choose>
      </xsl:when>

      <!-- Other nodes: test for the same type and content -->
      <xsl:when test="$type1!='element' and $type1=$type2 and name($node1)=name($node2) and ($node1=$node2 or (normalize-space($node1)= normalize-space($node2)))">=</xsl:when>

      <!-- Otherwise: different node types or different name/content -->
      <xsl:otherwise>!</xsl:otherwise>
   </xsl:choose>
</xsl:template>


<!-- Type detection, thanks to M. H. Kay -->
<xsl:template match="*" mode="xar:detect-type">element</xsl:template>
<xsl:template match="text()" mode="xar:detect-type">text</xsl:template>
<xsl:template match="comment()" mode="xar:detect-type">comment</xsl:template>
<xsl:template match="processing-instruction()" mode="xar:detect-type">pi</xsl:template>


  <!-- File header -->
  <xsl:template name="topheader">
    <xsl:param name="dbname"/>
    <xsl:param name="remarks"/>
/* ---------------------------------------------------------------------------
 * Model generated from: TODO
 * Name                : <xsl:value-of select="$dbname"/>
 * Vendor              : <xsl:value-of select="$vendor"/>
 * Date                : TODO
 * Remarks:            :
 *   <xsl:value-of select="$remarks"/>
 */
</xsl:template>

<!-- Context sensitive header, reacts on name and element-name -->
<xsl:template name="dynheader">
/* ---------------------------------------------------------------------------
 * <xsl:value-of select="local-name()"/>: <xsl:value-of select="@name" />
 */
</xsl:template>

<!-- Easy TODO inclusion -->
<xsl:template name="TODO">
  <xsl:text>/* TODO: Template for: </xsl:text>
  <xsl:value-of select="local-name()"/>
  <xsl:text> </xsl:text>
  <xsl:value-of select="@name"/>
  <xsl:text> handling (vendor: </xsl:text>
  <xsl:value-of select="$vendor"/>
  <xsl:text>) */
</xsl:text>
</xsl:template>

<!-- Default create database statement -->
<!--
<xsl:template match="schema">
  <xsl:call-template name="dynheader"/>
  <xsl:if test="$dbcreate">
    <xsl:text>CREATE DATABASE </xsl:text><xsl:value-of select="@name"/>;
  </xsl:if>
<xsl:apply-templates/>
</xsl:template>
-->

<!--  @todo make this a generic template? -->
<xsl:key name="columnid" match="table/column" use="@id"/>
<xsl:template name="columnrefscsv">
  <xsl:for-each select="columnref">
    <xsl:value-of select="key('columnid',@id)/@name"/>
    <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
  </xsl:for-each>
</xsl:template>

<!-- Index base create is pretty portable
     @todo put these back together?
-->
<xsl:template match="table/constraints/index">
  <xsl:text>CREATE INDEX </xsl:text>
  <xsl:value-of select="@name"/> ON <xsl:value-of select="../../@name"/> (<xsl:call-template name="columnrefscsv"/>);
</xsl:template>

<xsl:template match="table/constraints/unique">
  <xsl:text>CREATE UNIQUE INDEX </xsl:text>
  <xsl:value-of select="@name"/> ON <xsl:value-of select="../../@name"/> (<xsl:call-template name="columnrefscsv"/>);
</xsl:template>

<!-- Primary key creation -->
<xsl:template match="table/constraints/primary">
  <xsl:text>ALTER TABLE </xsl:text>
  <xsl:value-of select="../../@name"/>
  <xsl:text> ADD PRIMARY KEY (</xsl:text><xsl:call-template name="columnrefscsv"/>);
</xsl:template>

<xsl:template match="schema/description"/> <!-- @todo : find out if this has a useful thing -->
<xsl:template match="index/description"/> <!-- @todo : find out if this has a useful thing -->
</xsl:stylesheet>
