<?php
function page_header($title) {
global $ROOTPATH;
?><!DOCTYPE html>
<html>
<head>
<title><?php echo $title; ?></title>
<link rel='stylesheet' href='<?php echo $ROOTPATH; ?>/styles.css'/>
</head>
<body>
<div class='header'>
<?php
    global $user;
    if ($user) {
        $gravatar = calc_gravatar($user['email']);
        echo "<div class='userinfo'><span class='username'>".htmlquote($user[name])."</span><span class='action logout'><a href='$ROOTPATH/logout.php'>Sign out</a></span></div>";
    } else {
        $gravatar = '';
        echo "<div class='loginlink'><span class='action login'><a href='$ROOTPATH/signin.php'>Sign in</a></span><span class='action register'><a href='$ROOTPATH/signup.php'>Sign up</a></span></div>";
    }
    echo "<a href='$ROOTPATH/'>$gravatar$title</a>";
    echo "</div>";
}
?>
