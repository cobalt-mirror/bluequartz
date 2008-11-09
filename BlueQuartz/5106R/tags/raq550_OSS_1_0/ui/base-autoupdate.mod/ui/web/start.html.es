<HTML>
<HEAD>
<STYLE TYPE="text/css">
<!--
BIG {font-family:sans-serif; font-size:14pt;}
STRONG {color:#990000;font-family:sans-serif; font-size:14pt;}
SMALL {background-color:#000099;color:#FFFFFF;font-family:sans-serif;font-size:10pt;font-weight:bold;text-decoration:none;}
-->
</STYLE>
</HEAD>
<BODY BACKGROUND="/libImage/blocksTile.gif" onResize="if(navigator.appName == 'Netscape') location.reload()">

<!-- CSS check --> 
<DIV ID="checkCss" STYLE="position:absolute"></DIV>
<SCRIPT LANGUAGE="javascript">
cssEnabled = false;
if(document.layers && document.checkCss != null)
  cssEnabled = true;
if(document.all && checkCss != null)
  cssEnabled = true;
</SCRIPT>

<!-- cookie check -->
<SCRIPT LANGUAGE="javascript">
cookieEnabled = false;
var cookie = "cookieSupport=true";
document.cookie = cookie;
if(document.cookie.indexOf(cookie) != -1)
  cookieEnabled = true;
</SCRIPT>

<!-- SSL check -->
<SCRIPT LANGUAGE="javascript">
sslEnabled = false;
/*
sslEnabled = true;
var name = navigator.appName;
var version = navigator.appVersion;
// no SSL for IE on Mac
if(name.indexOf("Microsoft") != -1 && version.indexOf("Mac") != -1)
  sslEnabled = false;
*/
</SCRIPT>

<CENTER>
<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" WIDTH="80%">
  <TR>
    <TD><IMG SRC="/libImage/splashGeneral.jpg" ALT=" " WIDTH="368" HEIGHT="337"></TD>
    <TD VALIGN="BOTTOM">
<SCRIPT LANGUAGE="javascript">
	if(!cssEnabled) {
	  <!-- locale-sensitive -->
	  document.write("<FONT SIZE=\"+1\" COLOR=\"#990000\" FACE=\"Arial, Helvetica, Sans-Serif\">Antes de iniciar la sesión, compruebe que el navegador que está utilizando admite hojas de estilo en cascada y después vuelva a cargar esta página.</FONT>");
	} else if(!cookieEnabled) {
	  <!-- locale-sensitive -->
	  document.write("<STRONG>Antes de iniciar la sesión, compruebe que el navegador que está utilizando admite cookies y después vuelva a cargar esta página.</STRONG>");
	} else {
	  <!-- locale-sensitive -->
	  document.write("<BIG>Bienvenido, le agradecemos que haya adquirido Cobalt Qube 3.  Dentro de pocos minutos su organización dispondrá de correo electrónico, web, compartición de archivos y otros servicios.</BIG>");

	  var action = sslEnabled ? "https://"+location.hostname+":81/loginHandler.php" : "http://"+location.hostname+":444/loginHandler.php";

	  document.write("<FORM ACTION=\""+action+"\" ENCTYPE=\"application/x-www-form-urlencoded\" METHOD=\"POST\" NAME=\"form\" onSubmit=\"this.timeStamp.value = (new Date()).getTime(); return true;\">");
	  document.write("<INPUT TYPE=\"HIDDEN\" NAME=\"timeStamp\">");
	  document.write("<INPUT TYPE=\"HIDDEN\" NAME=\"target\" VALUE=\"/nav/flow.php?root=base_autoupdateNetwork\">");
	  document.write("<INPUT TYPE=\"HIDDEN\" NAME=\"fallback\" VALUE=\"/login.php?target=/nav/flow.php?root=base_autoupdateNetwork\">");
	  document.write("<INPUT TYPE=\"HIDDEN\" NAME=\"newLoginName\" VALUE=\"admin\">");
	  document.write("<INPUT TYPE=\"HIDDEN\" NAME=\"newLoginPassword\" VALUE=\"admin\">");
	  <!-- locale-sensitive -->
	  document.write("<DIV ALIGN=\"right\"><TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD><A HREF=\"javascript: if(document.form.onsubmit()) document.form.submit();\"><IMG BORDER=\"0\" SRC=\"/libImage/buttonLeftDot.gif\"></A></TD><TD NOWRAP BGCOLOR=\"#000099\"><A HREF=\"javascript: if(document.form.onsubmit()) document.form.submit();\"><SMALL>Start</SMALL></A></TD><TD><A HREF=\"javascript: if(document.form.onsubmit()) document.form.submit(); \"><IMG BORDER=\"0\" SRC=\"/libImage/buttonRight.gif\"></A></TD></TR></TABLE>    </DIV>");
	  document.write("</FORM>");
	}
      </SCRIPT>
      <NOSCRIPT>
	<!-- locale-sensitive -->
	<STRONG>Antes de iniciar la sesión, compruebe que el navegador que está utilizando admite JavaScript y después vuelva a cargar esta página.</STRONG>
      </NOSCRIPT>
    </TD>
  </TR>
</TABLE>
</CENTER>

</BODY>
</HTML>
