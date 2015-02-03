<?php
require_once('header.php');
require_once('mechinfo.php');
require_once('eventinfo.php');
require_once('teaminfo.php');
require_once('userinfo.php');
page_header('Mech Warfare Registration -- Events');
?>
<div class='content'>
<?
    $_isadmin = $user['adminlevel'] > 0;
    if (@$_GET['id']) {
        $event = get_event_by_id($_GET['id']);
        if (!$event) {
            errors_fatal("There is no event id $_GET[id]");
        }
        /* display one event */
        if ($_isadmin) {
            function fn($name, $value) {
                echo "<div class='formfield'><span class='label'>$name:</span><span class='field'><input type='text' name='$name' value='".htmlquote($value)."'/></span></div>";
            }
            echo "<form method='post'>";
        } else {
            function fn($name, $value) {
                echo "<div class='info'><div class='label'>$name:</div><div class='value'>".htmlquote($value)."</div></div>";
            }
            echo "<div class='list event'>";
        }
        fn('name', $event['name']);
        fn('location', $event['location']);
        fn('starttime', $event['starttime']);
        fn('endtime', $event['endtime']);
        fn('url', $event['url']);
        if ($_isadmin) {
            echo get_csrf_input();
            echo "<button name='action' value='editevent'>Update Event</button>";
            echo "</form>";
        } else {
            echo "</div>";
        }
        $mechs = get_mechs_by_event($event['eventid']);
        $imadmin = get_teams_by_admin($user['userid']);
        if ($imadmin) {
            /* client-side filtering */
            $hasmech = array();
            if ($mechs) {
                foreach ($mechs as $ix => $m) {
                    $hasmech[$m['mechid']] = true;
                }
            }
            echo "<div class='heading'>Sign Up Mechs</div>";
            echo "<div class='signup list'>";
            foreach ($imadmin as $ix => $t) {
                $m = get_mechs_by_teamid($t['teamid']);
                foreach ($m as $mix => $m) {
                    echo "<form class='teammechsignup item listitem' method='post'>";
                    echo "<div class='teamname'>".htmlquote($t['name'])."</div>";
                    echo "<div class='mechname'>".htmlquote($m['name'])."</div>";
                    if (!$hasmech[$m['mechid']]) {
                        echo "<button name='action' value='signupmech'>Sign Up</button>";
                        echo get_csrf_input();
                        echo "<input type='hidden' name='mechid' value='".htmlquote($m['mechid'])."'/>";
                        echo "<input type='hidden' name='teamid' value='".htmlquote($t['teamid'])."'/>";
                    } else {
                        echo "<div class='info'>Already Signed Up</div>";
                    }
                    echo "</form>";
                }
            }
            echo "</div>";
        }
        if ($mechs) {
            echo "<div class='heading'>Signed Up Mechs</div>";
            echo "<div class='mechs signedup list'>";
            foreach ($mechs as $ix => $m) {
                echo "<div class='item mech'>";
                echo "<div class='mechname'>".htmlquote($m['name'])."</div>";
                echo "<div class='buildername'>".htmlquote($m['buildername'])."</div>";
                echo "<div class='teamname'>".htmlquote($m['teamname'])."</div>";
                echo "</div>"; 
            }
            echo "</div>";
        }
    } else if ($_action == 'proposeevent') {
        /* empty propose-event form */
        function fn($name, $value) {
            echo "<div class='formfield'><span class='label'>$name:</span><span class='field'><input type='text' name='$name' value='".htmlquote($value)."'/></span></div>";
        }
        echo "<form method='post'>";
        fn('name', $event['name']);
        fn('location', $event['location']);
        fn('starttime', $event['starttime']);
        fn('endtime', $event['endtime']);
        fn('url', $event['url']);
        echo get_csrf_input();
        echo "<button name='action' value='proposeeventsubmit'>Propose Event</button>";
        echo "</form>";
    } else if ($_action == 'proposeeventsubmit') {
        echo "<div class='result'>Thank you for submitting a proposed event. You will receive ".
            "an e-mail verifying the proposal, and another e-mail once a moderator has ".
            "reviewed the event for possible inclusion in the database.</div>";
    } else {
        /* show all events */
        $events = get_all_future_events();
        echo "<div class='list events'>";
        foreach ($events as $e) {
            echo "<div class='info event'>";
            echo "<div class='eventid'>".htmlquote($e['eventid'])."</div>";
            echo "<div class='name'>".htmlquote($e['name'])."</div>";
            echo "<div class='location'>".htmlquote($e['location'])."</div>";
            echo "<div class='starttime'>".htmlquote($e['starttime'])."</div>";
            echo "<div class='endtime'>".htmlquote($e['endtime'])."</div>";
            echo "<div class='url'>".htmlquote($e['url'])."</div>";
            echo "</div>";
        }
        echo "</div>";
        echo "<form method='post' class='propose event'>".get_csrf_input()."<button name='action' value='proposeevent'>Propose New Event</button></form>";
    }
    if ($_isadmin) {
        echo "<div class='heading'>Proposed Events</div>";
        echo "<div class='list proposed events'>";
        $proposed = get_open_proposed_events();
        if ($proposed) foreach ($proposed as $p) {
            echo "<div class='info event'>";
            echo "<div class='proposedeventid'>".htmlquote($p['proposedeventid'])."</div>";
            echo "<div class='name'>".htmlquote($p['name'])."</div>";
            echo "<div class='location'>".htmlquote($p['location'])."</div>";
            echo "<div class='starttime'>".htmlquote($p['starttime'])."</div>";
            echo "<div class='endtime'>".htmlquote($p['endtime'])."</div>";
            echo "<div class='url'>".htmlquote($p['url'])."</div>";
            echo "<div class='submitter'>".htmlquote($p['submitter_name'])." ".htmlquote($p['submitter_email'])."</div>";
            echo "<form method='post' class='actions'>".get_csrf_input()."<button name='action' value='acceptevent'>Accept</button>".
                "<button name='action' value='rejectevent'>Reject</button><input type='hidden' name='pid' value='".
                htmlquote($p['proposedeventid'])."'/></form>";
            echo "</div>";
        }
        echo "</div>";
    }
?>
</div>
<div class='footer'>
</div>
</body>
