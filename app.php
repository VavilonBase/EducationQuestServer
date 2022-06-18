<?php

require 'connect.php';

header('Content-type: json/application');

echo 1;

$sql = "SELECT * FROM public.users";
$users = pg_query($connect, $sql);

$usersList = [];

while ($user = pg_fetch_assoc($users)) {
    echo $user;
    $usersList = $user;
}

echo json_encode($usersList);
