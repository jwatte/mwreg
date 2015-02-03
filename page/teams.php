<?php
require_once('header.php');
page_header('Mech Warfare Registration -- Teams');
?>
<div class='content'>
<?php
    if ($teams_error) {
        echo "<div class='error'>".htmlquote($teams_error)."</div>";
    }
    $teamid = @$_GET['id'];
    if ($teamid) {
        $team = get_team_by_id($teamid);
        if (!$team) {
            errors_fatal("There is no team ID $teamid");
        }
        $_ismember = is_team_member($user['userid'], $team);
        if (is_team_admin($user['userid'], $team)) {
            //  edit form
            $_islead = true;
            function emit_field($team, $tag, $label) {
                echo "<div class='formfield'><span class='label'>$label</span><span class='field'><input type='text' name='$tag' value='".htmlquote($team[$tag])."'/></span></div>";
            }
            echo "<form method='post'>";
            echo "<input type='hidden' name='id' value='$team[teamid]'/>";
            echo get_csrf_input();
        } else {
            //  team glory page
            $_islead = false;
            function emit_field($team, $tag, $label) {
                echo "<div class='listfield'><span class='label'>$label</span><span class='value'>".htmlquote($team[$tag])."</span></div>";
            }
        }
        echo "<div class='team'>";
        echo "<div class='heading'>Team</div>";
        echo "<div class='teamid'><span class='label'>ID</span><span class='value'>".htmlquote($team['teamid'])."</span></div>";
        emit_field($team, 'name', 'Team Name');
        emit_field($team, 'url', 'Team URL');
        echo "<div class='teamleader'><span class='label'>Leader</span><span class='value'>".htmlquote($team['leadername'])."</span></div>";
        echo "</div>";
        if ($_islead) {
            echo "<div class='formfield'><span class='label'>&nbsp;</span><span class='value'><button name='action' value='editteam'>Update Team</button></span></div>";
            echo "</form>";
        }
        if ($team_ok) {
            echo "<div class='result'>".htmlquote($team_ok)."</div>";
        }
        echo "<div class='list mechs'>";
        echo "<div class='heading'>Team Mechs</div>";
        foreach ($team['mechs'] as $mech) {
            echo "<div class='item mech'>";
            echo "<div class='mechid'>".htmlquote($mech['mechid'])."</div>";
            echo "<div class='mechname'>".htmlquote($mech['name'])."</div>";
            echo "<div class='mechurl'>".htmlquote($mech['url'])."</div>";
            echo "<div class='mechbuilder'>".htmlquote($mech['builder'])."</div>";
            if ($_islead || $mech['builder'] == $user['userid']) {
                echo "<form method='post' class='action remove'>".get_csrf_input()."<input type='hidden' name='mechid' value='$mech[mechid]'/>";
                echo "<button name='action' value='removemech'>Remove</button></form>";
            }
            echo "</div>";
        }
        if ($_ismember) {
            $mechs = db_query("SELECT m.mechid AS mechid, m.name AS name, m.builder AS builder, m.team AS team, m.url AS URL, t.name AS teamname FROM mechs m LEFT OUTER JOIN teams t ON m.team = t.teamid WHERE builder=:userid",
                    array('userid'=>$user['userid']));
            if ($mechs) {
                echo "<form method='post'>";
                echo get_csrf_input();
                echo "<div class='formfield'><span class='label'>Add Mech:</span><span class='value'>";
                echo "<select name='mechid'>";
                foreach ($mechs as $m) {
                    $str = $m['name'];
                    if ($m['team']) {
                        $str .= " (On team $m[teamname])";
                    }
                    echo "<option value='$m[mechid]'>".htmlquote($str)."</option>";
                }
                echo "</select>";
                echo "</span></div>";
                echo "<div class='formfield'><span class='label'>&nbsp;</span><span class='value'>";
                echo "<button name='action' value='addmech'>Add to Team</button></span></div>";
                echo "</form>";
            }
        }
        echo "</div>";
        echo "<div class='list members'>";
        echo "<div class='heading'>Team Members</div>";
        $_isadmin = false;
        $_applicant = false;
        foreach ($team['members'] as $member) {
            echo "<div class='item team'>";
            echo "<div class='userid'>".htmlquote($member['userid'])."</div>";
            echo "<div class='username'>".htmlquote($member['name'])."</div>";
            echo "<div class='membersince'>".htmlquote($member['membersince'])."</div>";
            echo "<div class='teamadmin'>".($member['teamadmin'] ? "admin" : "member")."</div>";
            if ($team['leader'] === $member['userid']) {
                echo "<div class='teamleader'>(Leader)</div>";
            } else if ($_islead) {
                echo "<form method='post' class='action remove'>".get_csrf_input()."<input type='hidden' name='userid' value='$member[userid]'/>";
                echo "<button name='action' value='removemember'>Remove</button></form>";
            }
            echo "</div>";
            if ($member['userid'] === $user['userid']) {
                $_ismember = true;
                if ($member['teamadmin']) {
                    $_isadmin = true;
                }
            }
        }
        foreach ($team['applicants'] as $a) {
            if ($a['userid'] == $user['userid']) {
                $_applicant = true;
                break;
            }
        }
        if (!$_ismember && !$_applicant) {
            echo "<form method='post'>";
            echo get_csrf_input();
            echo "<div class='formfield'><span class='label'>&nbsp;</span><span class='value'>";
            echo "<button name='action' value='apply'>Apply for Membership</button></span></div>";
            echo "<input type='hidden' name='id' value='".htmlquote($team['teamid'])."'/>";
            echo "</form>";
        } else {
            $_kind = 'an applicant';
            if ($_ismember) {
                $_kind = 'a member';
            }
            if ($_islead) {
                $kind = 'an administrator';
            }
            if ($team['leader'] === $user['userid']) {
                $_kind = 'a leader';
            }
            echo "<div class='text'>You are $_kind of this team.</div>";
        }
        echo "</div>";
        if ($_islead) {
            if ($team['applicants']) {
                echo "<div class='heading'>Applicants</div>";
                echo "<div class='list applicants'>";
                foreach ($team['applicants'] as $member) {
                    echo "<div class='item teammember'>";
                    echo "<div class='userid'>".htmlquote($member['userid'])."</div>";
                    echo "<div class='username'>".htmlquote($member['name'])."</div>";
                    echo "<div class='membersince'>".htmlquote($member['membersince'])."</div>";
                    echo "<div class='actions'>";
                    formbtn("/teams.php",
                        array('id'=>$teamid, 'user'=>$member['userid'], 'action'=>'approve'),
                        "Approve");
                    formbtn("/teams.php",
                        array('id'=>$teamid, 'user'=>$member['userid'], 'action'=>'reject'),
                        "Reject");
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div>";
            }
        }
    } else if ($user) {
        echo "<div class='teamlist'>";
        echo "<div class='heading'>Registered Teams</div>";
        $offset = (int)@$_GET['offset'];
        if ($offset <= 0) {
            $offset = 0;
        }
        if ($offset > 1000000) {
            $offset = 0;
        }
        $limit = 50;
        $teams = db_query("SELECT t.teamid as teamid, t.name as teamname, u.name as username FROM teams t, users u WHERE t.leader = u.userid " .
            "LIMIT $limit OFFSET $offset", array());
        if ($teams) {
            foreach ($teams as $t) {
                echo "<div class='team item'>";
                echo "<span class='teamid'>".htmlquote($t['teamid'])."</span>";
                echo "<span class='teamname'>".htmlquote($t['teamname'])."</span>";
                echo "<span class='username'>".htmlquote($t['username'])."</span>";
                echo "<span class='details'><a href='/mwreg/teams.php?id=".urlencode($t['teamid'])."'>Details</a></span>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>No teams found.</div>";
        }
        echo "</div>";
        //  todo: paginate
        $q = db_query("SELECT COUNT(1) AS count FROM teams", array());
        $cnt = $q[0]['count'];
        if ($cnt > $limit) {
            echo "<div class='pagination'>";
            for ($i = 0; $i < $cnt; $i++) {
                $cur = "";
                if ($i * $limit <= $offset && ($i+1) * $limit > $offset) {
                    $cur = " current";
                }
                echo "<span class='page$cur'><a href='/mwreg/teams.php?offset=".($i * $limit)."'>".($i+1)."</a></span>";
            }
        }
        //  allow creation of one team, or editing the team you have
        $c = db_query("SELECT COUNT(1) AS count FROM teams WHERE leader=:userid", array('userid'=>$user['userid']));
        if ($c && !$c[0]['count']) {
?>
<form method='post'>
<div class='formfield'><button type='submit' name='action' value='newteam'>New Team</button></div>
<?php
       echo get_csrf_input(); 
?>
<div class='info'>You may be leader of only one team. You may be member or administrator 
of any number of teams that invite you.</div>
</form>
<?php
        } else {
            $t = get_teams_by_leader($user['userid']);
            echo "<div class='info'>You are the leader for team '".htmlquote($t[0]['name'])."'. ";
            echo "<span class='action teamedit'><a href='/mwreg/teams.php?id=".urlencode($t[0]['teamid'])."'>Edit</a></span></div>";
        }
    } else {
        echo "<div class='error'>You must be logged in to view team listings.</div>";
    }
?>
</div>
<div class='footer'>
</div>
</body>
