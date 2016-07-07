<?php

$serverName = "213.197.176.247, 5566"; //serverName\instanceName, portNumber (default is 1433)
$connectionInfo = array( "Database"=>"prototipas6", "UID"=>"fo_order", "PWD"=>"peH=waGe?zoOs69");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true));
}

$formArray = array(
    ''
);

/*
$rez = sqlsrv_query ( $conn , "SELECT * FROM sys.Tables");

if( $rez === false) {
    die( print_r( sqlsrv_errors(), true) );
}

$rezList = sqlsrv_fetch_array($rez, SQLSRV_FETCH_ASSOC);
echo "<pre>";
var_dump($rezList);
$rezList = sqlsrv_fetch_array($rez, SQLSRV_FETCH_ASSOC);
echo "<pre>";
var_dump($rezList);
*/

$rez = sqlsrv_query ( $conn , 'SELECT *  FROM [PROTOTIPAS Skambuciu Centras$Web Order Header] ORDER BY timestamp DESC');

if( $rez === false) {
    die( print_r( sqlsrv_errors(), true) );
}

$rezList = sqlsrv_fetch_array($rez, SQLSRV_FETCH_ASSOC);
echo "<pre>";
var_dump($rezList);

/**
 *  'timestamp' => '',
'Order No_' => '',
'Phone' => '',
'ZipCode' => '',
'City' => '',
'Street' => '',
'Street No_' => '',
'Floor' => '',
'Grid' => '',
'Chain' => '',
'Name' => '',
'Delivery Type' => '',
'Restaurant No_' => '',
'Order Date' => '',
'Order Time' => '',
'Takeout Time' => '',
'Directions' => '',
'Discount Card No_' => '',
'Order Status' => '',
'Delivery Order No_' => '',
'Error Description' => '',
'Flat No_' => '',
'Entrance Code' => '',
'Region Code' => '',
'Delivery Status' => '',
'In Use By User' => '',
'Loyalty Card No_' => '',
'Order with Alcohol' => ''
 */

/**
 * 1)  INSERT INTO [dbo].[PROTOTIPAS Skambuciu Centras$Web Order Header]


29)      VALUES

2)             ([Order No_]30)            (77030

3)             ,[Phone]31)            ,'861234029'

4)             ,[ZipCode]32)            ,'5'

5)             ,[City]33)            ,'Vilnius'

6)             ,[Street]34)            ,'A. BARANAUSKO G.'

7)             ,[Street No_]35)            ,'1'

8)             ,[Floor]36)            ,'5'

9)             ,[Grid]37)            ,'Z-V13'--GRID

10)            ,[Chain]38)            ,'PICA'

11)            ,[Name]39)            ,'FoodOut'

12)            ,[Delivery Type]40)            ,1 --delivery type

13)            ,[Restaurant No_]41)            ,'C27'--restoranas

14)            ,[Order Date]42)            ,'2014-07-30 00:00:00.000'

15)            ,[Order Time]43)            ,'1754-01-01 13:13:31.000'

16)            ,[Takeout Time]44)            ,'2014-07-30 15:40:00.000'

17)            ,[Directions]45)            ,'FoodOut Testas - NEGAMINTI!'

18)            ,[Discount Card No_]46)            ,''

19)            ,[Order Status]47)            ,4 --order status

20)            ,[Delivery Order No_]48)            ,''

21)            ,[Error Description]49)            ,''

22)            ,[Flat No_]50)            ,'56'

23)            ,[Entrance Code]51)            ,'5656'

24)            ,[Region Code]52)            ,'VILNIUS'

25)            ,[Delivery Status]53)            ,12 --delivery status

26)            ,[In Use By User]54)            ,''

27)            ,[Loyalty Card No_]55)            ,''

28)            ,[Order with Alcohol])56)            ,0

57)                      )

58) GO

59)
 */

?>