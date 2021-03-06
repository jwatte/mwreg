<?php

require_once 'mailinfo.php';

function make_new_team() {
    global $user;
    if (!$user) {
        //  not logged in
        return false;
    }
    $teams = get_teams_by_leader($user['userid']);
    if ($teams) {
        //  already leading a team
        return false;
    }
    $newteam = db_insert(
        'teams',
        array(
            'name' => "$user[name]'s Team",
            'leader' => $user['userid'],
            'url' => ""
        ),
        'teamid');
    db_query("INSERT INTO teammembers(teamid, userid, membersince, teamadmin, approved) ".
        "VALUES(:teamid, :userid, NOW(), 1, 1)", array('teamid'=>$newteam, 'userid'=>$user['userid']));
    return $newteam;
}

function get_team_by_id($teamid) {
    $t = db_query("SELECT t.name AS name, t.teamid AS teamid, t.url AS url, u.name AS leadername, u.userid AS leader " .
            "FROM teams t, users u WHERE t.teamid=:teamid and u.userid=t.leader", array('teamid'=>$teamid));
    if (!$t || !$t[0]) {
        return null;
    }
    $team = $t[0];
    $c = db_query("SELECT m.mechid AS mechid, m.name AS name, m.url AS url, u.name AS builder " .
        "FROM mechs m, users u WHERE m.builder=u.userid AND m.team=:teamid", array('teamid'=>$teamid));
    $team['mechs'] = $c;
    $c = db_query("SELECT u.name AS name, u.userid AS userid, tm.membersince AS membersince, tm.teamadmin AS teamadmin, tm.approved as approved ".
            "FROM teammembers tm, users u WHERE tm.userid=u.userid AND tm.teamid=:teamid", array('teamid'=>$teamid));
    $members = array();
    $applicants = array();
    foreach ($c as $v) {
        if ($v['approved']) {
            $members[] = $v;
        } else {
            $applicants[] = $v;
        }
    }
    $team['members'] = $members;
    $team['applicants'] = $applicants;
    return $team;
}

function get_teams_by_leader($leader) {
    $t = db_query("SELECT * FROM teams WHERE leader=:leader", array('leader'=>$leader));
    if (!$t) {
        return null;
    }
    return $t;
}

function get_teams_by_admin($admin) {
    $t = db_query("SELECT t.name as name, t.teamid as teamid, t.leader as leader, t.url as url, m.teamadmin as teamadmin ".
        "FROM teams t, teammembers m ".
        "WHERE t.teamid = m.teamid AND m.teamadmin > 0 AND m.approved > 0 AND m.userid = :admin",
        array('admin'=>$admin));
    return $t ? $t : null;
}

function is_valid_team_name($name) {
    return strlen($name) >= 5 && strlen($name) <= 95 && strpos($name, '@') === false && strpos($name, '://') === false &&
        strpos($name, '<') === false && strpos($name, '&') === false;
}

function is_valid_team_url($url) {
    if ($url === '') {
        return true;
    }
    if (strlen($url) < 12) {
        return false;
    }
    if (strpos($url, "http://") !== 0 &&
        strpos($url, "https://") !== 0) {
        return false;
    }
    return true;
}

function is_team_admin($userid, array $team) {
    if ($userid === $team['leader']) {
        return true;
    }
    foreach ($team['members'] as $mem) {
        if ($mem['userid'] === $userid) {
            return $mem['teamadmin'];
        }
    }
    return false;
}

function is_team_member($userid, array $team) {
    if ($team['leader'] === $userid) {
        return true;
    }
    foreach ($team['members'] as $mem) {
        if ($mem['userid'] === $userid) {
            return true;
        }
    }
    return false;
}

function apply_for_team($teamid, array$user) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    $team = get_team_by_id($teamid);
    if (!$team) {
        errors_fatal("There is no team $teamid");
    }
    db_query("INSERT INTO teammembers(teamid, userid, membersince) VALUES" .
        "(:teamid, :userid, NOW()) ON DUPLICATE KEY UPDATE membersince=NOW()",
            array('teamid'=>$teamid, 'userid'=>$user['userid']));
    email_by_address($user['email'],
        "You applied for team $team[name]",
        "You applied for membership in team $team[name] on Mech Warfare Registration.\n".
        "Once the team administrator has approved your membership, you will be sent another email.\n");
    $leader = get_user_by_id($team['leader']);
    email_by_address($leader['email'],
        "User $user[name] applied for membership in team $team[name]",
        "User $user[name] id $user[userid] applied for membership in team $team[name] on Mech Warfare Registration.\n".
        "You can approve or reject this application in the team control panel.\n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n");
}

function approve_team_member($teamid, array $iuser) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    $team = get_team_by_id($teamid);
    db_query("UPDATE teammembers SET approved=1 WHERE teamid=:teamid AND userid=:userid",
        array('teamid'=>$teamid, 'userid'=>$iuser['userid']));
    email_by_address($iuser['email'],
        "You were approved as member in team $team[name]",
        "The administrator for team $team[name] on Mech Warfare Registration approved your application for membership.\n".
        "You can view information about this team at:\n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n");
}

function reject_team_member($teamid, array $iuser) {
    db_query("DELETE FROM teammembers WHERE teamid=:teamid AND userid=:userid",
        array('teamid'=>$teamid, 'userid'=>$iuser));
    // don't send email
}

function remove_member_from_team(array $team, array $member) {
    $owner = db_query("SELECT * FROM teams WHERE teamid=:teamid AND leader=:userid",
        array('teamid'=>$team['teamid'], 'userid'=>$member['userid']));
    if ($owner) {
        errors_fatal("Cannot remove the team leader from a team (owner $member[userid], team $team[teamid].)");
    }
    db_query("DELETE FROM teammembers WHERE teamid=:teamid AND userid=:userid",
        array('teamid'=>$team['teamid'], 'userid'=>$member['userid']));
    email_by_address($member['email'],
        "You were removed as member from team $team[name]",
        "The administrator for team $team[name] on Mech Warfare Registration removed you from membership of the team.\n".
        "You can view information about this team at:\n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n");
    $leader = get_user_by_id($team['leader']);
    email_by_address($leader['email'],
        "User $member[name] was removed from team $team[name]",
        "User $member[name] id $member[userid] was removed from membership in team $team[name] on Mech Warfare Registration.\n".
        "You can view information about this team at:\n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n");
}

function is_team_mech(array $mech, array $team) {
    foreach ($team['mechs'] as $m) {
        if ($m['mechid'] == $mech['mechid']) {
            return true;
        }
    }
    return false;
}

