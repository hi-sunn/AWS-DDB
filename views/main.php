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


// get table list

$i = 0;
$tablenameArray = [];
$lastEvaluatedTableName = ['', 'dev-gg_joya', 'gcove_enso-woods', 'skyparkRegistration'];

while ($i < count($lastEvaluatedTableName)) {
    if ($lastEvaluatedTableName[$i] == '') {
        $tablelist = $sdk->listTables();
        foreach ($tablelist['TableNames'] as $value) {
            array_push($tablenameArray, $value);
        }
    } else {
        $tablelist = $sdk->listTables([
            'ExclusiveStartTableName' => $lastEvaluatedTableName[$i]
        ]);
        foreach ($tablelist['TableNames'] as $value) {
            array_push($tablenameArray, $value);
        }
    }
    $i++;
}

// scan @ get 1 or more item(s) in a table
try {
    $service = $sdk->scan([
        'TableName' => $db
    ]);
} catch (Exception $e) {
    echo "Oops, it seems that the table is not exist...";
}

$serviceArray = [];
$tableDataArray = [];
$tablecount = 0;

// get highest column count
$maxcolumnlength = 0;
foreach ($service['Items'] as $value) {
    if ($maxcolumnlength == 0) {
        $maxcolumnlength = count($value);
        $referenced_key = array_keys($value);
    }

    if (count($value) > $maxcolumnlength) {
        $maxcolumnlength = count($value);
        $referenced_key = array_keys($value);
    }
}

// Convert db items into json
foreach ($service['Items'] as $value) {
    $columnlength = count($value);
    if ($columnlength < $maxcolumnlength) {
        $tablekey = $marshal->unmarshalItem($value);
        $columnkeys =  array_keys($value);
        $missing_key = array_values(array_diff($referenced_key, $columnkeys));
        $keycount = count($tablekey);
        $S["S"] = "";
        $value[$missing_key[0]] = $S;
        $tablevalue = $marshal->unmarshalJson($value);  
        array_push($serviceArray, json_decode($tablevalue));
    } else {
        $tablevalue = $marshal->unmarshalJson($value);
        $tablekey = $marshal->unmarshalItem($value);
        if ($tablecount < 1) {
            array_push($tableDataArray, array_keys($tablekey));
            $tablecount++;
        }
        array_push($serviceArray, json_decode($tablevalue));
    }
}

// Convert db key into json
$tableDataColumn = [];
foreach ($tableDataArray[0] as $tablecolumn) {
    $tableColumn = "{" . "\"data\":" . "\"" . $tablecolumn . "\"" . "}";
    array_push($tableDataColumn, json_decode($tableColumn));
}
$json_tablekey = json_encode($tableDataColumn);
$json_service =  json_encode($serviceArray);
// echo "<pre>".$json_service."</pre>";

// Error message for empty table
$error = '';
if (empty($tableDataArray[0])) {
    $error = "The Table does not exist !";
    header("Location: http://localhost/Aws-DDB/?db=257PropertyProsperity&error=" . $error);
}


// Count table amount
$table_count = count($tablenameArray);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Default UI src -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="./node_modules//datatables.net-dt/css/jquery.dataTables.min.css" />
    <script src="./node_modules/datatables.net/js/jquery.dataTables.min.js"></script>
    <!-- Jquery UI src -->
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js" integrity="sha256-VSu9DD6vJurraXgjxQJv9BuzedGfJm7XEgPQQehKBlw=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.jqueryui.min.js" integrity="sha256-39KLqmdnivnZ0OGcx+5CoAcwdauI0bdEDcFhdVTNit0=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" integrity="sha256-yMIVeRjJ/tC7ncxWyWtS3Hr3CwXKAijkZ+r5F3d1Gtc=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.jqueryui.min.css" integrity="sha256-1mZHAtpOaAWOWfEqCa7ezIHD2LA1YsUM28154dMqXWA=" crossorigin="anonymous"> -->
    <!-- Export dependency -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js" integrity="sha256-dJiW4V/uPOIBxZUw2TwTxw1eSCqwzUDZIo2jDFyKBLw=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js" integrity="sha256-RbP/rbx4XeYJH6eYUniR63Jk5NEV48Gjestg49cNSWY=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js" integrity="sha256-Xon5hF/CqTXIN9zXCJpZrwnN6P/b8YZt//YhFS/HRpA=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js" integrity="sha256-UsYCHdwExTu9cZB+QgcOkNzUCTweXr5cNfRlAAtIlPY=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js" integrity="sha256-Ovv7z/mozqT8l4fJSUUSCC8n3e7iAXlWXHj8FLuoG58=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" integrity="sha256-9KeRjUewuF4eDFbdumgTsAXcQ154a85x0wgZFFwgS9g=" crossorigin="anonymous">
    <!-- Search builder dependency -->
    <script src="https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js" integrity="sha256-JRRv5UZEd/vpVB1Q0EAEnASffb5O2oWdcNWXz9g5NNg=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/datetime/1.4.1/js/dataTables.dateTime.min.js" integrity="sha256-+K//LRFhDW/cq6jmm/qL9NqbK04P7oeGrWJZDB/MtKE=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.dataTables.min.css" integrity="sha256-Tnx4Ws7wTGvtj+BujEcplfmtO1VxagBRvB2qSp/Xyxo=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.4.1/css/dataTables.dateTime.min.css" integrity="sha256-6YeYhBx/LkYlekMIRM1+fXzFaRCQOLKms/lrFcO6tfI=" crossorigin="anonymous">
    <!-- Custom Styling -->
    <link rel="stylesheet" href="./public/css/styles.css">
    <script>
        var data = <?= $json_service ?>;
        var tableKey = <?= $json_tablekey ?>;
        console.log(data)
        console.log(tableKey);

        $(document).ready(function() {
            let tableData = $("#myTable").DataTable({
                data: data,
                // data: [{
                //     "created_at": "2022-12-20 10:15:08",
                //     "utm_sources": "{\"utm_source\":\"google\",\"utm_medium\":\"cpc\",\"utm_campaign\":\"designerhomes_SEM\",\"utm_term\":\" \"}",
                //     "project": "Aria, Luxura",
                //     "uuid": "205613b0-800c-11ed-aea6-b2f00926e861",
                //     "source": "SearchOP1",
                //     "email": "ecam2462@gmail.com",
                //     "phone": "01111404616",
                //     "name": "Mohamad hishamudin komoh"
                // }, {
                //     "created_at": "2022-10-20 10:36:00",
                //     "project": "Duet, Lucent Residence",
                //     "uuid": "eee79286-501f-11ed-a3d6-362af6ac807e",
                //     "source": "Twentyfive.7 257 Designer Collection Microsite 2022",
                //     "email": "testnex2@gmail.com",
                //     "phone": "0118977777",
                //     "name": "NexTest2"
                // }],
                paging: true,
                scrollY: 550,
                columns: tableKey,
                dom: 'Bfrtpi',
                buttons: [
                    'excelHtml5',
                    'csvHtml5'
                ],

            });
            new $.fn.dataTable.SearchBuilder(tableData, {});
            tableData.searchBuilder.container().prependTo(tableData.table().container());
        });
        // display table name
        $(document).ready(function() {
            $("#tableName").DataTable({
                data: [],
                paging: false,
                scrollY: 500,
                columns: [1]
            });
        });
        // search table name
        $(document).ready(function() {
            $("#myInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $(".tableName tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
        // Display table name
        // $(document).ready(function() {
        //     let tri = $(".dtsb-searchBuilder").find(".dtsb-title").html("<h2 class='font-tablename'> <?= $db ?> </h2>");
        //     let tri2 = $(".dtsb-searchBuilder").find(".dtsb-title").html();
        //     let add_condition_btn = $(".dtsb-group").find(".dtsb-button");

        //     $(add_condition_btn).click(function() {
        //         $(".dtsb-searchBuilder").find(".dtsb-title").html("<h2 class='font-tablename'> <?= $db ?> </h2>");
        //         let exit_btn = $(".dtsb-group").find(".dtsb-delete");

        //         $(exit_btn).click(function() {
        //             $(".dtsb-searchBuilder").find(".dtsb-title").html("<h2 class='font-tablename'> <?= $db ?> </h2>");
        //             // console.log("inside");
        //         });

        //         // console.log(exit_btn.html());


        //     });


        // });
        console.log(data);
        console.log(tableKey);
    </script>
    <title><?= $db ?></title>
</head>

<body>

    <?php
    if (isset($_GET['error'])) {
        echo "<script>alert('" . $_GET['error'] . "')</script>";
        echo "<script>location.href= 'http://localhost/Aws-DDB/' </script>";
    }
    ?>
    <!-- table indicator -->
    <!-- <span class="font-h1"><?= $db ?> Table</span> -->
    <div class="grid">
        <div style="padding: 1px; border-radius:4px;" class="scroll-overflow-y tablelist-container">
            <input id="myInput" type="text" style="width:97%;height:25px;" placeholder="Search..">
            <table class="grayborder">
                <tbody class="tableName">
                    <tr style="background-color:#FAFAFA; height:50px;">
                        <td><b>Tables</b> <span>(<?= $table_count ?>)</span> </td>
                    </tr>
                    <?php foreach ($tablenameArray as $table) : ?>
                        <tr>
                            <td id="tablelist"><a id="<?= $table ?>" class="table-anchor" href="?db=<?= $table ?>"><?= $table ?></a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
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
</body>

</html>
<script>
    $(document).ready(function() {
        $("#<?= $db ?>").css('background-color', 'darkgrey', );
        $("#<?= $db ?>").css('border-radius', '15px');
        $("#<?= $db ?>").css('opacity', '1');
        $("#<?= $db ?>").focus();
    });
</script>