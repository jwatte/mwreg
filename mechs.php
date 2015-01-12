<?php

require_once 'init.php';
require_once 'dbconnect.php';
require_once 'userinfo.php';
require_once 'pages.php';
require_once 'mechinfo.php';

$_action = @$_POST['action'];
$_mechid = (int)@$_GET['mechid'];
$mechs_error = '';
if ($_action && !verify_csrf($_POST['csrf'])) {
    $mechs_error = "The server detected an error in editing the team. There is a time-out for editing each form.";
} else if ($_action && !$user) {
    $mechs_error = "You must be logged in to register/edit mechs.";
} else if ($_action == 'newmech') {
    $_action = '';
    $_mechid = '';
    $m = get_mechs_by_userid($user['userid']);
    if (count($m) >= 3) {
        $mechs_error = "You can register at most 3 mechs.";
    } else {
        $mid = db_insert('mechs', array('name'=>$user['name']."'s Mech", 'builder'=>$user['userid'], 'team'=>0, 'url'=>''), 'mechid');
        if (!$mid) {
            $mechs_error = 'Error creating mech.';
        } else {
            $_action = 'editmech';
            $_mechid = $mid;
        }
    }
} else if ($_action == 'editmech') {
    $_mechid = @$_POST['mechid'];
    $_mech = get_mech_by_id($_mechid);
    if (!$_mech || $_mech['builder'] != $user['userid']) {
        $mechs_error = "You do not have a mech with ID $_mechid.";
    } else if (!is_valid_mech_name($_POST['name'])) {
        $mechs_error = "The name '$_POST[name]' is not a valid mech name.";
    } else if (!is_valid_mech_url($_POST['url'])) {
        $mechs_error = "The url '$_POST[url]' is not a valid mech URL.";
    } else {
        $q = db_query("UPDATE mechs SET name=:name, url=:url WHERE mechid=:mechid",
            array('name'=>$_POST['name'], 'url'=>$_POST['url'], 'mechid'=>$_mechid));
    }
}

require_once 'page/mechs.php';

require_once 'finish.php';
