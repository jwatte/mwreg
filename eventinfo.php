<?php

function valid_event($name, $location, datetime $starttime, datetime $endtime, $url) {
    if (!is_valid_event_name($name)) {
        return array(false, "The proposed name '$name' is not valid.");
    }
    if (!is_valid_location($location)) {
        return array(false, "The proposed location '$location' is not valid.");
    }
    if (!is_valid_eventtime($starttime)) {
        return array(false, "The proposed start time '".$starttime->format(datetime::W3C)."' is not valid.");
    }
    if (!is_valid_eventtime($endtime)) {
        return array(false, "The proposed end time '".$endtime->format(datetime::W3C)."' is not valid.");
    }
    $diff = $endtime->getTimestamp() - $startime->getTimestamp();
    if ($diff < 0 || $diff > 32 * 24 * 60 * 60) {
        return array(false, "The proposed end time '".$endtime->format(datetime::W3C)."' doesn't match start time '".$starttime->format(datetime::W3C)."'.");
    }
    if (!is_valid_url($url)) {
        return array(false, "The proposed URL is not valid.");
    }
    return array(true, null);
}

function update_event(array $event) {
    $comma = "";
    $b = "(";
    foreach ($event as $key=>$value) {
        $b .= "$comma$key=:$key";
        $comma = ",";
    }
    db_query("UPDATE events SET $b WHERE eventid=:eventid", $event);
}

function delete_event(array $event) {
    db_query("DELETE FROM events WHERE eventid=:eventid", $event['eventid']);
}

function propose_event(
    array $proposing_user,
    $name,
    $location,
    datetime $starttime,
    datetime $endtime,
    $url) {

    global $URLHOST;
    global $ROOTPATH;
    global $MAILFROM;

    $submitter = $proposing_user['userid'];
    $n = db_query("SELECT COUNT(1) AS count FROM proposedevents WHERE submitter=:userid AND approvedtime IS NULL ".
        "AND submittedtime > DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    if (!$n) {
        errors_fatal("Internal error in propose_event()");
    }
    if ($n[0]['count'] > 3) {
        return array(false, "Limit reached for number of pending events submitted by this user.");
    }
    list($ok, $err) = valid_event($name, $location, $starttime, $endtime, $url);
    if (!$ok) {
        return array($ok, $err);
    }
    $subuser = get_user_by_id((int)$submitter);
    if (!$subuser) {
        return array(false, "The proposed submitter is not valid.");
    }
    $now = new DateTime();
    $id = db_insert('proposedevents', array(
        'name'=>$name,
        'location'=>$location,
        'starttime'=>$starttime->format(datetime::W3C),
        'endtime'=>$endtime->format(datetime::W3C),
        'url'=>$url,
        'submitter'=>$subuser['userid'],
        'submittedtime'=>$now->format(datetime::W3C)
    ), 'proposedeventid');
    if ($id) {
        mail($subuser['email'],
            "Your event was proposed to Mech Warfare Registration",
            "The event named '$name' was proposed at Mech Warfare Registration. \n".
            "A moderator will approve or reject this proposal, and you will receive \n".
            "a second email when that happens.\n".
            "Thank you for using Mech Warfare Registration!\n",
            "From: $MAILFROM");
        $au = get_admin_users();
        foreach ($au as $k => $v) {
            mail($v['email'],
                "New event proposed for Mech Warfare Registration",
                "An event was proposed for Mech Warfare Registration:\n".
                "Name: $name\n".
                "Submitter: $subuser[name]\n".
                "Start: ".$starttime->format(datetime::W3C)."\n".
                "You can manage proposed events at \n".
                "$URLHOST$ROOTPATH/proposedevents.php\n",
                "From: $MAILFROM");
        }
    }
    return $id ? array(true, $id) : array(false, "Failure to insert proposed event.");
}

function get_all_future_events() {
    return db_query("SELECT * FROM events WHERE published > 0 AND endtime > NOW()", array());
}

function get_proposed_event_by_id($eid) {
    $eid = (int)$eid;
    if (!$eid) {
        errors_fatal("Bad proposedeventid in get_proposed_event_by_id.");
    }
    return db_query("SELECT p.eventid AS eventid, p.name AS name, p.location AS location, p.starttime AS starttime, ".
        "p.endtime AS endtime, p.url AS url, p.submitter AS submitter, u.name AS submitter_name, u.email AS submitter_email ".
        "FROM proposedevents p LEFT OUTER JOIN users u ON p.submitter = u.userid WHERE p.proposedeventid=:id", array('id'=>$eid));
}

function get_open_proposed_events() {
    return db_query("SELECT p.eventid AS eventid, p.name AS name, p.location AS location, p.starttime AS starttime, ".
        "p.endtime AS endtime, p.url AS url, p.submitter AS submitter, u.name AS submitter_name, u.email AS submitter_email ".
        "FROM proposedevents p LEFT OUTER JOIN users u ON p.submitter = u.userid WHERE approvedtime IS NULL", array());
}

function approve_proposed_event(array $event, array $user) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    $u = get_user_by_id($user['userid']);
    if (!$u || !$u['adminlevel']) {
        errors_fatal("User $user[userid] cannot approve events.");
    }
    $e = get_proposed_event_by_id($event['proposedeventid']);
    if (!$e) {
        errors_fatal("Event $event[proposedeventid] is not valid.");
    }
    $id = db_insert("events",
        array('name'=>$event['name'], 'location'=>$event['location'], 'starttime'=>$event['starttime'],
        'endtime'=>$event['endtime'], 'url'=>$event['url'], 'submitter'=>$event['submitter']),
        "eventid"
    );
    db_query("UPDATE proposedevents SET approver=:approver, approvedtime=NOW() WHERE proposdeventid=:id",
        array('approver'=>$user['userid'], 'id'=>$event['proposedeventid']));
    email($user['email'],
        "Your event named $event[name] was accepted",
        "You proposed an event named $event[name] for the Mech Warfar Registration \n".
        "web site. A moderator has approved this event, and it should now be visible \n".
        "on the event calendar: \n".
        "$URLHOST$ROOTPATH/events.php?id=$id\n",
        "From: $MAILFROM");

    return $id;
}

function reject_proposed_event(array $event, array $user) {
    global $MAILFROM;
    global $URLHOST;
    global $ROOTPATH;
    $u = get_user_by_id($user['userid']);
    if (!$u || !$u['adminlevel']) {
        errors_fatal("User $user[userid] cannot approve events.");
    }
    $e = get_proposed_event_by_id($event['proposedeventid']);
    if (!$e) {
        errors_fatal("Event $event[proposedeventid] is not valid.");
    }
    db_query("DELETE FROM proposedevents WHERE proposdeventid=:id",
        array('id'=>$event['proposedeventid']));
    email($user['email'],
        "Your event named $event[name] was rejected",
        "You proposed an event named $event[name] for the Mech Warfar Registration \n".
        "web site. A moderator has rejected this event for some reason. You can \n".
        "see a list of currently accepted events here: \n".
        "$URLHOST$ROOTPATH/events.php\n",
        "From: $MAILFROM");
}

