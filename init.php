<?php

ob_start();

$_data = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_data = $_POST;
    if (isset($_data['password'])) $_data['password'] = '*****';
    if (isset($_data['password2'])) $_data['password2'] = '*****';
    $_data = print_r($_data, true);
}
error_log("$_SERVER[REQUEST_METHOD] $_SERVER[REQUEST_URI] $_SERVER[REMOTE_ADDR] $_data");

require_once 'config.php';

$_ver = explode('.', phpversion());
if ($_ver[0] < '5' || ($_ver[0] === '5' && $_ver[1] < '5')) {
    /* if I upgrade the PHP version, all passwords may suddenly become invalid... */
    function _password_work($pw, $iter, $salt) {
        for ($i = 0; $i != $iter; ++$i) {
            $pw = strtolower(hash('sha256', "$i+$salt+$pw"));
        }
        return $pw;
    }
define('PASSWORD_DEFAULT', 0);
    function password_hash($pw, $algo) {
        $iter = 10;
        $salt = base64_encode(openssl_random_pseudo_bytes(32));
        return "x:$iter:$salt:"._password_work($pw, $iter, $salt);
    }
    function password_verify($pw, $hash) {
        list($alg, $iter, $salt, $expected) = explode(':', $hash);
        if (!$expected || !$pw) {
            return false;
        }
        return _password_work($pw, $iter, $salt) === $expected;
    }
}

function logerror() {
    $bta = array_slice(debug_backtrace(0), 0, 5);
    $bt = array();
    foreach ($bta as $i) {
        $args = $i['args'];
        if (array_key_exists("password", $args)) {
            $args['password'] = '******';
        }
        if (array_key_exists("password2", $args)) {
            $args['password2'] = '******';
        }
        $bt[] = "$i[file]:$i[line]:$i[function](".
            implode(',', 
                array_map(function ($x) { return str_replace(array("\r", "\n"), ' ', var_export($x, true)); },
                $args)
            ).")";
    }
    $args = func_get_args();
    error_log(print_r(array('info'=>$args, 'backtrace'=>$bt), true));
}
