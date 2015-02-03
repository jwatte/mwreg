<?php

function email_by_userid(
    $userid,
    $subject,
    $body)
{
    $u = get_user_by_id($userid);
    if (!$u) {
        errors_fatal("There is no user with id $userid.");
    }
    return email_by_address($u['email'], $subject, $body);
}

function email_by_address(
    $useremail,
    $subject,
    $body)
{
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    /* don't allow crazy repeat if there's some sneaky bug/hole. */
    $r = db_query("SELECT COUNT(1) AS count FROM sentmails WHERE sendtime > DATE_SUB(NOW(), INTERVAL 1 DAY)", array());
    if ($r && $r[0]['count'] > 999 && !($r['count'] % 1000)) {
        mail("hplus@mindcontrol.org", "Too many emails sent in one day from mwreg",
            "MWReg is configured to only allow 1000 emails per day -- now at $r[count]",
            "From: $MAILFROM");
        return;
    }
    db_query("INSERT INTO sentmails(address, subject, sendtime) VALUES(:address, :subject, NOW())",
        array('address'=>$useremail, 'subject'=>$subject));
    return mail($useremail,
        $subject,
        $body,
        "From: $MAILFROM");
}

