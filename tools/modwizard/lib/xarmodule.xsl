<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">
<!--

    This is the global stylesheet. It includes and calls all the other
    stylesheets.

-->

<!-- We suppress the xml declaration. This context is only user for status
     messages. -->
<xsl:output type="text" omit-xml-declaration="yes" />



<!--

    PARAMETERS
    ==========
-->

<!-- output      ->  directory where the output files should be created -->
<xsl:param name="output"    required="yes" />
<!-- gCommentsLevel

     0 => NO COMMENT
     10 => NORMAL PRODUCTION COMMENTS
     20 => ADDITIONAL INFORMATION FOR BEGINNERS
     30 => ALL INFORMATION AVAILABLE !!

-->
<xsl:param name="gCommentsLevel"   required="no">3</xsl:param>

<!-- INCLUDES -->
<xsl:include href="include/verify_hooks.xsl" />
<xsl:include href="include/verify_database.xsl" />
<xsl:include href="include/xaraya_standard.xsl" />

<!-- TOP LEVEL FILES -->
<xsl:include href="xartables.xsl" />
<xsl:include href="xarinit.xsl" />
<xsl:include href="xarobject.xsl" />
<xsl:include href="xarversion.xsl" />

<!-- SUBDIRECTORIES -->
<xsl:include href="xarblocks.xsl" />
<xsl:include href="xardocs.xsl" />
<xsl:include href="xaritemtypeapi.xsl" />
<xsl:include href="xarprivateapi.xsl" />
<xsl:include href="xarhookapi.xsl" />
<xsl:include href="xarhook.xsl" />
<xsl:include href="xaruserapi.xsl" />
<xsl:include href="xaruser.xsl" />
<xsl:include href="xaradmin.xsl" />
<xsl:include href="xaradminapi.xsl" />
<xsl:include href="xarstyles.xsl" />

<!-- XARTEMPLATE INCLUDES -->
<xsl:include href="xartemplates/includes/header.xsl" />
<xsl:include href="xartemplates/includes/navbar.xsl" />


<xsl:template match="/">
    <xsl:apply-templates select="xaraya_module" />
</xsl:template>

<xsl:template match="xaraya_module">

    <xsl:message>
    Xaraya module generator will generate the following module for you:

    Module:         <xsl:value-of select="about/name" />
    Author:         <xsl:value-of select="about/author/name" />

    </xsl:message>

    <xsl:message>
### Verifying the xml file</xsl:message>

    <!-- VERIFY -->
    <xsl:apply-templates mode="verify" select="." />

    <xsl:message>
### Begin of code generation</xsl:message>

    <!-- TOP LEVEL FILES -->
    <xsl:apply-templates mode="xartables"             select="." />
    <xsl:apply-templates mode="xarinit"               select="." />
    <xsl:apply-templates mode="xarobject"             select="." />
    <xsl:apply-templates mode="xarversion"            select="." />

    <!-- SUBDIRECTORIES -->
    <xsl:apply-templates mode="xaritemtypeapi"        select="." />
    <xsl:apply-templates mode="xarprivateapi"         select="." />
    <xsl:apply-templates mode="xarhookapi"            select="." />
    <xsl:apply-templates mode="xarhook"               select="." />
    <xsl:apply-templates mode="xaradminapi"           select="." />
    <xsl:apply-templates mode="xaradmin"              select="." />
    <xsl:apply-templates mode="xaruserapi"            select="." />
    <xsl:apply-templates mode="xaruser"               select="." />
    <xsl:apply-templates mode="xarblocks"             select="." />
    <xsl:apply-templates mode="xardocs"               select="." />
    <xsl:apply-templates mode="xarstyles"             select="." />

    <!-- XARTEMPLATE INCLUDES -->
    <xsl:apply-templates mode="xd_includes_header"    select="." />
    <xsl:apply-templates mode="xd_includes_navbar"    select="." />

    <xsl:message>
### End of code generation

</xsl:message>

</xsl:template>

</xsl:stylesheet>
