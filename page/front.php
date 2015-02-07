<?php
require_once 'header.php';
page_header('Mech Warfare Registration');
?>
<div class='content'>
<?php
if ($user) {
?>
<div class='userlinks'>
<span class='label'>Manage:</span>
<span class='action profile'><a href='<?php echo $ROOTPATH; ?>/profile.php'>Profile</a></span>
<span class='action teams'><a href='<?php echo $ROOTPATH; ?>/teams.php'>Teams</a></span>
<span class='action mechs'><a href='<?php echo $ROOTPATH; ?>/mechs.php'>Mechs</a></span>
<span class='action events'><a href='<?php echo $ROOTPATH; ?>/events.php'>Events</a></span>
</div>
<?php
}
?>
<div class='heading'>Upcoming Events</div>
<div class='upcoming list'>
<?php
    $_events = db_query('SELECT * FROM events WHERE endtime > NOW() AND published > 0 ORDER BY endtime ASC LIMIT 10', array());
    if (!$_events) {
        echo "<div class='nodata'>No currently scheduled upcoming events.</div>";
    } else {
        foreach ($_events as $k => $e) {
            echo "<div class='event item'>";
            foreach (array('name', 'location', 'starttime', 'endtime', 'url') as $x => $i) {
                if ($x == 'name') {
                    echo "<a href='events.php?id=".htmlquote($e['eventid'])."'>";
                }
                echo "<span class='$i'>".htmlquote($e[$i])."</span>";
                if ($x == 'name') {
                    echo "</a>";
                }
            }
            echo "<span class='action details'><a href='$ROOTPATH/events.php?id=$e[eventid]'>See More</a></span>";
            echo "</div>";
        }
    }
?>
</div>
<div class='heading'>Previous Events</div>
<div class='previous list'>
<?php
    $_events = db_query('SELECT * FROM events WHERE endtime < NOW() ORDER BY endtime AND published > 0 DESC LIMIT 10', array());
    if (!$_events) {
        echo "<div class='nodata'>No previous events yet.</div>";
    } else {
        foreach ($_events as $k => $e) {
            echo "<div class='event item'>";
            foreach (array('name', 'location', 'starttime', 'endtime', 'url') as $x => $i) {
                if ($x == 'name') {
                    echo "<a href='events.php?id=".htmlquote($e['eventid'])."'>";
                }
                echo "<span class='$i'>".htmlquote($e[$i])."</span>";
                if ($x == 'name') {
                    echo "</a>";
                }
            }
            echo "</div>";
        }
    }
?>
</div>
</div>
<div class='footer'></div>
</body>
