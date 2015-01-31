<?php

require_once 'init.php';
require_once 'dbconnect.php';
require_once 'userinfo.php';
require_once 'eventinfo.php';
require_once 'pages.php';

$_action = @$_POST['action'];
$_peid = @$_POST['pid'];
$_eid = @$_GET['id'];
if ($_action) {
    if (!verify_csrf(@$_POST['csrf'])) {
        errors_fatal("Verification of POST request failed.");
    }
    if ($_action == 'proposeevent') {
    } else if ($_action == 'proposeeventsubmit') {
        list($ok, $err) = propose_event($user,
            $_POST['name'],
            $_POST['location'],
            new datetime($_POST['starttime']),
            new datetime($_POST['endtime']),
            $_POST['url']
        );
        if (!$ok) {
            errors_fatal("Could not submit proposed event: $err");
        }
    } else if ($user['adminlevel'] < 1) {
        errors_fatal("You are not permitted to manage events.");
    } else if ($_action == 'acceptevent') {
        $_pevent = get_proposed_event_by_id($_peid);
        if (!$_pevent) {
            errors_fatal("There is no proposed event by id $_peid.");
        }
        $id = approve_proposed_event($_pevent, $user);
        $_GET['id'] = $id;
    } else if ($_action == 'rejectevent') {
        $_pevent = get_proposed_event_by_id($_peid);
        if (!$_pevent) {
            errors_fatal("There is no proposed event by id $_peid.");
        }
        reject_proposed_event($_pevent, $user);
        $_GET['id'] = null;
    } else if ($_action == 'editevent') {
        $_ev = get_event_by_id($_eid);
        if (!$_ev) {
            errors_fatal("There is no event by id $_eid.");
        }
        list($ok, $err) = valid_event($_POST['name'],
            $_POST['location'],
            new datetime($_POST['starttime']),
            new datetime($_POST['endtime']),
            $_POST['url']);
        if ($ok) {
            update_event(array('eventid'=>$_eid,
                'name'=>$_POST['name'],
                'location'=>$_POST['location'],
                'starttime'=>$_POST['starttime'],
                'endtime'=>$_POST['endtime'],
                'url'=>$_POST['url']));
        } else {
            errors_fatal($err);
        }
    } else if ($_action == 'deleteevent') {
        $_ev = get_event_by_id($_eid);
        if (!$_ev) {
            errors_fatal("There is no event by id $_eid.");
        }
        delete_event($_ev);
        $_GET['id'] = null;
    }
}
require_once 'page/events.php';

require_once 'finish.php';
