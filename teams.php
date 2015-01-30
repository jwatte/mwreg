<?php

require_once 'init.php';
require_once 'dbconnect.php';
require_once 'userinfo.php';
require_once 'pages.php';
require_once 'teaminfo.php';
require_once 'mechinfo.php';

$_action = @$_POST['action'];
if ($_action && !verify_csrf(@$_POST['csrf'])) {
    $teams_error = "The server detected an error in editing the team. There is a time-out for editing each form.";
} else if ($_action == 'newteam') {
    $_tid = make_new_team();
    if ($_tid) {
        $_GET['id'] = $_tid;
    } else {
        $teams_error = "Could not create new team.";
    }
} else if ($_action == 'editteam') {
    $_tid = @$_POST['id'];
    $_GET['id'] = $_tid;
    $_team = get_team_by_id((int)$_tid);
    if (!$_team) {
        $teams_error = "There is no such team.";
    } else {
        if (is_team_admin($user['userid'], $_team)) {
            $_name = @$_POST['name'];
            $_url = @$_POST['url'];
            if (!is_valid_team_name($_name)) {
                $teams_error = "The name '$_name' is not a valid team name.";
            } else if (!is_valid_team_url($_url)) {
                $teams_error = "The url '$_url' is not a valid team URL.";
            } else {
                db_query("UPDATE teams SET name=:name, URL=:url WHERE teamid=:teamid",
                    array('teamid'=>$_tid, 'name'=>$_name, 'url'=>$_url));
                $team_ok = "Team updated.";
            }
        } else {
            $teams_error = "You do not have permission to edit this team.";
        }
    }
} else if ($_action == 'apply') {
    $_tid = (int)@$_POST['id'];
    $_team = get_team_by_id((int)$_tid);
    $_GET['id'] = $_tid;
    if (!$_team) {
        $teams_error = "There is no such team.";
    } else if (is_team_member($user['userid'], $_team)) {
        $teams_error = "You are aready a member.";
    } else {
        apply_for_team($_tid, $user);
    }
} else if ($_action == 'approve') {
    $_tid = @$_POST['id'];
    $_GET['id'] = $_tid;
    $_team = get_team_by_id((int)$_tid);
    $_user = get_user_by_id((int)$_POST['user']);
    if (!$_team) {
        $teams_error = "There is no such team.";
    } else if (!$_user) {
        $teams_errir = "There is no such user.";
    } else if (!is_team_admin($user['userid'], $_team)) {
        $teams_error = "Only team admins can approve new memberships.";
    } else {
        approve_team_member($_tid, $_user);
    }
} else if ($_action == 'reject') {
    $_tid = @$_POST['id'];
    $_GET['id'] = $_tid;
    $_team = get_team_by_id((int)$_tid);
    $_user = get_user_by_id((int)$_POST['user']);
    if (!$_team) {
        $teams_error = "There is no such team.";
    } else if (!$_user) {
        $teams_error = "There is no such user.";
    } else if (!is_team_admin($user['userid'], $_team)) {
        $teams_error = "Only team admins can reject new memberships.";
    } else {
        reject_team_member($_tid, $_user);
    }
} else if ($_action == 'addmech') {
    $_tid = @$_GET['id'];
    $_team = get_team_by_id($_tid);
    $_mechid = @$_POST['mechid'];
    $_minfo = get_mech_by_id($_mechid);
    if (!$_team) {
        $team_error = "There is no team ID $_tid.";
    } else if (!$_minfo) {
        $teams_error = "There exists no mech with id $_mechid.";
    } else if ($_minfo['team']) {
        $teams_error = "The mech $_minfo[name] is already on team id $_minfo[team].";
    } else if ($_minfo['builder'] != $user['userid']) {
        $teams_error = "Only the builder of a mech can add it to a team.";
    } else if (!is_team_member($user['userid'], $_team)) {
        $teams_error = "User $user[userid] is not member of team $_tid.";
    } else {
        add_mech_to_team($_mechid, $_team);
    }
} else if ($_action == 'removemech') {
    $_team = get_team_by_id($_GET['id']);
    if (!$_team) {
        errors_fatal("There is no team '$_GET[id]'.");
    }
    $_mech = get_mech_by_id($_POST['mechid']);
    if (!$_mech) {
        errors_fatal("There is no mech '$_POST[mechid]'.");
    }
    if (!is_team_admin($user['userid'], $_team) &&
        !is_mech_builder($user['userid'], $_mech)) {
        $teams_error = "User $user[userid] is not an admin of $_team[name].";
    } else {
        remove_mech_from_team($_mech, $_team);
        $teams_error = "Mech $_mech[name] removed from team $_team[name].";
    }
} else if ($_action == 'removemember') {
    $_team = get_team_by_id($_GET['id']);
    if (!$_team) {
        errors_fatal("There is no team '$_GET[id]'.");
    }
    $_user = get_user_by_id($_POST['userid']);
    if (!$_user) {
        errors_fatal("There is no user '$_POST[userid]'.");
    }
    if (!is_team_admin($user['userid'], $_team) &&
        $user['userid'] != $_user['userid']) {
        $teams_error = "You do not have permission to remove $_user[name] from $_team[name].";
    } else {
        remove_member_from_team($_team, $_user);
        $teams_error = "Member $_user[name] was removed from team $_team[name].";
    }
}
require_once 'page/teams.php';

require_once 'finish.php';
