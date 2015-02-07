<?php
require_once 'header.php';
page_header('Mech Warfare Participant Registration');
?>
<div class='content'>
<?php
if ($signup_error) {
    echo "<div class='error'>".htmlquote($signup_error)."</div>";
}
?>
<div class='text'>Register yourself here. You can then register teams and mechs after logging in.</div>
<form method='post'>
<div class='formfield'><span class='label'>Name:</span><span class='field'><input type='text' id='name' name='name' value='<?php echo htmlquote(@$_POST['name']); ?>'/></div>
<div class='formfield'><span class='label'>E-mail:</span><span class='field'><input type='text' id='email' name='email' value='<?php echo htmlquote(@$_POST['email']); ?>'/></div>
<div class='formfield'><span class='label'>E-mail Again:</span><span class='field'><input type='text' id='email2' name='email2' value='<?php echo htmlquote(@$_POST['email2']); ?>'/></div>
<div class='formfield'><span class='label'>Password:</span><span class='field'><input type='password' id='password' name='password' value='<?php echo htmlquote(@$_POST['password']); ?>'/></div>
<div class='formfield'><span class='label'>Password Again:</span><span class='field'><input type='password' id='password2' name='password2' value='<?php echo htmlquote(@$_POST['password2']); ?>'/></div>
<div class='formfield'><span class='label'>Agreement:</span><span class='field'><label><input type='checkbox' id='tosok' name='tosok' value='1'/>I accept and agree to abide by the <a href='<?php echo $ROOTPATH; ?>/tos.php' target='_new'>Terms of Service</a>.</label></div>
<div class='formfield'><span class='label'>&nbsp;</span><span class='field'><button name='submit' id='submit'>Sign up</button></div>
</div>
</form>
<div class='text'>
Thanks for your interest in Mech Warfare!
<ul><li>The name has to be at least three (3) and at most thirty-two (32) characters long, may not begin or end with a space, and may not contain the at-sign '@' character.</li>
<li>The name must not already be registered in our system.</li>
<li>You will be identified in the forums with the name you give.</li>
<li>Your email will not be visible to other memebers of the site.</li>
<li>Your name <b>can</b> be found using the site &quot;find by email&quot; feature.</li>
<li>The password must be at least six (6) and at most seventy-two (72) characters long. Please choose wisely.</li>
<li>After registering, we will send you an email. You cannot log into your account until you read the email and click the link provided in it.</li>
<li>Use of the site is subject to the <a href='<?php echo $ROOTPATH; ?>/tos.php' target='_new'>Terms of Service</a>. Signing up for the site means you give consent and agree to abide by these terms.</li>
</ul>
</div>
</div>
<div class='footer'>
</div>
</body>
