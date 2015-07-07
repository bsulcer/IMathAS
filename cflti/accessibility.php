<?php
$pref = 0;
$flexwidth = true;
$nologo = true;
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
require("header.php");
echo "<h4>Connecting to $installname</h4>";
echo "<form method=\"post\" action=\"{$_SERVER['PHP_SELF']}?launch=true\" ";
if ($sessiondata['ltiitemtype']==0 && $sessiondata['ltitlwrds'] != '') {
	echo "onsubmit='return confirm(\"This assessment has a time limit of {$sessiondata['ltitlwrds']}.  Click OK to start or continue working on the assessment.\")' >";
	echo "<p style=\"color:red;\">This assessment has a time limit of {$sessiondata['ltitlwrds']}.</p>";
} else {
	echo ">";
}
?>
<div id="settings"><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  
Please enable JavaScript and reload this page</noscript></div>
<input type="hidden" id="tzoffset" name="tzoffset" value="" />
<input type="hidden" id="tzname" name="tzname" value=""> 
<script type="text/javascript"> 
	 function updateloginarea() {
		setnode = document.getElementById("settings"); 
		var html = ""; 
		html += 'Accessibility: ';
		html += "<a href='#' onClick=\"window.open('<?php echo $imasroot;?>/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help<\/a>";
		html += '<div style="margin-top: 0px;margin-right:0px;text-align:right;padding:0px"><select name="access"><option value="0">Use defaults</option>';
		html += '<option value="3">Force image-based display</option>';
		html += '<option value="1">Use text-based display</option></select></div>';
	
		if (!MathJaxCompatible) {
			html += '<input type="hidden" name="mathdisp" value="0" />';
		} else {
			html += '<input type="hidden" name="mathdisp" value="1" />';
		}
		if (ASnoSVG) {
			html += '<input type="hidden" name="graphdisp" value="2" />';
		} else {
			html += '<input type="hidden" name="graphdisp" value="1" />';
		}
		html += '<div class="textright"><input type="submit" value="Continue" /><\/div>';
		setnode.innerHTML = html; 
		var thedate = new Date();  
		document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
		var tz = jstz.determine(); 
		document.getElementById("tzname").value = tz.name();
	}
	var existingonload = window.onload;
	if (existingonload) {
		window.onload = function() {existingonload(); updateloginarea();}
	} else {
		window.onload = updateloginarea;
	}
</script>
</form>
<?php
require("footer.php");
