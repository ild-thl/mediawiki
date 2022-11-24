<?php
# LOOP User/Rights statistics. Previously statistik.php

require '../includes/WebStart.php';
require_once "../LoopSettings.php";
require_once "functions_lib.php";

echo '<html><head><link rel="stylesheet" type="text/css" href="statistik.css"></head><body>';

global $IP;

$db = connect_db ( $wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname );
if ( $db == false ) {
    die('Could not connect: ' . mysql_error());
}

$groups = array();
$query="select distinct(ug_group) from user_groups";
$result = $db->query( $query, __METHOD__ );
foreach ( $result as $row ) {
    $groups[] = $row->ug_group;
} 

echo '<table>';

echo '<tr>';
echo '<th>Gruppe</th><th>*</th><th>user</th>';
foreach ($groups as $group) {
	echo '<th>'.$group.'</th>';
}
echo '</tr>';

echo '<tr><th>Rechte</th>';
echo '<td>';
print_grouprights($wgGroupPermissions['*']);
echo '</td>';
echo '<td>';
print_grouprights($wgGroupPermissions['user']);
echo '</td>';
foreach ($groups as $group) {
    echo '<td>';
    if ( array_key_exists( $group, $wgGroupPermissions ) ) {
        print_grouprights($wgGroupPermissions[$group]);
    } else {
        echo "-";
    }
	
	echo '</td>';
}
echo '</tr>'; 

echo '<tr><th>Anzahl User</th>';
echo '<td> - </td>';
echo '<td>';
$query="select count(user_id) from user";
$result = $db->query( $query, __METHOD__ );
foreach ( $result as $row => $val ) {
    foreach ( $val as $data ) {
        echo $data;
        break;
    }
    break;
} 
 
echo '</td>';
foreach ($groups as $group) {
	echo '<td>';
    $query="select count(ug_user) from user_groups where ug_group='".$group."'";
    $result = $db->query( $query, __METHOD__ );
    foreach ( $result as $row => $val ) {
        foreach ( $val as $data ) {
            echo $data;
            break;
        }
        break;
    } 
	echo '</td>';
}
echo '</tr>'; 

echo '<tr><th>User</th>';
echo '<td> - </td>';
echo '<td>';
	$query2 = 'select user_name from user';
    $result2 = $db->query( $query2, __METHOD__ );
    foreach ( $result2 as $row ) {
        $user_name = $row->user_name;
		echo $user_name.'<br>';
        break;
    } 
echo '</td>';

foreach ($groups as $group) {
	echo '<td>';
	$query2 = 'select * from user left join user_groups on user.user_id=user_groups.ug_user where user_groups.ug_group="'.$group.'"';
    $result2 = $db->query( $query2, __METHOD__ );
    foreach ( $result2 as $row ) {
        $user_name = $row->user_name;
		echo $user_name.'<br>';
        break;
    } 

	echo '</td>';
}
echo '</tr>'; 
echo '</table>';
echo '</body></html>';


function print_grouprights($grouprights) {
	echo '<table>';
	foreach ($grouprights as $key=>$value) {
		echo '<tr><td>'.$key.'</td><td>';
		if ($value) {echo 'x';}else{echo '-';}
		echo '</td></tr>';
	}
	echo '</table>';
} 