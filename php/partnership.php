<?php
header("Content-type:text/plain");
include_once("lib/db_mysql.php");
include_once("lib/params.php");
include_once("lib/db_params.php");

define("SECRET", "0TN@Z6E7**1)U'?MH81:[)z|;nj#3N&Ayb@Ql~.4XE+eR$)Dbg-}Omp_f*2iem=" );
define("ZERO_UUID", "00000000-0000-0000-0000-000000000000");

$p = new parameters();

if( $p->pw != SECRET ){
    echo "0|Epic Failure|Very Epic Failure|Truly Epic Failure"; // status code and error message
    exit(0);
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
    }
}
echo "$result|$user1|$user2$extra";
?>
