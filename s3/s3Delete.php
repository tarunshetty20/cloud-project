<?php
include_once "../root/AwsFactory.php";
require_once("../root/ConnectionManager.php");

$aws = new AwsFactory();

$key = isset($_POST["object-name"]) ? htmlspecialchars($_POST["object-name"], ENT_QUOTES) : null;

if(!is_null($key)) {
    $s3 = $aws->getS3Client();
    $parts = explode("/", $key, 2);
    $bucket = $parts[0];
    $object = $parts[1];

    try {
        $result = $s3->deleteMatchingObjects($bucket, $object);
        print '<p class="text-success">' . $object . ' deleted</a></p>';

        $connectionManager = new ConnectionManager();
        $mysqlConnector = $connectionManager->getMysqlConnector();

        $query = "DELETE FROM "._RDS_DATABASE.".objects WHERE bucket = '".$bucket."' AND object = '".$object."'";
        $result = $mysqlConnector->exec($query);


    } catch (\Aws\S3\Exception\S3Exception $ex) {
        print '<p class="text-danger">' . $key . ' deleted failed. ' . $ex->getAwsErrorCode() . '</p>';
    } catch (Exception $ex) {
        print '<p class="text-danger">' . $key . ' deleted failed. ' . $ex->getMessage() . '</p>';
    }
} else {
    print '<div class="alert alert-danger">Cannot delete the object</div>';
}



?>