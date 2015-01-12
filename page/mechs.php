<?php
require_once('header.php');
page_header('Mech Warfare Registration -- Mechs');
?>
<div class='content'>
<?php
if ($mechs_error) {
    echo "<div class='error'>".htmlquote($mechs_error)."</div>";
}
if ($_action && $_mechid) {
    $_mech = get_mech_by_id($_mechid);
    if (!$_mech) {
        errors_fatal("There is no mech with id $_mechid");
    }
    echo "<div class='heading'>Edit Mech</div>";
    echo "<form class='editmech' method='post'>";
    echo get_csrf_input();
    echo "<input type='hidden' name='mechid' value='".htmlquote($_mechid)."'/>";
    echo "<div class='formfield'><span class='label'>Name:</span><span class='field'><input type='text' name='name' value='".
        htmlquote($_mech['name'])."'></span></div>";
    echo "<div class='formfield'><span class='label'>URL:</span><span class='field'><input type='text' name='url' value='".
        htmlquote($_mech['url'])."'></span></div>";
    echo "<div class='mechteam'>".htmlquote($_mech['teamname'])."</div>";
    echo "<div class='formfield'><button type='submit' name='action' value='editmech'>Edit Mech</button></div>";
    echo "</form>";
    echo "</div>";
} else {
    if ($_mechid) {
        $_mech = get_mech_by_id($_mechid);
        if (!$_mech) {
            errors_fatal("There is no mech with id $_mechid");
        }
        echo "<div class='mech'>";
        echo "<div class='mechname'>".htmlquote($_mech['name'])."</div>";
        echo "<div class='mechbuilder'>".htmlquote($_mech['buildername'])."</div>";
        echo "<div class='mechteam'>".htmlquote($_mech['teamname'])."</div>";
        echo "<div class='mechurl'>".htmlquote($_mech['url'])."</div>";
        echo "</div>";
        echo "<div class='mechevents'>";
        echo "<div class='heading'>Mech Events</div>";
        $ev = get_events_for_mech($_mechid);
        foreach ($ev as $e) {
            echo "<div class='mechevent'>";
            echo "<span class='col eventname'>".htmlquote($e['eventname'])."</span>";
            echo "<span class='col eventtime'>".htmlquote($e['eventtime'])."</span>";
            echo "<span class='col regusername'>".htmlquote($e['regusername'])."</span>";
            echo "<span class='col regtime'>".htmlquote($e['regtime'])."</span>";
            echo "</div>";
        }
        echo "</div>";
    }
    else {
        $allmechs = get_all_mechs();
        echo "<div class='allmechs'>";
        foreach ($allmechs as $mech) {
            echo "<div class='mechrow'>";
            echo "<div class='name'>".htmlquote($mech['name'])."</div>";
            echo "<div class='team'>".htmlquote($mech['teamname'])."</div>";
            echo "</div>";
        }
        echo "</div>";
    }
    if ($user) {
        $usermechs = get_mechs_by_userid($user['userid']);
        if (!$usermechs || !count($usermechs)) {
            echo "<div class='info'>You have registered no mechs yet.</div>";
        } else {
            echo "<div class='yourmechs'><div class='heading'>Your Mechs</div>";
            foreach ($usermechs as $mech) {
                echo "<div class='mechrow'>";
                echo "<div class='name'>".htmlquote($mech['name'])."</div>";
                echo "<div class='team'>".htmlquote($mech['teamname'])."</div>";
                echo "</div>";
            }
            echo "</div>";
        }
?><form method='post'>
<div class='formfield'><button type='submit' name='action' value='newmech'>New Mech</button></div>
<?php
       echo get_csrf_input(); 
       echo "<input type='hidden' name='name' value='".htmlquote("$user[name]'s Mech")."'/>";
       echo "<input type='hidden' name='url' value=''/>";
       echo "<input type='hidden' name='mechid' value='new'/>";
?>
<div class='info'>You may submit up to three mechs, that can then be assigned to teams, 
that can then apply for events.</div>
</form><?php
    }
}
?>
</div>
<div class='footer'>
</div>
</body>
