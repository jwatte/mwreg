<?php
require_once 'header.php';
page_header('Mech Warfare Registration Sign In');
?>
<div class='content'>
<?php
if ($signin_error) {
    echo "<div class='error'>".htmlquote($signin_error)."</div>";
}
?>
<form method='post'>
<div class='formfield'><span class='label'>Name or e-mail:</span><span class='field'><input type='text' id='name' name='name'/></span></div>
<div class='formfield'><span class='label'>Password:</span><span class='field'><input type='password' id='password' name='password'/></span></div>
<div class='formfield'><span class='label'><a href='<?php echo $ROOTPATH; ?>/forgot.php'>Forgot Password?</a></span><span class='field'><button name='submit' id='submit'>Sign in</button></span></div>
</div>
</form>
</div>
<div class='footer'>
</div>
</body>
