<?php

require_once 'dbconnect.php';
require_once 'mailinfo.php';

$user = null;
$session = null;
$orig_session = null;

$_sessionid = @$_COOKIE['session'];
if ($_sessionid) {
    $_data = db_query('SELECT expires, data FROM sessions WHERE sessionid=:id AND expires > NOW()', array('id'=>$_sessionid));
    if ($_data) {
        $session = json_decode($_data[0]['data'], true);
        $orig_session = session;
        $_user = db_query('SELECT * FROM users WHERE userid=:id', array('id'=>$session['userid']));
        if ($_user) {
            $user = @$_user[0];
            /* TODO: extend the session */
            /*
            if ($_data[0]['expires'] < now + 9 days) {
                db_query('UPDATE sessions SET expires = NOW + INTERVAL 10 DAY WHERE sessionid=:id', array('id'=>$_sessionid));
                setcookie('session', $_sessionid, time() + 86400*10, ...
            }
             */
        }
        if (!$user) {
            /* there is no user as identified in the session */
            db_query('DELETE FROM sessions WHERE sessionid=:id', array('id'=>$_sessionid));
            $session = null;
            $orig_session = null;
        }
    }
}

function delete_session() {
    global $user;
    global $session;
    global $ROOTPATH;
    global $COOKIEHOST;
    $user = null;
    $session = null;
    setcookie('session', null, 0, $ROOTPATH.'/', $COOKIEHOST, false, true);
    $sessionid = @$_COOKIE['session'];
    if ($sessionid) {
        db_query("DELETE FROM sessions WHERE sessionid=:sessionid", array('sessionid'=>$sessionid));
    }
}

function user_signin($name, $password) {
    global $session;
    global $user;
    global $ROOTPATH;
    global $COOKIEHOST;
    $u = db_query("SELECT * FROM users WHERE ((name=:name) OR (email=:email))",
        array('name'=>$name, 'email'=>$name));
    if (!$u) {
        return false;
    }
    if ($u[0]['disabled']) {
        errors_fatal("The account $name has been disabled.");
    }
    if (!$u[0]['verified']) {
        errors_fatal("The account $name has not yet verified its email address. " .
            "Please check your inbox and click the link provided. " .
            "If you cannot find the e-mail, please click the 'Forgot password?' link on the sign-in page.");
    }
    if (!password_verify($password, $u[0]['passwordhash'])) {
        return false;
    }
    /* create session */
    $userid = $u[0]['userid'];
    $sessionid = hash_hmac('sha256', "$userid $name $password ".microtime(true).openssl_random_pseudo_bytes(32), 'no need to be secret');
    $session = array(
        'userid' => $userid,
        'sessid' => $sessionid,
        'logintime' => strftime("%Y-%m-%dT%H:%M:%S")
    );
    db_query("INSERT INTO sessions(sessionid, data, expires) VALUES(:sessid, :data, NOW() + INTERVAL 10 DAY)",
        array('sessid'=>$sessionid, 'data'=>json_encode($session)));
    $user = $u[0];
    setcookie('session', $sessionid, time() + 86400*30, $ROOTPATH.'/', $COOKIEHOST, false, true);
    db_query("INSERT INTO useriplog(userid, ipaddr, attime) VALUES(:userid, :ipaddr, :attime)",
        array('userid'=>$user['userid'], 'ipaddr'=>$_SERVER['REMOTE_ADDR'], 'attime'=>strftime("%Y-%m-%d %H:%M:%S")));
    return true;
}

function user_register($name, $email, $password) {
    global $URLHOST;
    global $ROOTPATH;
    global $MAILFROM;
    $u = db_query("SELECT * FROM users WHERE name=:name OR email=:email",
        array('name'=>$name, 'email'=>$email));
    if ($u) {
        $ret = '';
        foreach ($u as $k => $v) {
            if (0 == strcasecmp($v['name'], $name)) {
                $ret .= "The name '$name' is already registered. \n";
            }
            if (0 == strcasecmp($v['email'], $email)) {
                $ret .= "The e-mail address '$email' is already registered. \n";
            }
        }
        return $ret;
    }
    $hash = password_hash($password);
    $vk = make_verify_key($email);
    $r = db_insert('users',
        array('name'=>$name,
            'email'=>$email,
            'passwordhash'=>$hash,
            'registerdate'=>strftime('%Y-%m-%d %H:%M:%S'),
            'verifykey'=>$vk),
        'userid');
    if (!$r) {
        return "Database error; cannot register user right now.";
    }
    db_query("INSERT INTO usernamelog(userid, name, setdate) VALUES(:userid, :name, NOW())",
        array('userid'=>$user['userid'], 'name'=>$name));
    db_query("INSERT INTO useremaillog(userid, email, setdate) VALUES(:userid, :email, NOW())",
        array('userid'=>$user['userid'], 'email'=>$email));
    $res = email_by_address($email, "Verify your Mech Warfare Registration", 
        "You recently registered for the Mech Warfware site. Please click this URL to verify that the email you provided is correct.\n".
        "$URLHOST$ROOTPATH/verifyok.php?code=".urlencode($vk)."&email=$email\n".
        "Registered name: $name \n".
        "If you didn't register for the site, someone else must have entered your e-mail address. Please just ignore this message if that is the case.\n");
    if (!$res) {
        return "The user was created, but the verification email could not be sent.";
    }
    return '';
}

function make_verify_key($email) {
    return hash('sha256', microtime(true)." ".posix_getpid()." ".
        openssl_random_pseudo_bytes(32)." $email");
}

function is_valid_name($name) {
    if (strpos($name, '@') !== false) {
        //  no email addreses, please
        return false;
    }
    if (strpos($name, '://')) {
        //  no URLs, please
        return false;
    }
    return strlen($name) >= 3 && strlen($name) <= 32;
}

function is_valid_email($email) {
    if (!preg_match('/[a-zA-Z0-9._+-]+@[a-zA-Z0-9_+-]+\\.[a-zA-Z0-9_.+-]+/', $email)) {
        return false;
    }
    return strlen($email) >= 8 && strlen($email) <= 64;
}

function is_valid_password($password) {
    return strlen($password) >= 6 && strlen($password) <= 72;
}

function verify_user($email, $code) {
    $u = db_query("SELECT * FROM users WHERE email=:email AND verifykey=:code", array('email'=>$email,'code'=>$code));
    if (!$u || count($u) != 1) {
        return false;
    }
    if (!db_query("UPDATE users SET verifykey='', verified=1 WHERE userid=:userid", array('userid'=>$u[0]['userid']))) {
        return false;
    }
    /* keep a log of which addresses are verified for each user. */
    db_query("UPDATE useremaillog SET verified=1 WHERE userid=:userid AND email=:email", array('userid'=>$u[0]['userid'], 'email'=>$email));
    return true;
}

function calc_gravatar($email) {
    $hash = md5(strtolower(trim($email)));
    return "<img class='gravatar' src='http://www.gravatar.com/avatar/$hash?r=r&d=retro'/>";
}


function send_user_name_email_reminder($name) {
    global $MAILFROM;
    $u = db_query("SELECT * FROM users WHERE name=:name", array('name'=>$name));
    if (!$u || count($u) != 1) {
        return "No such user was found.";
    }
    if (!email_by_address($u[0]['email'],
        'Mech Warfare Registration E-mail Reminder',
        "This is a brief reminder from the Mech Warfare Registration site.\n".
        "The email address for the account '$name' is: {$u[0][email]}\n")) {
        return "Not able to send reminder email.";
    }
    return '';
}

function send_user_password_reset($email) {
    global $URLHOST;
    global $ROOTPATH;
    global $MAILFROM;
    $u = db_query("SELECT * FROM users WHERE email=:email", array('email'=>$email));
    if (!$u || count($u) != 1) {
        return "No such e-mail was found.";
    }
    $vtok = make_verify_key($email);
    if (!email_by_address($u[0]['email'],
        'Your Password Reset Link from Mech Warfare Registration',
        "You recently requested a password reset link from the Mech Warfare Registration site. \n".
        "Click this link and enter a new password to proceed: \n".
        "$URLHOST$ROOTPATH/reset.php?email=$email&code=".urlencode($vtok)." \n")) {
        return 'Could not send password reset mail.';
    }
    $q = db_query("UPDATE users SET verifykey=:key WHERE userid=:userid",
        array('key'=>$vtok, 'userid'=>$u[0]['userid']));
    if (!$q) {
        return "Could not generate a password reset token.";
    }
    return '';
}

function verify_password_reset($email, $code) {
    $u = db_query("SELECT * FROM users WHERE email=:email AND verifykey=:code", array('email'=>$email, 'code'=>$code));
    if (!$u || count($u) != 1) {
        return "The verify code is not valid for that e-mail address.";
    }
    return '';
}

function user_change_name($user, $name) {
    global $MAILFROM;
    if ($user['name'] == $name) {
        //  no-op
        return true;
    }
    $u = db_query("SELECT * FROM users WHERE name=:name", array('name'=>$name));
    if ($u && count($u) > 0) {
        return false;   //  already taken
    }
    $u = db_query("SELECT COUNT(1) AS num FROM usernamelog WHERE userid=:userid AND setdate > NOW() - INTERVAL 30 DAY", 
        array('userid'=>$user['userid']));
    if ((int)$u[0]['num'] >= 3) {
        return false;   //  too many changes recently
    }
    $ret = db_query("UPDATE users SET name=:name WHERE userid=:userid", array('name'=>$name, 'userid'=>$user['userid']));
    if (!$ret) {
        return false;
    }
    db_query("INSERT INTO usernamelog(userid, name, setdate) VALUES(:userid, :name, NOW())", array('userid'=>$user['userid'], 'name'=>$name));
    email_by_address($user['email'],
        "Mech Warfare Registration Name Change",
        "An account registered to the e-mail address {$user[email]} recently changed the user name \n".
        "from {$user[name]} to $name. Assuming you intended to do this, you can archive this message. \n".
        "An account is allowed up to three name changes in a 30-day period, and names must not be \n".
        "offensive or misleading. \n".
        "Thanks for participating in Mech Warfare! \n");
    db_query("INSERT INTO useriplog(userid, ipaddr, attime) VALUES(:userid, :ipaddr, :attime)",
        array('userid'=>$user['userid'], 'ipaddr'=>$_SERVER['REMOTE_ADDR'], 'attime'=>strftime("%Y-%m-%d %H:%M:%S")));
    return true;
}

function user_change_email($user, $email) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    $u = db_query("SELECT * FROM users WHERE email=:email", array('email'=>$email));
    if ($u && count($u) > 0) {
        return false;   //  already taken
    }
    $q = db_query("SELECT COUNT(1) AS num FROM useremaillog WHERE userid=:userid AND setdate > NOW() - INTERVAL 30 DAY",
        array('userid'=>$user['userid']));
    if ((int)$q[0]['num'] >= 3) {
        return false;   //  rate limited
    }
    db_query("INSERT INTO useremaillog(userid, email, setdate) VALUES(:userid, :email, NOW())",
        array('userid'=>$user['userid'], 'email'=>$email));
    $vk = make_verify_key($email);
    if (!db_query("UPDATE users SET verified=0, email=:email, verifykey=:vk WHERE userid=:userid",
        array('email'=>$email, 'userid'=>$user['userid'], 'vk'=>$vk))) {
        return false;   //  DB error
    }
    email_by_address($email, 
        "Mech Warfare Registration E-mail Address Verification",
        "The user account {$user[name]} recently requested to change the e-mail address for \n".
        "the account to $email. To verify this new address, please click this link: \n".
        "$URLHOST$ROOTPATH/verifyok.php?code=".urlencode($vk)."&email=$email\n".
        "Thanks for your interest in Mech Warfare! \n");
    email_by_address($user['email'],
        "Mech Warfare Registration E-mail Change Notification",
        "Recently, the account {$user[name]} requested a change of e-mail address from the \n".
        "address {$user[email]} (to which this message is sent) to the address \n".
        "$email \n".
        "Assuming that you intended to make this change, click on the verification link in \n".
        "email sent to the new address. \n");
    db_query("INSERT INTO useriplog(userid, ipaddr, attime) VALUES(:userid, :ipaddr, :attime)",
        array('userid'=>$user['userid'], 'ipaddr'=>$_SERVER['REMOTE_ADDR'], 'attime'=>strftime("%Y-%m-%d %H:%M:%S")));
    return true;
}

function user_change_password($user, $password) {
    global $MAILFROM;
    $hash = password_hash($password);
    if (!db_query("UPDATE users SET passwordhash=:hash WHERE userid=:userid",
        array('hash'=>$hash, 'userid'=>$user['userid']))) {
        return false;
    }
    email_by_address($user['email'],
        "Mech Warfare Registration Password Change Notification",
        "Recently, the account {$user[name]} requested a change of password. \n".
        "Assuming this was you, you can archive this message; please use your new \n".
        "password the next time you log in. \n");
    db_query("INSERT INTO useriplog(userid, ipaddr, attime) VALUES(:userid, :ipaddr, :attime)",
        array('userid'=>$user['userid'], 'ipaddr'=>$_SERVER['REMOTE_ADDR'], 'attime'=>strftime("%Y-%m-%d %H:%M:%S")));
    return true;
}

function get_user_by_id($userid, $require_valid=true) {
    $str = "";
    if ($require_valid) {
        $str = " AND verified=1";
    }
    $ret = db_query("SELECT * FROM users WHERE userid=:id $str", array('id'=>$userid));
    if (!is_array($ret)) {
        return null;
    }
    return $ret[0];
}

function get_admin_users($level = 1) {
    return db_query("SELECT * FROM users WHERE adminlevel>:level AND verified=1",
        array('level'=>$level));
}
