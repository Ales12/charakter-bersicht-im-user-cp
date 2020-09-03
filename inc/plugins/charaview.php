<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

//Hooks
$plugins->add_hook("usercp_start", "ucp_charaview");

function charaview_info()
{
    return array(
        "name"			=> "Charakterübersicht",
        "description"	=> "Hier können alle Charaktere eines Users im User CP angezeigt werden.",
        "website"		=> "",
        "author"		=> "Ales",
        "authorsite"	=> "https://tearstellstories.de/theday/member.php?action=profile&uid=1",
        "version"		=> "1.0",
        "compatibility" => "*"
    );
}

function charaview_install()
{
    global $db;
    /*
        * nun kommen die Einstellungen
        */
    $setting_group = array(
        'name' => 'charaview',
        'title' => 'Charakterübersicht',
        'description' => 'Einstellungen für die Charakterübersicht',
        'disporder' => 3,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'name' => 'charaview_active',
        'title' => 'Plugin aktivieren',
        'description' => 'Soll der Plugin aktiv sein?',
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 1,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'charaview_profilfield',
        'title' => 'Profilfelder angeben',
        'description' => 'Welche Profilfelder sollen angegeben werden?',
        'optionscode' => 'text',
        'value' => '1,2,3',
        'disporder' => 1,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);

    rebuild_settings();


}

function charaview_is_installed()
{
    global $mybb;
    if(isset($mybb->settings['charaview_profilfield']))
    {
        return true;
    }

    return false;
}

function charaview_uninstall()
{
    global $db;

    $db->delete_query('settings', "name IN ('charaview_active', 'charaview_profilfield')");
    $db->delete_query('settinggroups', "name = 'charaview'");

// Don't forget this
    rebuild_settings();

}

function charaview_activate()
{


}

function charaview_deactivate()
{

}



//wer ist wo
$plugins->add_hook('fetch_wol_activity_end', 'charaview_user_activity');
$plugins->add_hook('build_friendly_wol_location_end', 'charaview_location_activity');

function charaview_user_activity($user_activity){
    global $user;

    if(my_strpos($user['location'], "usercp.php?action=charaview") !== false) {
        $user_activity['activity'] = "charaview";
    }

    return $user_activity;
}

function charaview_location_activity($plugin_array) {
    global $db, $mybb, $lang;

    if($plugin_array['user_activity']['activity'] == "charaview")
    {
        $plugin_array['location_name'] = "Schaut sich die eigene <b><a href='usercp.php?action=charaview'>Charakterübersicht</a></b> im User CP an.";
    }


    return $plugin_array;
}

function ucp_charaview(){

    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $page, $usercpnav, $db, $chara,  $user_info;

    if($mybb->get_input('action') == 'charaview') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb('Deine Charakterübersicht', "usercp.php?action=charaview");

// suche alle angehangenen accounts
        //welcher user ist online
        $this_user = intval($mybb->user['uid']);

//für den fall nicht mit hauptaccount online
        $as_uid = intval($mybb->user['as_uid']);

// suche alle angehangenen accounts
        if ($as_uid == 0) {
            $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username ASC");
        } else if ($as_uid != 0) {
//id des users holen wo alle angehangen sind
            $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username ASC");
        }

        while ($chara = $db->fetch_array($select)) {
            $chara_avatar = "<img src='{$chara['avatar']}'>";
            $username = format_name($chara['username'], $chara['usergroup'], $chara['displaygroup']);
            $chara_name = build_profile_link($username, $chara['uid']);
            $uid = $chara['uid'];
            $user_info = "";
            $charabirthday = "";
            $age = "";
            $group = $chara['usergroup'];

            $usergroup = $chara['usergroup'];
            //Geburtstag

            $birthday = explode("-", $chara['birthday']);

            if ($chara['birthdayprivacy'] != 'none') {
                if ($birthday[0] && $birthday[1] && $birthday[2]) {

                    $bdayformat = fix_mktime($mybb->settings['dateformat'], $birthday[2]);
                    $charabirthday = mktime(0, 0, 0, $birthday[1], $birthday[0], $birthday[2]);
                    $charabirthday = date($bdayformat, $charabirthday);
                    $age = intval(date('Y', strtotime("1." . $mybb->settings['minica_month'] . "." . $mybb->settings['minica_year'] . "") - strtotime($chara['birthday']))) - 1970;
                    $age = $age . " Jahre";
                }
            }

            $work = "";


            if ($chara['job']) {
                $work = "<div class=\"infos\"><i class=\"fas fa-briefcase\"></i> " . $chara['job'] . "</div>";
            }elseif ($chara['fach']) {
                $work = "<div class=\"infos\"><i class=\"fas fa-university\"></i> " . $chara['fach'] . "</div>";
            }


            $profile = $db->query("SELECT *
            FROM " . TABLE_PREFIX . "userfields uf
            WHERE ufid = '$uid'
            ");

            while ($pf = $db->fetch_array($profile)) {
                $blood = "";
                $height = "";
                $eyes = "";
                $attitude = "";
                $sex = "";
                $relation = "";
                $school = "";
                $membership = "";
                $special = "";
                $charaarea="";


                $blood = $pf['fid34'];
                $height = $pf['fid56'] . "m";
                $eyes = $pf['fid57'];
                $attitude = $pf['fid58'];#
                $sex = $pf['fid55'];
                $relation = $pf['fid52'];
                $charaarea ="<div class=\"infos\"><b><a href='{$pf['fid9']}' target='_blank'>Charakterarea</a></b></div>";

                if($pf['fid36']){
                    $membership = "<div class=\"infos\"><i class=\"far fa-file-alt\" original-title=\"\"></i> {$pf['fid36']}</div>";
                }

                if($pf['fid33']){
                    $special ="<div class=\"infos\"><i class=\"fas fa-star\" original-title=\"\"></i> {$pf['fid33']}</div>";
                }

                if (!empty($pf['fid19'])) {
                    if ($pf['fid19'] != 'keine Angabe') {
                        if (!empty($pf['fid18'])) {
                            $pf['fid18'] = "für {$pf['fid18']}";
                        }
                        $school = "<i class=\"fas fa-school\"></i> {$pf['fid19']} {$pf['fid18']}";
                    }elseif($pf['fid5'] != '' ){
                        $school = "<i class=\"fas fa-school\"></i>  {$pf['fid5']} in der {$pf['fid6']}";
                    }

                }

            }


//Charas aufrufen
            eval("\$charas_bit .= \"" . $templates->get("usercp_charaview_bit") . "\";");
        }
        // Using the misc_help template for the page wrapper
        eval("\$page = \"".$templates->get("usercp_charaview")."\";");
        output_page($page);
    }

}