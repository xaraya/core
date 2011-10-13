<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

    <xsl:template name="xar-template" match="xar:template">
    
      <xsl:choose>
        <xsl:when test="not(node()) and @file">

          <xsl:variable name="subdata">
            <xsl:choose>
              <xsl:when test="not(@subdata)">
                <xsl:text>$_bl_data</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="@subdata"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          
          <xsl:choose>
            <!-- deal with the system type immediately -->
            <xsl:when test="@type='system'">
              <!-- The name is to be interpreted relative to the file we're parsing now -->
              <xsl:processing-instruction name="php">
                <xsl:text>echo xarTpl::file("</xsl:text>
                <xsl:value-of select="$bl_dirname"/><xsl:text>/</xsl:text><xsl:value-of select="@file"/>
                <xsl:text>",</xsl:text>
                <xsl:call-template name="resolvePHP">
                  <xsl:with-param name="expr" select="$subdata"/>
                </xsl:call-template>
                <xsl:text>);</xsl:text>
             </xsl:processing-instruction>
            </xsl:when>
            <xsl:otherwise>

              <!-- determine scope -->
              <xsl:variable name="scope">
                <xsl:choose>
                  <xsl:when test="@type != ''">
                    <!-- scope indicated by type attribute --> 
                    <xsl:value-of select="@type"/>
                  </xsl:when>
                  <xsl:otherwise>
                    <!-- determine scope from other attributes -->
                    <xsl:choose>
                      <xsl:when test="@theme != ''">
                        <xsl:text>theme</xsl:text>
                      </xsl:when>
                      <xsl:when test="@block != ''">
                        <xsl:text>block</xsl:text>
                      </xsl:when>
                      <xsl:when test="@property != ''">
                        <xsl:text>property</xsl:text>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>module</xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>                
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:variable>
          
              <!-- determine package -->
              <xsl:variable name="package">
                <xsl:choose>
                  <xsl:when test="$scope='theme'">
                    <xsl:choose>
                      <xsl:when test="@theme != ''">
                        <xsl:value-of select="@theme"/>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>xarTpl::getThemeName()</xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>
                  </xsl:when>
                  <xsl:when test="$scope='block'">
                    <xsl:choose>
                      <xsl:when test="@block != ''">
                        <xsl:value-of select="@block"/>
                      </xsl:when>
                      <xsl:when test="string-length(substring-before(substring-after($bl_dirname,'blocks/'),'/')) &gt; 0">
                        <xsl:value-of select="substring-before(substring-after($bl_dirname,'blocks/'),'/')"/>
                      </xsl:when>
                    </xsl:choose>
                  </xsl:when>
                  <xsl:when test="$scope='property'">
                    <xsl:choose>
                      <xsl:when test="@property != ''">
                        <xsl:value-of select="@property"/>
                      </xsl:when>
                      <xsl:when test="string-length(substring-before(substring-after($bl_dirname,'properties/'),'/')) &gt; 0">
                        <xsl:value-of select="substring-before(substring-after($bl_dirname,'properties/'),'/')"/>
                      </xsl:when>
                    </xsl:choose>
                  </xsl:when>
                  <xsl:otherwise>
                     <xsl:choose>
                       <xsl:when test="@module != ''">
                         <xsl:value-of select="@module"/>
                       </xsl:when>
                      <xsl:when test="string-length(substring-before(substring-after($bl_dirname,'modules/'),'/')) &gt; 0">
                        <xsl:value-of select="substring-before(substring-after($bl_dirname,'modules/'),'/')"/>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>xarMod::getName()</xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>
                  </xsl:otherwise>                           
                </xsl:choose>
              </xsl:variable>


          
              <!-- Optional relative path from template folder (default includes) -->
              <xsl:variable name="tplpath">
                <xsl:choose>
                  <xsl:when test="@includepath != ''">
                    <xsl:value-of select="@includepath"/>
                  </xsl:when>
                  <!--
                  <xsl:when test="$scope='system'">
                    <xsl:choose>
                      <xsl:when test="string-length(substring-after($bl_dirname, 'xartemplates/')) &gt; 0">
                        <xsl:value-of select="substring-after($bl_dirname,'xartemplates/')"/>
                      </xsl:when>
                      <xsl:when test="string-length(substring-after($bl_dirname, substring-before(substring-after($bl_dirname,'modules/'),'/'))) &gt; 0">
                         <xsl:value-of select="substring-after($bl_dirname, substring-before(substring-after($bl_dirname,'modules/'),'/'))"/>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>includes</xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>
                  </xsl:when>
                  -->
                  <xsl:otherwise>
                    <xsl:text>includes</xsl:text>
                  </xsl:otherwise>                            
                </xsl:choose>
              </xsl:variable>
          
              <xsl:processing-instruction name="php">
                <xsl:text>echo xarTpl::includeTemplate("</xsl:text>
                <xsl:call-template name="resolvePHP">
                   <xsl:with-param name="expr" select="$scope"/>
                </xsl:call-template>
                <xsl:text>","</xsl:text>        
                <xsl:call-template name="resolvePHP">
                   <xsl:with-param name="expr" select="$package"/>
                </xsl:call-template>           
                <xsl:text>","</xsl:text> 
                 <xsl:call-template name="resolvePHP">
                   <xsl:with-param name="expr" select="@file"/>
                </xsl:call-template>
                <xsl:text>",</xsl:text>
                <xsl:call-template name="resolvePHP">
                  <xsl:with-param name="expr" select="$subdata"/>
                </xsl:call-template>
                <xsl:text>,"</xsl:text>
                <xsl:call-template name="resolvePHP">
                  <xsl:with-param name="expr" select="$tplpath"/>
                </xsl:call-template>
                <xsl:text>");</xsl:text>                          
              </xsl:processing-instruction>          
            
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise>
          <!--
            It's the root tag of a template file, or placed in block form inline
            no need to do anything yet, but process the children in it
          -->
          <xsl:apply-templates/>
        </xsl:otherwise>

      </xsl:choose>
    </xsl:template>

<xsl:template name="xar-template-old" match="xar:template-old">
  <xsl:variable name="subdata">
    <xsl:choose>
      <xsl:when test="not(@subdata)">
        <xsl:text>$_bl_data</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@subdata"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <xsl:choose>
    <!-- If the template tag does not contain anything, treat it as in 1.x -->
    <!--
      Redundant space will be compressed, so watch out here for nodes which
      just contain space-type content. There's no way to test that anymore once
      we get here.
    -->
    <xsl:when test="not(node()) and @file">
      <xsl:processing-instruction name="php">
        <xsl:text>echo </xsl:text>
        <xsl:choose>
          <xsl:when test="@type='theme'">
            <xsl:text>xarTpl::includeThemeTemplate("</xsl:text>
            <xsl:value-of select="@file"/>
            <xsl:text>",</xsl:text>
            <xsl:value-of select="$subdata"/>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:when test="@type='system'">
            <!-- The name is to be interpreted relative to the file we're parsing now -->
            <xsl:text>xarTpl::file("</xsl:text>
            <xsl:value-of select="$bl_dirname"/><xsl:text>/</xsl:text><xsl:value-of select="@file"/>
            <xsl:text>",</xsl:text>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="$subdata"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>xarTpl::includeModuleTemplate(</xsl:text>
            <xsl:choose>
              <xsl:when test="@module != ''">
                <xsl:text>"</xsl:text>
                <xsl:value-of select="@module"/>
                <xsl:text>"</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:choose>
                  <xsl:when test="string-length(substring-before(substring-after($bl_dirname,'modules/'),'/')) &gt; 0">
                    <xsl:text>'</xsl:text>
                    <xsl:value-of select="substring-before(substring-after($bl_dirname,'modules/'),'/')"/>
                    <xsl:text>'</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:text>xarMod::getName()</xsl:text>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>,"</xsl:text>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="@file"/>
            </xsl:call-template>
            <xsl:text>",</xsl:text>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="$subdata"/>
            </xsl:call-template>
            <xsl:text>,</xsl:text>
            <xsl:choose>
              <xsl:when test="@property != ''">
                <xsl:text>"</xsl:text>
                <xsl:value-of select="@property"/>
                <xsl:text>"</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>"</xsl:text>
                <xsl:if test="string-length(substring-before(substring-after($bl_dirname,'properties/'),'/')) &gt; 0">
                  <xsl:value-of select="substring-before(substring-after($bl_dirname,'properties/'),'/')"/>
                </xsl:if>
                <xsl:text>"</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:processing-instruction>
    </xsl:when>
    <xsl:otherwise>
      <!--
        It's the root tag of a template file, or placed in block form inline
        no need to do anything yet, but process the children in it
      -->
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>
</xsl:stylesheet>
