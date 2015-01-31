<?php

function errors_fatal($x) {
    logerror("FATAL: ".print_r($x, true));
    ob_end_clean();
?><!DOCTYPE html>
<head><title>Mech Warfare - an error!</title>
<link href='styles.css'/>
</head>
<body><div class='content'>
<div class='text'>
Oops! An error occurred. Please go back to the start and try again later. We sincerely apologize for the inconvenience.
</div>
<div class='error'><?php echo htmlquote($x); ?></div>
<div class='text'>
If the error persists, please contact the site operator.
</div>
</div></body><?php
exit;
}
