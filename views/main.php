<?php

require "vendor/autoload.php";

use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

// SDK instance
$sdk = new DynamoDbClient([
    'version' => 'latest',
    'region' => 'ap-southeast-1'
]);


$marshal = new Marshaler();

// for selecting different db
if (isset($_GET['db'])) {
    $db = $_GET['db'];
    if ($_GET['db'] == NULL) {
        $db = '257PropertyProsperity';
    }
} else {
    $db = '257PropertyProsperity';
}

// Get tablelist names. limit at 100 table names per instance
$tablelist_1stBatch = $sdk->listTables([
    'LastEvaluatedTableName' => 'dev-gg_joya'
]);

$tablelist_2ndBatch = $sdk->listTables([
    'ExclusiveStartTableName' => 'dev-gg_joya',
    'LastEvaluatedTableName' => 'gcove_enso-woods'
]);

$tablelist_3rdBatch = $sdk->listTables([
    'ExclusiveStartTableName' => 'gcove_enso-woods',
    'LastEvaluatedTableName' => 'skyparkRegistration'
]);
$tablelist_4thBatch = $sdk->listTables([
    'ExclusiveStartTableName' => 'skyparkRegistration'
]);

// scan @ get 1 or more item(s) in a table
$service = $sdk->scan([
    'TableName' => $db
]);


$serviceArray = [];
$tableDataArray = [];
$tablecount = 0;

// Convert db items into json
foreach ($service['Items'] as $value) {
    $tablevalue = $marshal->unmarshalJson($value);
    $tablekey = $marshal->unmarshalItem($value);
    if ($tablecount < 1) {
        array_push($tableDataArray, array_keys($tablekey));
        $tablecount++;
    }
    array_push($serviceArray, json_decode($tablevalue));
}
// Convert db key into json
$tableDataColumn = [];
foreach ($tableDataArray[0] as $tablecolumn) {
    $tableColumn = "{" . "\"data\":" . "\"" . $tablecolumn . "\"" . "}";
    array_push($tableDataColumn, json_decode($tableColumn));
}
$json_tablekey = json_encode($tableDataColumn);
$json_service =  json_encode($serviceArray);

// Error message for empty table
$error = '';
if (empty($tableDataArray[0])) {
    $error = "The Table is empty";
    header("Location: http://localhost/AwsDDB/?db=257PropertyProsperity&error=" . $error);
}

if (isset($_GET['error'])) {
    echo $error;
}
// Count table amount
$table_count = count($tablelist_1stBatch['TableNames']) + count($tablelist_2ndBatch['TableNames']) + count($tablelist_3rdBatch['TableNames']) + count($tablelist_4thBatch['TableNames']);


// listing table list (using datatable) 
$tablelistArray = [];
foreach ($tablelist_1stBatch['TableNames'] as $tableName) {
    $tablename = "{" . "\"tablename\":" . "\"" . $tableName . "\"" . "}";
    array_push($tablelistArray, json_decode($tablename));
}
$tablelistJson = json_encode($tablelistArray);


// insert table list into single array
$tablenameArray = [];
foreach ($tablelist_1stBatch['TableNames'] as $value) {
    array_push($tablenameArray, $value);
}
foreach ($tablelist_2ndBatch['TableNames'] as $value) {
    array_push($tablenameArray, $value);
}
foreach ($tablelist_3rdBatch['TableNames'] as $value) {
    array_push($tablenameArray, $value);
}
foreach ($tablelist_4thBatch['TableNames'] as $value) {
    array_push($tablenameArray, $value);
}

// echo $_SERVER["DOCUMENT_ROOT"]."/AwsDDB/";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Default UI src -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script> -->
    <!-- <link rel="stylesheet" href="./node_modules//datatables.net-dt/css/jquery.dataTables.min.css" /> -->
    <!-- <script src="./node_modules/datatables.net/js/jquery.dataTables.min.js"></script> -->
    <!-- Jquery UI src -->
    <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js" integrity="sha256-VSu9DD6vJurraXgjxQJv9BuzedGfJm7XEgPQQehKBlw=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.jqueryui.min.js" integrity="sha256-39KLqmdnivnZ0OGcx+5CoAcwdauI0bdEDcFhdVTNit0=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" integrity="sha256-yMIVeRjJ/tC7ncxWyWtS3Hr3CwXKAijkZ+r5F3d1Gtc=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.jqueryui.min.css" integrity="sha256-1mZHAtpOaAWOWfEqCa7ezIHD2LA1YsUM28154dMqXWA=" crossorigin="anonymous">


    <link rel="stylesheet" href="./public/css/styles.css">


    <script>
        var data = <?= $json_service ?>;
        var tableKey = <?= $json_tablekey ?>;
        var tablelist = <?= $tablelistJson ?>;
        console.log(data);
        console.log(tableKey);

        $(document).ready(function() {
            $("#myTable").DataTable({
                data: data,
                paging: true,
                scrollY: 500,
                columns: tableKey,
                stripe: false,
            });
        });
        $(document).ready(function() {
            $("#tableName").DataTable({
                data: [],
                paging: false,
                scrollY: 500,
                columns: [1],
                searching: true
            });
        });
    </script>

    <title>AWS DDB</title>
</head>

<body>

    <div class="grid">

        <div style="padding: 1px; border-radius:4px;" class="scroll-overflow-y tablelist-container">
            <table class="grayborder ">
                <tr style="background-color:#FAFAFA; height:50px;">
                    <td><b>Tables</b> <span>(<?= $table_count ?>)</span></td>
                </tr>
                <tbody>
                    <tr>
                        <td></td>
                    </tr>
                </tbody>

                <?php
                foreach ($tablenameArray as $table) :

                ?>
                    <tr>
                        <td id="tablelist"><a class="table-anchor" href="?db=<?= $table ?>"><?= $table ?></a></td>
                    </tr>
                <?php endforeach ?>


            </table>
        </div>

        <table id="myTable" class="display">
            <thead>
                <tr>
                    <?php
                    foreach ($tableDataArray[0] as $Cvalue) : ?>
                        <th><?= $Cvalue ?></th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>



</html>