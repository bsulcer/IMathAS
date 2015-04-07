<?php
require_once("includes/utils.php");
header('P3P: CP="ALL CUR ADM OUR"');
utils_start_session();
$redir = $_GET['redirect_url'];
?>
<!DOCTYPE html>
<html>
<head>
<head>
<script type="text/javascript">
function redirect() {
	window.location = "<?php echo $redir ?>";
}
</script>
</head>
<body onload="redirect()">
Redirecting you back to your LMS...<br/>
If you aren't redirected in 5 seconds, 
<a href="<?php echo $redir ?>">click here</a>.
</body>
</html>
