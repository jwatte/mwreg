<?php

require_once 'init.php';
require_once 'dbconnect.php';
require_once 'userinfo.php';
require_once 'eventinfo.php';
require_once 'mechinfo.php';
require_once 'teaminfo.php';
require_once 'pages.php';

$_action = @$_POST['action'];
$_peid = @$_POST['pid'];
$_eid = @$_GET['id'];
if ($_action) {
    if (!verify_csrf(@$_POST['csrf'])) {
        errors_fatal("Verification of POST request failed.");
    }
    if ($_action == 'signupmech') {
        $_mech = get_mech_by_id($_POST['mechid']);
        $_team = get_team_by_id($_POST['teamid']);
        $_event = get_event_by_id($_eid);
        if (!$_mech) {
            errors_fatal("There is no mech id $_POST[mechid].");
        }
        if (!$_team) {
            errors_fatal("There is no team id $_POST[teamid].");
        }
        if (!$_event) {
            errors_fatal("There is no event id $_eid.");
        }
        if (!is_team_mech($_mech, $_team)) {
            errors_fatal("The mech $_mech[name] is not available for team $_team[name].");
        }
        if (!is_team_admin($user['userid'], $_team)) {
            errors_fatal("You are not an admin for team $_team[name].");
        }
        sign_up_mech_for_event($_mech, $_team, $_event);
    } else if ($_action == 'proposeevent') {
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
