<?php

$output = <<<HTML
<!DOCTYPE html>
<html class="no-js">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Foodout.lt</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            html, body, #wrapper {
               height:100%;
               width: 100%;
               margin: 0;
               padding: 0;
               border: 0;
            }
            #wrapper td {
               vertical-align: middle;
               text-align: center;
            }
        </style>
    </head>
    <body>
        <table id="wrapper">
            <tr>
                <td><img src="foodout.png" alt="" /></td>
            </tr>
        </table>
    </body>
</html>
HTML;
echo $output;
die;
