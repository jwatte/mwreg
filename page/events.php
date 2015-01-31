<?php
require_once('header.php');
page_header('Mech Warfare Registration -- Events');
?>
<div class='content'>
<?
    $_isadmin = $user['adminlevel'] > 0;
    if (@$_GET['id']) {
        /* display one event */
        if ($_isadmin) {
        } else {
        }
    } else if ($_action == 'proposeevent') {
        /* empty propose-event form */
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
        echo "<div class='header'>Proposed Events</div>";
        echo "<div class='list proposed events'>";
        $proposed = get_open_proposed_events();
        foreach ($proposed as $p) {
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
