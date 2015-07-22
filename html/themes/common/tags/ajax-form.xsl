<?xml version="1.0" encoding="utf-8"?>
<!--
This tag can be used to submit forms data for saving via an AJAX call

Example:

<xar:ajax-form form="myform"/>

Behavior:
- If the submit is successful, displays a modal window logging the success for 2 seconds.
- If the submit is successful, but validation of the data fails, displays a modal window with the input errors and an "OK" button for the user to acknowledge.
- If the submit is unsuccessful, displays a "unsuccessful" message. 
- If AJAX is disabled (see themes module admin interface), then behavior reverts back to a normal submit with server side validation and saving.

Requires:
- The tag must reference a form, e.g. <xar-ajax-form form="myform"/> where myfrom is the ID of a form whose data is to be saved.
- The PHP code validates the inputs received form he AJAX call. 
  * If the inputs are correct the code exits.
  * If they are not correct the code sends an array with key=problem_title and value=problem_description for each problem encountered to the template for display. 

Uses
- This tag calls the latest jquery and jquery-ui files
- The error message(s) display via a jquery modal window that uses the template themes/common/includes/user-messages.xt, which can be overridden in themes.

Usage
- Add this tag anywhere on the template that submits to PHP code with the proper AJAX responses.
- See the AJAX methods in lib/xaraya/mapper/request.php for responses to be included in the PHP code.
- The tag has no open form.
-->
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template name="xar-ajax-form" match="xar:ajax-form">
<link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
  <!-- Proceed if we have a form -->
    <xsl:choose>
      <xsl:when test="@form">

  <!-- Load the latest jquery version -->
  <xsl:processing-instruction name="php">
    <xsl:text>xarMod::apiFunc('themes','user','registerjs',array('type'=&gt;'lib','lib'=&gt;'jquery','position'=&gt;'head'));</xsl:text>
    <xsl:text>xarMod::apiFunc('themes','user','registerjs',array('type'=&gt;'lib','lib'=&gt;'jquery-ui','position'=&gt;'head'));</xsl:text>
  </xsl:processing-instruction>

  <!-- Start javascript -->
  <script type="text/javascript">

    <xsl:text>

      $(document).ready(function(){
      
      <!-- Whether this call proceeds depends on the global variable for AJAX -->
      if (</xsl:text>
      <xsl:processing-instruction name="php">
        <xsl:text>
          echo xarConfigVars::get(null, 'Site.Core.AllowAJAX');
        </xsl:text>
      </xsl:processing-instruction>
      <xsl:text> != 1) return true;
      
      <!-- Variable to hold request -->
      var request;

      <!-- bind to the submit event of our form -->
      $("#</xsl:text><xsl:value-of select="@form" />
        <xsl:text>").submit(function(event){

      <!-- Abort any pending request -->
      if (request) {request.abort();}

      <!-- Setup some local variables -->
      var $form = $(this);

      <!-- Select and cache all the fields -->
      var $inputs = $form.find("input, select, button, textarea");

      <!-- Serialize the data in the form -->
      var dataValues = $form.serialize();

      <!-- Prevent default posting of form -->
      event.preventDefault();
    </xsl:text>

    <!-- Call the ajax method -->
    <xsl:call-template name="ajax_code">
      <xsl:with-param name="type" ><xsl:value-of select="@type" /></xsl:with-param>
      <xsl:with-param name="url" ><xsl:value-of select="@url" /></xsl:with-param>
      <xsl:with-param name="success" ><xsl:value-of select="'success'" /></xsl:with-param>
      <xsl:with-param name="failure" ><xsl:value-of select="'failure'" /></xsl:with-param>
    </xsl:call-template>

    <xsl:text>
      });
      });
    </xsl:text>
  </script>
    </xsl:when>
  </xsl:choose>
  <div id="success"><xsl:comment>Empty tag workaround for div tag</xsl:comment></div>
  <div id="failure"><xsl:comment>Empty tag workaround for div tag</xsl:comment></div>
</xsl:template>

<xsl:template name="ajax_code">
  <xsl:param name="type" />
  <xsl:param name="url" />
  <xsl:param name="success" />
  <xsl:param name="failure" />

    <xsl:text>
      <!-- Disable input fields -->
      $inputs.prop("disabled", true);

      request = $.ajax({
    </xsl:text>

    <!-- check for the type GET, POST -->
    <xsl:choose>
      <xsl:when test="normalize-space($type)">
        <xsl:text>type: "</xsl:text>
        <xsl:value-of select="$type" />
        <xsl:text>",</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>
          cache: false,
          type: "post",
        </xsl:text>
      </xsl:otherwise>
    </xsl:choose>

    <!-- check for a url -->
    <xsl:choose>
      <xsl:when test="normalize-space($url)">
        <xsl:text>url: "</xsl:text>
        <xsl:value-of select="$url" />
        <xsl:text>",</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>url: "</xsl:text>
        <xsl:processing-instruction name="php">
          <xsl:text>
            echo xarServer::getCurrentURL(array(), false);
          </xsl:text>
        </xsl:processing-instruction>
        <xsl:text>",</xsl:text>
      </xsl:otherwise>
    </xsl:choose>

    <!-- pass the data -->
    <xsl:text>data: dataValues});</xsl:text>

    <!-- log success -->
    <xsl:text>
      request.done(function (response, textStatus, jqXHR){
      var submitworked = false;
        if(!response){
            response = "Submitted successfuly";
            submitworked = true;
        };
        $("#</xsl:text><xsl:value-of select="$success" />
        <xsl:text>").html(response);
        console.log("Submitted successfully");
        if (submitworked) {
          <!-- Show dialog -->
          $( "#success" ).dialog({
            modal: true,
            width: 200,
            height: "auto",
            minHeight:0,
            buttons: {}
          });
          $(".ui-dialog-buttonset").hide();
          $(".ui-dialog-titlebar").hide();

          setTimeout(function() {
            $("#</xsl:text><xsl:value-of select="$success" />
        <xsl:text>").dialog( "close" );
          }, 2000);
        } else {
          <!-- Show dialog -->
          $( "#</xsl:text><xsl:value-of select="$success" />
        <xsl:text>" ).dialog({
            modal: true,
            width: 400,
            height:"auto",
            minHeight: 0,
            buttons: {
              Ok: function() {
                $(this).dialog( "close" );
              }
            }
          });
          $(".ui-dialog-titlebar").hide();
        }
      });

    </xsl:text>
    
    <!-- log failure -->
    <xsl:text>
      request.fail(function (jqXHR, textStatus, errorThrown){
        $("#</xsl:text><xsl:value-of select="$failure" />
        <xsl:text>").html('Submit error encountered');
        console.error(
          "The following submit error occured: "+textStatus, errorThrown
        );

      });

    </xsl:text>

    <!-- Callback handler that will be called in any case -->
    <xsl:text>
      request.always(function () {
        <!-- Enable input fields -->
        $inputs.prop("disabled", false);
      });
    </xsl:text>
</xsl:template>

</xsl:stylesheet>
