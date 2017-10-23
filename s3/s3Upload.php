<?php
require_once("../root/functions.php");
include_once "../root/AwsFactory.php";
require_once("../root/ConnectionManager.php");

$aws = new AwsFactory();

$bucket = isset($_POST["bucket"]) ? htmlspecialchars($_POST["bucket"], ENT_QUOTES) : null;
$prefix = isset($_POST["prefix"]) ? htmlspecialchars($_POST["prefix"], ENT_QUOTES) : "";
$desc = isset($_POST["objectdesc"]) ? htmlspecialchars($_POST["objectdesc"], ENT_QUOTES) : "-";

$file_errors = array(
    0=>"Upload Success",
    1=>"The uploaded file(s) exceeds the MAX_FILE_SIZE",
    2=>"The uploaded file(s) exceeds the MAX_POST_SIZE",
 

if(!is_null($bucket)){
    try {
        $s3 = $aws->getS3Client();
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["objectname"]) ){
            foreach ($_FILES['objectname']['tmp_name'] as $key => $tmp_name) {

                $filename = $_FILES['objectname']['name'][$key];
                $tmpfile = $_FILES['objectname']['tmp_name'][$key];
                $dbupdate = "insert";

                if ($s3->doesObjectExist($bucket, $prefix . $filename)) {
                    $dbupdate = "update";
                } else {
                    $dbupdate = "insert";
                }
                if ($_FILES['objectname']['error'][$key] == UPLOAD_ERR_OK && is_uploaded_file($tmpfile)) {
                    try {
                        $result = $s3->putObject([
                            'Bucket' => $bucket,
                            'Key' => $prefix . $filename,
                            'SourceFile' => $tmpfile,
                            'ServerSideEncryption' => 'AES256'
                        ]);
                        if ($result["ObjectURL"]) {
                            print '<p class="text-success">' . $filename . ' upload successful</a></p>';
                            $connectionManager = new ConnectionManager();
                            $mysqlConnector = $connectionManager->getMysqlConnector();

                            if($dbupdate == "insert") {
                                $query = "INSERT INTO " . _RDS_DATABASE . ".objects VALUES 
                                ('" . $bucket . "','" . $prefix . $filename . "','" . $_SESSION["DatalakeUser"] . "',
                                '" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','".$desc."')";
                                $result = $mysqlConnector->exec($query);
                            } else {
                                $query = "UPDATE " . _RDS_DATABASE . ".objects SET updated = '".date('Y-m-d H:i:s')."', 
                                 description = '".$desc."' 
                                WHERE bucket='".$bucket."' AND object='".$prefix . $filename."'";
                                $result = $mysqlConnector->exec($query);
                            }
                        }
                    } catch (\Aws\S3\Exception\S3Exception $ex) {
                        print '<p class="text-danger">' . $filename . ' upload failed. ' . $ex->getAwsErrorCode() . '</p>';
                    } catch (Exception $ex) {
                        print '<p class="text-danger">' . $filename . ' upload failed. ' . $ex->getMessage() . '</p>';
                    }
                } else {
                    print '<p class="text-danger">' . $filename . ' upload failed to. ' . $file_errors[$_FILES['objectname']['error'][$key]] . '</p>';
                }
            }
        }

    } catch (\Aws\S3\Exception\S3Exception $ex){
        print '<p class="text-danger">Object upload failed. '.$ex->getAwsErrorCode().'</p>';
    } catch (Exception $ex){
        print '<p class="alert alert-danger">Object Upload failed. '.$ex->getMessage().'</p>';
    }
} else {
    print '<div class="alert alert-danger">Bucket name cannot be null</div>';
}


?>
