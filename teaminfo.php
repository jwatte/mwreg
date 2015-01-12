<?php

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
    mail($user['email'],
        "You applied for team $team[name]",
        "You applied for membership in team $team[name] on Mech Warfare Registration.\n".
        "Once the team administrator has approved your membership, you will be sent another email.\n",
        "From: $MAILFROM");
    $leader = get_user_by_id($team['leader']);
    mail($leader['email'],
        "User $user[name] applied for membership in team $team[name]",
        "User $user[name] id $user[userid] applied for membership in team $team[name] on Mech Warfare Registration.\n".
        "You can approve or reject this application in the team control panel.\n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n",
        "From: $MAILFROM");
}

function approve_team_member($teamid, array $iuser) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    $team = get_team_by_id($teamid);
    db_query("UPDATE teammembers SET approved=1 WHERE teamid=:teamid AND userid=:userid",
        array('teamid'=>$teamid, 'userid'=>$iuser['userid']));
    mail($iuser['email'],
        "You were approved as member in team $team[name]",
        "The administrator for team $team[name] on Mech Warfare Registration approved your application for membership.\n".
        "You can view information about this team at:\n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n",
        "From: $MAILFROM");
}

function reject_team_member($teamid, array $iuser) {
    db_query("DELETE FROM teammembers WHERE teamid=:teamid AND userid=:userid",
        array('teamid'=>$teamid, 'userid'=>$iuser));
    // don't send email
}


