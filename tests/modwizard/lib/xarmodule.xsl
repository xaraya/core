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

<xsl:include href="include/verify_hooks.xsl" />
<xsl:include href="include/verify_database.xsl" />

<!-- INCLUDE THE REQUIRED STYLESHEETS -->
<!-- Xaraya Module File and Function Templates -->      <xsl:include href="include/xaraya_standard.xsl" />
<!-- Template for xartables.php -->                     <xsl:include href="xartables.xsl" />
<!-- Template for xarinit.php -->                       <xsl:include href="xarinit.xsl" />
<!-- Template for xarobject.xml -->                     <xsl:include href="xarobject.xsl" />
<!-- Template for xarversion.php -->                    <xsl:include href="xarversion.xsl" />
<!-- Template for xaradminapi.php -->                   <xsl:include href="xaradminapi.xsl" />
<!-- Template for xaritemtypeapi.php -->                <xsl:include href="xaritemtypeapi.xsl" />
<!-- Template for xaradmin.php -->                      <xsl:include href="xaradmin.xsl" />
<!-- Template for xaruserapi.php -->                    <xsl:include href="xaruserapi.xsl" />
<!-- Template for xaruser.php -->                       <xsl:include href="xaruser.xsl" />
<!-- Template for xartemplates/admin-main.xd -->        <xsl:include href="xartemplates/admin-main.xsl" />
<!-- Template for xartemplates/admin-view.xd -->        <xsl:include href="xartemplates/admin-view.xsl" />
<!-- Template for xartemplates/admin-view-table.xd -->  <xsl:include href="xartemplates/admin-view-table.xsl" />
<!-- Template for xartemplates/admin-modify-table.xd --><xsl:include href="xartemplates/admin-modify-table.xsl" />
<!-- Template for xartemplates/admin-config.xd -->      <xsl:include href="xartemplates/admin-config.xsl" />
<!-- Template for xartemplates/admin-config-table.xd --><xsl:include href="xartemplates/admin-config-table.xsl" />
<!-- Template for xartemplates/admin-delete-table.xd --><xsl:include href="xartemplates/admin-delete-table.xsl" />
<!-- Template for xartemplates/admin-new-table.xd -->   <xsl:include href="xartemplates/admin-new-table.xsl" />
<!-- Template for xartemplates/user-main.xd -->         <xsl:include href="xartemplates/user-main.xsl" />
<!-- Template for xartemplates/user-display-table.xd --><xsl:include href="xartemplates/user-display-table.xsl" />
<!-- Template for xartemplates/user-view-table.xd -->   <xsl:include href="xartemplates/user-view-table.xsl" />
<!-- Template for xartemplates/includes/admin-header.xd -->   <xsl:include href="xartemplates/includes/admin-header.xsl" />
<!-- Template for xartemplates/includes/user-header.xd -->    <xsl:include href="xartemplates/includes/user-header.xsl" />
<!-- Template for xarblocks/block.php -->               <xsl:include href="xarblocks/block.xsl" />
<!-- Template for xartemplates/blocks/block.php -->     <xsl:include href="xartemplates/blocks/block.xsl" />


<xsl:template match="/">

    <!-- VERIFY -->
    <xsl:apply-templates mode="verify" select="." />

    <!-- CREATE -->
    <xsl:apply-templates select="xaraya_module" />

</xsl:template>

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="xaraya_module" xml:space="preserve">

    <xsl:message>
    Xaraya module generator will generate the following module for you:

    Module:         <xsl:value-of select="about/name" />
    Author:         <xsl:value-of select="about/author/name" />

    Starting with the creation
    </xsl:message>
    <!-- CALL THE TEMPLATES -->
    <!-- xartables.php -->                      <xsl:apply-templates mode="xartables"             select="/" />
    <!-- xarinit.php -->                        <xsl:apply-templates mode="xarinit"               select="/" />
    <!-- xarobject.xml -->                      <xsl:apply-templates mode="xarobject"             select="/" />
    <!-- xarversion.php -->                     <xsl:apply-templates mode="xarversion"            select="/" />

    <!-- xaradminapi.php -->                    <xsl:apply-templates mode="xaradminapi"           select="/" />
    <!-- xaritemtypeapi.php -->                 <xsl:apply-templates mode="xaritemtypeapi"           select="/" />
    <!-- xaradmin.php -->                       <xsl:apply-templates mode="xaradmin"              select="/" />
    <!-- xartemplates/admin-main.xd -->         <xsl:apply-templates mode="xd_admin-main"         select="/" />
    <!-- xartemplates/admin-view.xd -->         <xsl:apply-templates mode="xd_admin-view"         select="/" />
    <!-- xartemplates/admin-view-table.xd -->   <xsl:apply-templates mode="xd_admin-view-table"   select="/" />
    <!-- xartemplates/admin-modify-table.xd --> <xsl:apply-templates mode="xd_admin-modify-table" select="/" />
    <!-- xartemplates/admin-config.xd -->       <xsl:apply-templates mode="xd_admin-config"       select="/" />
    <!-- xartemplates/admin-config-table.xd --> <xsl:apply-templates mode="xd_admin-config-table" select="/" />
    <!-- xartemplates/admin-delete-table.xd --> <xsl:apply-templates mode="xd_admin-delete-table" select="/" />
    <!-- xartemplates/admin-new-table.xd -->    <xsl:apply-templates mode="xd_admin-new-table"    select="/" />

    <!-- xaruserapi.php -->                     <xsl:apply-templates mode="xaruserapi"            select="/" />
    <!-- xaruser.php -->                        <xsl:apply-templates mode="xaruser"               select="/" />
    <!-- xartemplates/user-main.xd -->          <xsl:apply-templates mode="xd_user-main"          select="/" />
    <!-- xartemplates/user-display-table.xd --> <xsl:apply-templates mode="xd_user-display-table" select="/" />
    <!-- xartemplates/user-view-table.xd -->    <xsl:apply-templates mode="xd_user-view-table"    select="/" />
    <!-- xartemplates/includes/admin-header.xd -->   <xsl:apply-templates mode="xd_includes_admin-header"    select="/" />
    <!-- xartemplates/includes/user-header.xd -->    <xsl:apply-templates mode="xd_includes_user-header"    select="/" />

    <!-- xarblocks/block.php -->                <xsl:apply-templates mode="xarblocks_block"       select="/" />
    <!-- xartemplates/blocks/block.php -->      <xsl:apply-templates mode="xd_blocks_block"       select="/" />

</xsl:template>

</xsl:stylesheet>
