<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://skanu/app_dev.php/api/v1/users");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n    \"phone\": \"37060000000\",\n    \"name\": \"Testas testuoklis\",\n    \"email\": \"fake@email.com\",\n    \"password\": \"sj2I/d8DKdjc\"\n}");
$response = curl_exec($ch);
curl_close($ch);

var_dump($response);