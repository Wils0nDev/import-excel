<?php
ini_set('memory_limit', '-1');

use Phppot\DataSource;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();
require_once('./vendor/autoload.php');
$codigoupdate = array();
$codigonoupdate = array();

if (isset($_POST["import"])) {

    $allowedFileType = [
        'application/vnd.ms-excel',
        'text/xls',
        'text/xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    if (in_array($_FILES["file"]["type"], $allowedFileType)) {

        $targetPath = 'uploads/' . $_FILES['file']['name'];
        move_uploaded_file($_FILES['file']['tmp_name'], $targetPath);

        $Reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        $spreadSheet = $Reader->load($targetPath);
        $excelSheet = $spreadSheet->getActiveSheet();
        $spreadSheetAry = $excelSheet->toArray();
        $sheetCount = count($spreadSheetAry);


        for ($i = 0; $i <= $sheetCount; $i++) {
            $id = 0;
            $codigo = "";
            if (isset($spreadSheetAry[$i][0])) {
                $codigo = mysqli_real_escape_string($conn, $spreadSheetAry[$i][0]);
            }
            $cost = "";
            if (isset($spreadSheetAry[$i][3])) {
               
                $cost = mysqli_real_escape_string($conn, $spreadSheetAry[$i][3]);
                $cost = str_replace(' €', '', $cost);
            }

            if (!empty($codigo) || !empty($description)) {
                $queryselect = "SELECT id FROM sma_products WHERE code = '$codigo' ";
                $isset = $db->select($queryselect);
                
                if (!empty($isset[0]['id'])) {
                     $id = $isset[0]['id'];
                     if(empty($cost)){
                        $cost = 0;
                     }

                    $queryupdate = "UPDATE sma_warehouses_products SET avg_cost =  $cost WHERE product_id = '$id' and avg_cost = 0";
                    echo $queryupdate;
                    $insertId = $db->update($queryupdate); 
                } else {
                    array_push($codigonoupdate, array($codigo,$spreadSheetAry[$i][1],$spreadSheetAry[$i][3]));
                }

                if ($insertId == 1 and $id > 0) {
                    array_push($codigoupdate, $id);
                    
                    $type = "success";
                    $message = "Registros actualizdo";
                }
            }
        }
    } else {
        $type = "error";
        $message = "Seleccione un archivo";
    }

}
?>

<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial;
            width: 550px;
        }

        .outer-container {
            background: #F0F0F0;
            border: #e0dfdf 1px solid;
            padding: 40px 20px;
            border-radius: 2px;
        }

        .btn-submit {
            background: #333;
            border: #1d1d1d 1px solid;
            border-radius: 2px;
            color: #f0f0f0;
            cursor: pointer;
            padding: 5px 20px;
            font-size: 0.9em;
        }

        .tutorial-table {
            margin-top: 40px;
            font-size: 0.8em;
            border-collapse: collapse;
            width: 100%;
        }

        .tutorial-table th {
            background: #f0f0f0;
            border-bottom: 1px solid #dddddd;
            padding: 8px;
            text-align: left;
        }

        .tutorial-table td {
            background: #FFF;
            border-bottom: 1px solid #dddddd;
            padding: 8px;
            text-align: left;
        }

        #response {
            padding: 10px;
            margin-top: 10px;
            border-radius: 2px;
            display: none;
        }

        .success {
            background: #c7efd9;
            border: #bbe2cd 1px solid;
        }

        .error {
            background: #fbcfcf;
            border: #f3c6c7 1px solid;
        }

        div#response.display-block {
            display: block;
        }
    </style>
</head>

<body>
    <h2>Import Excel File into MySQL Database using PHP</h2>

    <div class="outer-container">
        <form action="" method="post" name="frmExcelImport" id="frmExcelImport" enctype="multipart/form-data">
            <div>
                <label>Choose Excel File</label> <input type="file" name="file" id="file" accept=".xls,.xlsx">
                <button type="submit" id="submit" name="import" class="btn-submit">Import</button>

            </div>

        </form>

    </div>
    <h3>Productos actualizados</h3>
    <div id="response" class="<?php if (!empty($type)) {
                                    echo $type . " display-block";
                                } ?>"><?php if (!empty($message)) {
                                                                                                    echo $message;
                                                                                                } ?></div>

    <table class='tutorial-table'>
        <thead>
            <tr>
                <th>Id poducto</th>
                <th>Costo</th>

            </tr>
        </thead>


        <tbody>
            <?php
            if (count($codigoupdate) > 0) {
                for ($i = 0; $i < count($codigoupdate); $i++) {
                    $sqlSelect = "SELECT product_id, avg_cost FROM sma_warehouses_products WHERE product_id = '$codigoupdate[$i]'";
                    $result = $db->select($sqlSelect);
                    if (!empty($result)) {
                        foreach ($result as $row) { // ($row = mysqli_fetch_array($result))

            ?>

                            <tr>
                                <td><?php echo $row['product_id']; ?></td>
                                <td><?php echo $row['avg_cost']; ?></td>
                            </tr>
            <?php
                        }
                    }
                }
            }
            ?>
        </tbody>
    </table>

    <h3>Productos no encontrados</h3>

    <table class='tutorial-table'>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Nombre</th>
                <th>Costo</th>

            </tr>
        </thead>


        <tbody>
            <?php
            
            if (count($codigonoupdate) > 0) {
                for ($i = 0; $i < count($codigonoupdate); $i++) {
                    
                    //$sqlSelect = "SELECT code,name,cost FROM sma_products WHERE code = '$codigoupdate[$i]' ";
                    //$result = $db->select($sqlSelect);
                    //if (!empty($result)) {
                        //foreach ($result as $row) { // ($row = mysqli_fetch_array($result))

            ?>

                            <tr>
                                <td><?php echo $codigonoupdate[$i][0]; ?></td>
                                <td><?php echo $codigonoupdate[$i][1]; ?></td>
                                <td><?php echo $codigonoupdate[$i][2]; ?></td>
                            </tr>
            <?php
                        
                    //}
                }
            }
            ?>
        </tbody>
    </table>


</body>

</html>