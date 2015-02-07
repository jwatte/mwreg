<?php
require_once 'header.php';
page_header('Mech Warfare Verification OK');
?><div class='content'>
<?php
if ($verify_error) {
    ?><div class='error'><?php
    echo htmlquote($verify_error);
    ?></div><?php
} else {
?>
<div class='text'>
Thank you for verifying your email address. You can now sign in using your name or email address, and password.
</div>
<div class='text'>
<a href='<?php echo $ROOTPATH; ?>/signin.php'>Sign in</a>
</div>
<?php
}
?>
</div>
<div class='footer'>
</div>
</body>
