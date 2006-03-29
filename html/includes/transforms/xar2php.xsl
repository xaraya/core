<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "includes/transforms/xar_entities.dtd">

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                xmlns:xar="http://xaraya.com/2004/blocklayout"   
                xmlns:php="http://php.net/xsl" 
                exclude-result-prefixes="php xar">
  <!-- 
       Issues to be researched:
       
       - how to cross the border? i.e. how do parameters from the module get
       passed into the xslt processor
       - how do we create a suiteable test suite (make a compilation of the core templates?)
       - can we make a stub inserting some random values for the template vars, so we can compare somehow
       - is merging with other output namespaces just a question of copying output (xhtml in our case)
       - how do we handle #$var# constructs?
       - the xarBLCompiler.php does some processing here and there, which of these need to stay in php, which
       of them can be done by xsl?
  -->
  
  <!-- Parameters -->
  <xsl:param name="bl_dirname"/>
  <xsl:param name="bl_filename"/>
  
  <!-- Start of stylesheet -->
  <xsl:variable name="nl"><xsl:text>
</xsl:text></xsl:variable>
<xsl:template name="phpon"><xsl:text disable-output-escaping="yes">&lt;?php 
</xsl:text></xsl:template>
<xsl:template name="phpoff"><xsl:text disable-output-escaping="yes">?&gt;
</xsl:text></xsl:template>

<!-- We view php as one large processing instruction of xml without the xml declaration -->
<xsl:output method="xml" omit-xml-declaration="yes" indent="yes" />
<xsl:strip-space elements="*" />

<!-- We're creating a php document --> 
<xsl:template match="/">
  <xsl:apply-templates />
</xsl:template>

<!-- 
     First we do some simple stuff to get rid of 
     things we dont really care about yet
-->

<!-- xar processing instructions, ignore them for now -->
<xsl:template match="/processing-instruction('xar')"/>
<!-- xml comments are ignored -->
<xsl:template match="comment()"/>

<!-- 4.3.1 <xar:blocklayout/> -->
<xsl:template match="xar:blocklayout">
  <xsl:call-template name="phpon"/>
  <xsl:text>  $_bl_locale  = xarMLSGetCurrentLocale();
  $_bl_charset = xarMLSGetCharsetFromLocale($_bl_locale);
  header("Content-Type:</xsl:text><xsl:value-of select="@content"/><xsl:text>; charset = $_bl_charset");</xsl:text>
  <xsl:value-of select="$nl"/>
  <xsl:call-template name="phpoff"/>
  <!-- Generate the doctype -->
  <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"&gt;
  </xsl:text>
  <xsl:apply-templates>
    <xsl:with-param name="php" select="'off'"/>
  </xsl:apply-templates>
</xsl:template>

<!-- 4.3.5 <xar:comment/> -->
<xsl:template match="xar:comment">
  <xsl:param name="php"/>
  <xsl:if test="$php = 'on'">
    <xsl:call-template name="phpoff"/>
  </xsl:if>
  <xsl:comment>
    <xsl:copy-of select="."/>
  </xsl:comment>
  <xsl:if test="$php = 'on'">
    <xsl:call-template name="phpon"/>
  </xsl:if>
</xsl:template>

<!-- xar:set -->
<xsl:template name="xar-set" match="xar:set">
  <xsl:param name="php"/>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpon"/>
  </xsl:if>
  <xsl:text>$</xsl:text><xsl:value-of select="@name"/><xsl:text> = </xsl:text>
  <xsl:apply-templates>
    <xsl:with-param name="php" select="'on'"/>
  </xsl:apply-templates>
  <xsl:text>;</xsl:text>
  <xsl:text>$_bl_data['</xsl:text>
  <xsl:value-of select="@name"/><xsl:text>'] = $</xsl:text><xsl:value-of select="@name"/><xsl:text>;</xsl:text>
  <xsl:value-of select="$nl"/>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpoff"/>
  </xsl:if>
  
</xsl:template>

<!-- xar:blockgroup -->
<xsl:template name="xar-blockgroup" match="xar:blockgroup">
  <xsl:param name="php"/>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpon"/>
    <xsl:text>echo </xsl:text>
  </xsl:if>
  <xsl:choose>
    <xsl:when test="child::node()">
      <xsl:text>$_bl_blockgroup_template = '></xsl:text>
      <xsl:value-of select="@template"/>
      <xsl:text>';</xsl:text>
      <xsl:value-of select="$nl"/>
      <xsl:apply-templates>
        <xsl:with-param name="php" select="'on'"/>
      </xsl:apply-templates>
      <xsl:text>unset($_bl_blockgroup_template);</xsl:text>
    </xsl:when>
    <xsl:when test="not(child::node())">
      <xsl:text>xarBlock_renderGroup('</xsl:text><xsl:value-of select="@name"/>
      <xsl:text>');</xsl:text>
      <xsl:value-of select="$nl"/>
      <xsl:apply-templates>
        <xsl:with-param name="php" select="'on'"/>
      </xsl:apply-templates>
    </xsl:when>
    <xsl:value-of select="$nl"/>
  </xsl:choose>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpoff"/>
  </xsl:if>
</xsl:template>

<!-- xar:var -->
<xsl:template name="xar-var" match="xar:var">
  <xsl:param name="php"/>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpon"/>
    <xsl:text>echo </xsl:text>
  </xsl:if>
  <xsl:choose>
    <xsl:when test="@scope = 'module'">
      <xsl:text>xarModGetVar('</xsl:text><xsl:value-of select="@module"/><xsl:text>', '</xsl:text>
      <xsl:value-of select="@name"/><xsl:text>');</xsl:text>
    </xsl:when>
    <xsl:when test="@scope = 'local' or not(@scope)">
      <xsl:text>$</xsl:text><xsl:value-of select="@name"/><xsl:text>;</xsl:text>
    </xsl:when>
  </xsl:choose>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpoff"/>
  </xsl:if>
  <xsl:value-of select="$nl"/>
</xsl:template>

<!-- xar:template -->
<xsl:template name="xar-template" match="xar:template">
  <xsl:choose>
    <!-- If the template tag does not contain anything, treate as in 1.x -->
    <xsl:when test="not(node())">
      <xsl:param name="php"/>
      <xsl:if test="$php = 'off'">
        <xsl:call-template name="phpon"/>
      </xsl:if>
      <xsl:text>echo </xsl:text>
      <xsl:choose>
        <xsl:when test="@type='theme'">
          <xsl:text>xarTpl_includeThemeTemplate('</xsl:text>
          <xsl:value-of select="@file"/>
          <xsl:text>',$_bl_data);</xsl:text>
        </xsl:when>
        <xsl:when test="@type='module'">
          <xsl:text>xarTpl_includeModuleTemplate($_bl_module_name, '</xsl:text>
          <xsl:value-of select="@file"/>
          <xsl:text>',$_bl_data);</xsl:text>
        </xsl:when>
        <xsl:when test="@type='system'">
          <!-- The name is to be interpreted relative to the file we're parsing now -->
          <xsl:text>xarTplFile('</xsl:text>
          <xsl:value-of select="$bl_dirname"/><xsl:text>/</xsl:text><xsl:value-of select="@file"/>
          <xsl:text>',$_bl_data );</xsl:text>
        </xsl:when>
      </xsl:choose>
      <xsl:if test="$php = 'off'">
        <xsl:call-template name="phpoff"/>
      </xsl:if>
      <xsl:value-of select="$nl"/>
    </xsl:when>
    <xsl:otherwise>
      <!-- It's the root tag of a template file, no need to do anything yet -->
      <xsl:apply-templates>
        <xsl:with-param name="php" select="'off'"/>
      </xsl:apply-templates>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- xar:if -->
<xsl:template match="xar:if">
  <xsl:param name="php"/>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpon"/>
  </xsl:if>
  <xsl:text>if(</xsl:text>
  <xsl:value-of 
      select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string(@condition))"
      disable-output-escaping="yes"/>
  <xsl:text>) {</xsl:text>
  <xsl:value-of select="$nl"/>
  <xsl:apply-templates>
    <xsl:with-param name="php" select="'on'"/>
  </xsl:apply-templates>
  <xsl:text>}</xsl:text>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpoff"/>
  </xsl:if>
  <xsl:value-of select="$nl"/>
</xsl:template>

<!-- xar:elseif -->
<xsl:template match="xar:if/xar:elseif">
  <xsl:param name="php"/>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpon"/>
  </xsl:if>
  <xsl:text>} elseif(</xsl:text>
  <xsl:value-of 
      select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string(@condition))"
      disable-output-escaping="yes"/>
  <xsl:text>) {</xsl:text>
  <xsl:if test="$php = 'off'">
    <xsl:call-template name="phpoff"/>
  </xsl:if>
  </xsl:template>

  <!-- xar:else -->
  <xsl:template match="xar:if/xar:else">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
    </xsl:if>
    <xsl:text>} else {</xsl:text>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>

  <!-- xar:foreach -->
  <xsl:template match="xar:foreach">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
    </xsl:if>
    <xsl:text>foreach(</xsl:text>
    <xsl:choose>
      <xsl:when test="@key!='' and @value!='' ">
        <xsl:value-of select="@in"/><xsl:text> as </xsl:text><xsl:value-of select="@key"/>
        <xsl:text disable-output-escaping="yes"> =&gt; </xsl:text><xsl:value-of select="@value"/>
      </xsl:when>
      <xsl:when test="@value!=''">
        <xsl:value-of select="@in"/><xsl:text> as </xsl:text><xsl:value-of select="@value"/>
      </xsl:when>
      <xsl:when test="@key!=''">
        <xsl:text>array_keys(</xsl:text>
        <xsl:value-of select="@in"/>
        <xsl:text>) as </xsl:text>
        <xsl:value-of select="@key"/>
      </xsl:when>
    </xsl:choose>
    <xsl:text>) {
    </xsl:text>
    <xsl:apply-templates>
      <xsl:with-param name="php" select="'on'"/>
    </xsl:apply-templates>
    }
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>

  <!-- xar:mlstring -->
  <xsl:template match="xar:mlstring">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
      <xsl:text>echo </xsl:text>
    </xsl:if>
    <xsl:text>xarML('</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::escape',string(.))"
        disable-output-escaping="yes" />
        <xsl:text>');</xsl:text>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>

  <!-- xar:ml -->
  <xsl:template match="xar:ml">
    <xsl:apply-templates/>
  </xsl:template>

  <!-- xar:mlvar -->
  <xsl:template match="xar:mlvar"/>

  <!-- xar:style -->
  <xsl:template match="xar:style">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
    </xsl:if>
    <xsl:text>xarModAPIFunc('themes','user','register',array(</xsl:text>
    <xsl:if test="@file != ''">
      <xsl:text disable-output-escaping="yes">'file' =&gt;'</xsl:text><xsl:value-of select="@file"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@scope != ''">
      <xsl:text disable-output-escaping="yes">'scope' =&gt;'</xsl:text><xsl:value-of select="@scope"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@type != ''">
      <xsl:text disable-output-escaping="yes">'type' =&gt;'</xsl:text><xsl:value-of select="@type"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@media != ''">
      <xsl:text disable-output-escaping="yes">'media' =&gt;'</xsl:text><xsl:value-of select="@media"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@alternate != ''">
      <xsl:text disable-output-escaping="yes">'alternate' =&gt;'</xsl:text><xsl:value-of select="@alternate"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@title != ''">
      <xsl:text disable-output-escaping="yes">'title' =&gt;'</xsl:text><xsl:value-of select="@title"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@method != ''">
      <xsl:text disable-output-escaping="yes">'method' =&gt;'</xsl:text><xsl:value-of select="@method"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@condition != ''">
      <xsl:text disable-output-escaping="yes">'condition' =&gt;'</xsl:text><xsl:value-of select="@condition"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:text>));</xsl:text>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>

  <!-- xar:additional-styles -->
  <xsl:template match="xar:additional-styles">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
    </xsl:if>
    <xsl:text disable-output-escaping="yes">echo xarModAPIFunc('themes','user','deliver',array('method' =&gt; 'render','base' =&gt; 'theme'));</xsl:text>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>

  <xsl:template match="xar:loop">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
    </xsl:if>
    <xsl:text disable-output-escaping="yes">$loop_1=(object) null; $loop_1-&gt;index=-1;$loop_1-&gt;number=1;
foreach(</xsl:text><xsl:value-of select="@name"/>
    <xsl:text disable-output-escaping="yes"> as $loop_1-&gt;key =&gt; $loop_1-&gt;item ) {
  $loop=(object) null; $loop_1-&gt;index++;
  $loop-&gt;index = $loop_1-&gt;index;
  $loop-&gt;key = $loop_1-&gt;key;
  $loop-&gt;item =&amp; $loop_1-&gt;item;
  $loop-&gt;number = $loop_1-&gt;number;</xsl:text>
    <xsl:apply-templates>
      <xsl:with-param name="php" select="'on'"/>
    </xsl:apply-templates>
    <xsl:text>}</xsl:text>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
      <xsl:text>echo </xsl:text>
    </xsl:if>
  </xsl:template>

  <!-- text nodes in php mode -->
  <xsl:template match="*/text()">
    <xsl:param name="php"/>
    <xsl:choose>
      <xsl:when test="substring(.,1,1) = '#'">
        <!-- The string starts with # so, let's resolve it -->
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="."/>
          <xsl:with-param name="php" select="$php"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="xar:module">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
    </xsl:if>
    <xsl:choose>
      <xsl:when test="string-length(@module) = 0">
        <xsl:text>echo $_bl_mainModuleOutput;</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <!-- module attribute has a value -->
        <xsl:text>echo xarModFunc('</xsl:text>
        <xsl:value-of select="@module"/><xsl:text>','</xsl:text>
        <xsl:choose>
          <xsl:when test="string-length(@type) = 0">
            <xsl:text>user</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@type"/>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>','</xsl:text>
        <xsl:choose>
          <xsl:when test="string-length(@func) = 0">
            <xsl:text>main</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@func"/>
          </xsl:otherwise>
        </xsl:choose>
        <!-- Add all other attributes -->
        <xsl:text>',array(</xsl:text>
        <xsl:for-each select="@*">
          <xsl:text>'</xsl:text><xsl:value-of select="name()"/><xsl:text>'</xsl:text>
          <xsl:text disable-output-escaping="yes">=&gt;'</xsl:text>
          <xsl:value-of select="."/><xsl:text>',</xsl:text>
        </xsl:for-each>
        <xsl:text>));</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>

  <!-- Identity transform for things we dont explicitly match-->
  <xsl:template match="node()">
    <xsl:param name="php"/>
    <xsl:if test="$php = 'on'">
      <xsl:call-template name="phpoff"/>
    </xsl:if>
    <!-- Make a copy of it and apply the templates -->
    <!-- If we could figure out a way to treat our expression in an xml compliant way we wouldn't have to do this trickery -->
    <xsl:text disable-output-escaping="yes">&lt;</xsl:text>
    <xsl:value-of select="name()"/><xsl:text> </xsl:text>
      <xsl:for-each select="@*">
        <xsl:value-of select="name()"/><xsl:text>="</xsl:text>
        <xsl:choose>
          <!-- If it is in #...# resolve it for sure -->
          <xsl:when test="string-length(.) &gt; 1 and substring(.,1,1) = '#' and substring(.,string-length(.),1) = '#'">
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="."/>
              <xsl:with-param name="php" select="'off'"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:otherwise>
            <!-- Be very careful not to produce anything else than the node here -->
            <xsl:value-of select="."/>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>" </xsl:text>
      </xsl:for-each>
      <xsl:text disable-output-escaping="yes">&gt;</xsl:text>
      <xsl:apply-templates>
        <xsl:with-param name="php" select="'off'"/>
      </xsl:apply-templates>
      <xsl:text disable-output-escaping="yes">&lt;/</xsl:text>
      <xsl:value-of select="name()"/>
      <xsl:text disable-output-escaping="yes">&gt;</xsl:text>
      
      <xsl:if test="$php = 'on'">
        <xsl:call-template name="phpon"/>
      </xsl:if>
  </xsl:template>

  <!-- Expression resolving in nodes-->
  <xsl:template name="resolvePHP">
    <xsl:param name="expr"/>
    <xsl:param name="php"/>
    <xsl:if test="$php = 'off'">
      <xsl:call-template name="phpon"/>
      <xsl:text>echo </xsl:text>
    </xsl:if>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::phpexpression',string($expr))"
        disable-output-escaping="yes"/>
    <xsl:if test="$php = 'off'">
      <xsl:text>;</xsl:text>
      <xsl:call-template name="phpoff"/>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>