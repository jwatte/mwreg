<?php

require_once 'init.php';
require_once 'dbconnect.php';
require_once 'userinfo.php';
require_once 'pages.php';

if (array_key_exists('code', $_POST) && array_key_exists('email', $_POST) &&
    array_key_exists('password', $_POST) && array_key_exists('password2', $_POST)) {
    $forgot_message = verify_password_reset(trim($_POST['email']), trim($_POST['code']));
    if (!$forgot_message) {
        $pw = trim($_POST['password']);
        $pw2 = trim($_POST['password2']);
        if ($pw !== $pw2) {
            $reset_message = 'The passwords must match.';
        } else if (!is_valid_password($pw)) {
            $reset_message = 'The password must be at least 6 characters.';
        } else {
            $pwhash = password_hash($pw, PASSWORD_DEFAULT);
            $email = trim($_POST['email']);
            db_query("UPDATE users SET passwordhash=:hash, verifykey='', verified=1 WHERE email=:email",
                array('hash'=>$pwhash, 'email'=>$email));
            $reset_message = 'The password was reset. You can now proceed to log in.';
        }
        require_once 'page/reset.php';
        require_once 'finish.php';
        exit();
    }
} else if (array_key_exists('code', $_GET) && array_key_exists('email', $_GET)) {
    $forgot_message = verify_password_reset(trim($_GET['email']), trim($_GET['code']));
    if (!$forgot_message) {
        require_once 'page/reset.php';
        require_once 'finish.php';
        exit();
    }
}
require_once 'page/forgot.php';

require_once 'finish.php';
