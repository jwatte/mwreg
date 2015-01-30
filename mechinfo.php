<?php

function get_all_mechs() {
    return db_query(
        "SELECT m.mechid AS mechid, m.name AS name, m.builder AS builder, m.team AS team, m.url AS url, ".
        "t.name AS teamname, u.name AS username FROM mechs m LEFT OUTER JOIN users u ON m.builder=u.userid ".
        "LEFT OUTER JOIN teams t ON m.team=t.teamid ".
        "WHERE 1", array());
}

function get_mech_by_id($mechid) {
    $ret = db_query(
        "SELECT m.mechid AS mechid, m.name AS name, m.builder AS builder, m.team AS team, m.url AS url, ".
        "t.name AS teamname, u.name AS username FROM mechs m LEFT OUTER JOIN users u ON m.builder=u.userid ".
        "LEFT OUTER JOIN teams t ON m.team=t.teamid ".
        "WHERE m.mechid=:mechid",
        array('mechid'=>$mechid));
    return $ret ? $ret[0] : null;
}

function get_mechs_by_userid($userid) {
    $ret = db_query(
        "SELECT m.mechid AS mechid, m.name AS name, m.builder AS builder, m.team AS team, m.url AS url, ".
        "t.name AS teamname, u.name AS username FROM mechs m LEFT OUTER JOIN users u ON m.builder=u.userid ".
        "LEFT OUTER JOIN teams t ON m.team=t.teamid ".
        "WHERE u.userid=:userid",
        array('userid'=>$userid));
    return $ret;
}

function get_mechs_by_teamid($teamid) {
    $ret = db_query(
        "SELECT m.mechid AS mechid, m.name AS name, m.builder AS builder, m.team AS team, m.url AS url, ".
        "t.name AS teamname, u.name AS username FROM mechs m LEFT OUTER JOIN users u ON m.builder=u.userid ".
        "LEFT OUTER JOIN teams t ON m.team=t.teamid ".
        "WHERE t.teamid=:teamid",
        array('teamid'=>$teamid));
    return $ret;
}

function create_mech(array $builder, $name, array $team, $url) {
    if (!$builder || !@$builder['userid']) {
        errors_fatal("Bad builder for create_mech()");
    }
    if ($team && !@$team['teamid']) {
        errors_fatal("Bad team for create_mech()");
    }
    $q = db_insert("mechs", array(
            'name'=>$name,
            'builder'=>$builder['userid'],
            'team'=>$team ? $team['teamid'] : null,
            'url'=>$url),
        "mechid");
    return $q;
}

function add_mech_to_team($mechid, array $team) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    if (!$team || !@$team['teamid']) {
        errors_fatal("Bad teamid in add_mech_to_team");
    }
    if (!(int)$mechid) {
        errors_fatal("Bad mech id '$mechid' in add_mech_to_team");
    }
    $mech = get_mech_by_id($mechid);
    if (!$mech) {
        errors_fatal("Bad mech '$mechid' in add_mech_to_team : " . print_r($mech, true));
    }
    $builder = get_user_by_id($mech['builder']);
    if (!$builder) {
        errors_fatal("Bad mech info '$mechid' in add_mech_to_team : " . print_r($mech, true));
    }
    db_query("UPDATE mechs SET team=:teamid WHERE mechid=:mechid",
        array('teamid'=>$team['teamid'], 'mechid'=>$mechid));
    mail($builder['email'],
        "Your mech was added to team $team[name]",
        "The mech $mech[name] that you are listed as builder for was added \n".
        "to the team $team[name]. You can view the team roster at: \n".
        "$URLHOST$ROOTPATH/teams.php?id=$team[teamid]\n",
        "From: $MAILFROM");
}

function get_events_for_mech($mid) {
    return db_query("SELECT e.name AS eventname, e.starttime AS eventtime, u.name AS regusername, m.regtime AS regtime ".
        "FROM mech_event_registration m LEFT OUTER JOIN events e ON m.eventid=e.eventid ".
        "LEFT OUTER JOIN users u ON m.reguser=u.userid WHERE e.mechid=:mechid ".
        "ORDER BY regtime DESC", array('mechid'=>$mid));
}

function is_valid_mech_name($name) {
    $name = trim($name);
    if (strlen($name) < 4) {
        return false;
    }
    if (strlen($name) > 95) {
        return false;
    }
    if (strpos($name, '@') !== false) {
        return false;
    }
    if (strpos($name, '://') !== false) {
        return false;
    }
    if (strpos($name, '<') !== false) {
        return false;
    }
    if (strpos($name, '&') !== false) {
        return false;
    }
    return true;
}

function is_valid_mech_url($url) {
    $url = trim($url);
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


