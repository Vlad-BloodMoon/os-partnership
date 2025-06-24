<?php
header("Content-type:text/plain");
include_once("lib/db_mysql.php");
include_once("lib/params.php");
include_once("lib/db_params.php");

define("SRC_VERSION", "1.0.10");

define("SECRET", "0TN@Z6E7**1)U'?MH81:[)z|;nj#3N&Ayb@Ql~.4XE+eR$)Dbg-}Omp_f*2iem=" );
define("ZERO_UUID", "00000000-0000-0000-0000-000000000000");
define("TIMEZONE", "America/Los Angeles");

$p = new parameters();

if( $p->pw != SECRET ){
    echo "0|Epic Failure|Very Epic Failure|Truly Epic Failure"; // status code and error message
    exit(0);
}

function updateNotes( $uuid, $data ){
    global $db;
    $note = "";

    $r = $db->execute_as_obj( "select `notes` from usernotes where `useruuid`='$uuid' and `targetuuid`='$uuid'");
    if( $r ){
        $note = $r->notes."\n----------\n";
    }

    $note .= $data;
    $sql = "insert into usernotes (`useruuid`, `targetuuid`, `notes`) values ('$uuid', '$uuid', '$note') on duplicate key update `notes`='$note'";
    $db->execute( $sql );

}

$db = new DB_Sql();
setDBParameters( $db ); // Host, Database, Username, Password

$user1 = $p->user1;
$user2 = $p->user2 == "" ? ZERO_UUID : $p->user2;
$result = 0;
$extra = "";

// 'divorce' splits a partnership from both sides
// 'partner' joins two avatars if they both independent
// 'info' fetches partnership status for an individual

if( $p->action == "divorce" || $p->action == "info" ){
    $sql = "select profilePartner from userprofile where useruuid='$user1'";
    if( $r = $db->execute_as_obj( $sql ) ){
        $user2 = $r->profilePartner;
    }
}
if( $p->action == "divorce" && $user2 != ZERO_UUID ){
    $sql = "update userprofile set profilePartner='".ZERO_UUID."'".
            "where useruuid='$user1' or useruuid='$user2'";
    $db->query( $sql );
    $result = $db->affected_rows();
}
else if( $p->action == "partner" ){
    $sql = "select useruuid, profilePartner from userprofile where useruuid='$user1' or useruuid='$user2'";
    $db->query( $sql );
    $ok = 0;
    while( $r = $db->next_rec_as_obj() ){
        if( $r->profilePartner == ZERO_UUID ) $ok++;
        else $extra = "|$r->profilePartner";
    }
    if( $ok == 2 ){
        $db->execute( "update userprofile set profilePartner='$user2' where useruuid='$user1'" );
        $result += $db->affected_rows();
        $db->execute( "update userprofile set profilePartner='$user1' where useruuid='$user2'" );
        $result += $db->affected_rows();

        // get user names
        $db->execute("select FirstName, LastName from UserAccounts where PrincipalID='$user1' or PrincipalID='$user2'");
        $uname1 = $db->f("FirstName")." ".$db->f("LastName");
        $db->next_record();
        $uname2 = $db->f("FirstName")." ".$db->f("LastName");

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(TIMEZONE));
        $date = $date->format("Y-m-d H:i:s");
        $note = "$date $uname1 Partnered with $uname2";
        
        updateNotes( $user1, $note );
        updateNotes( $user2, $note );
    }
}
echo "$result|$p->action|$user1|$user2$extra";
?>
