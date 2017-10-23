<?php
use Aws\S3\Exception\S3Exception;

    include_once "../root/header.php";
    include_once "../root/AwsFactory.php";
    require_once("../root/ConnectionManager.php");
    checkSession();

    $aws = new AwsFactory();
    $s3Client = $aws->getS3Client();
    $action = (isset($_GET["action"])) ? sanitizeParameter($_GET["action"]) : "listBuckets";
    $bucket = (isset($_GET["bucket"])) ? sanitizeParameter($_GET["bucket"]) : _BUCKET;
    $buckets_list = array(_BUCKET);
    $prefix = '';
    $list_object_error = null;
    $bucket_lifecycle_error = null;
    $objects = null;
    $no_objects = true;

    if(isset($_GET["prefix"])){
        if(isset($_GET["source"])){
            if($_GET["source"]=="tag"){
                $prefix = sanitizeParameter($_GET["prefix"]);
            }
        } else {
            $prefix = sanitizeParameter($_GET["prefix"]) . '/';
        }
    }

    print '<div class="clearfix"></div><br>
    <div class="col-lg-1 col-md-1"></div>
    <div class="col-lg-10 col-md-10 col-sm-12 col-xs-12 contentBody">
    
    <script type="text/javascript" src="../resources/js/jquery.form-4.20.min.js"></script>
    <script type="text/javascript" src="../resources/js/clipboard-1.60.min.js"></script>
    <script type="text/javascript" src="../resources/js/s3Utilities.js"></script>
    ';

    $lifecyclepolicy = "";
    try{
        $result = $s3Client->getBucketLifecycleConfiguration([
            'Bucket' => $bucket
        ]);

        $lifecyclepolicy = '<b>Id</b> : '.$result["Rules"][0]["ID"].'<br>
            <b>Storage Class</b> : '.$result["Rules"][0]["Transitions"][0]["StorageClass"].'<br>
            <b>Expiration</b> : '.$result["Rules"][0]["Expiration"]["Days"].' Days<br>
            <b>Status</b> : '.$result["Rules"][0]["Status"].'<br>';

        print '
            <a tabindex="0" id="BLCpolicy" class="pull-right '.(($action=="listBuckets") ? 'hidden' : '').'" 
                data-toggle="popover" data-trigger="focus" 
                title="Bucket Life Cycle Policy" 
                data-content="'.$lifecyclepolicy.'">
                Bucket Life Cycle Policy
            </a>
            <br>
            ';
    } catch (S3Exception $ex) {
        $bucket_lifecycle_error = $ex->getAwsErrorCode();
    } catch (Exception $ex){
        $bucket_lifecycle_error = $ex->getMessage();
    }

    print '
        <ol class="breadcrumb">
    ';
        if($action == "listBuckets"){
            print '
                <li class="active">Simple Storage Service</li>';
        } else if($action == "listObjects" && !isset($_GET["prefix"])){
            print '
                <li><a href="../s3/index.php?action=listBuckets">s3</a></li>
                <li class="active">'.$bucket.'</li>';
        } else {
            $url = "../s3/index.php?action=listObjects&bucket=".$bucket."";
            print '
                <li><a href="../s3/index.php?action=listBuckets">s3</a></li>
                <li><a href="'.$url.'">'.$bucket.'</a></li>';

            if(substr_count($prefix, "/") > 0){
                $url .= "&source=tag&prefix=";
                $folders = explode("/",$prefix);
                $i=0;
                for($i=0;$i<(count($folders)-2);$i++){
                    $url .= $folders[$i]."/";
                    print '
                <li><a href="'.$url.'">'.$folders[$i].'</a></li>';
                }
                print '
                <li class="active">'.$folders[$i].'</li>';
            } else {
                print '
                <li class="active">'.$prefix.'</li>';
            }
        }

    print '
        </ol>';

    if($action == "listBuckets"){
        print '
            <ul class="list-group">';
        foreach ($buckets_list as $bucket_item) {
            print '
                <li class="list-group-item">
                    <a href="../s3/index.php?action=listObjects&bucket=' . $bucket_item . '" class="s3Bucket">
                        <img src="../resources/images/s3Bucket.png" alt="s3Bucket"/> &nbsp;' . $bucket_item . '
                    </a>
                </li>';
        }
        print '
           </ul>';

    } else if($action == "listObjects" && in_array($bucket,$buckets_list)) {
        print '
        <div class="s3UtilitiesBar">
            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createFolderModal" title="Create a folder here">
                <span class="glyphicon glyphicon-plus"></span> Create Folder
            </a> &nbsp;
            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#uploadObjectModal"  title="Upload Object to this folder">
                <span class="glyphicon glyphicon-cloud-upload"></span> Upload
            </a>
        </div>
        <!-- Start createFolder Modal -->
        <div class="modal fade" id="createFolderModal" tabindex="-1" role="dialog" aria-labelledby="createFolderLabel">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-primary" id="createFolderTitle">Create Folder</h4>
              </div>
              <div class="modal-body" style="margin-top:1em;">
                <span id="createFolderMessage"></span>
                <form id="createFolderForm" action="../s3/s3Transfer.php" method="post">
                    <div class="form-group">
                        <label for="foldername">New Folder Name:</label>
                        <input type="text" pattern="[a-zA-Z0-9\-\ ]" title="Enter Alpha-numeric Folder name" class="form-control" id="foldername" name="foldername" placeholder="New Folder" required>
                        <input type="hidden" name="bucket" value="'.$bucket.'" required>
                        <input type="hidden" name="prefix" value="'.$prefix.'" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Create" class="btn btn-success btn-lg" id="createFolderSubmit">
                    </div>
                </form>
              </div>
              <div class="modal-footer">
                <a href="#" type="button" class="btn btn-default" id="createFolderClose" data-dismiss="modal">Close</a>
              </div>
            </div>
          </div>
        </div>
        <!-- End createFolder Modal -->
        <!-- Start uploadObject Modal -->
        <div class="modal fade" id="uploadObjectModal" tabindex="-1" role="dialog" aria-labelledby="uploadObjectLabel">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-primary" id="uploadObjectTitle">Upload Object to S3</h4>
              </div>
              <div class="modal-body" style="margin-top:1em;">
                <div class="progress">
                  <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" id="uploadProgress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <span class="uploadProgressSR">0% Complete</span>
                  </div>
                  <div class="clearfix"></div>
                </div>
                <p class="text-primary progressText">upload in progress...</p>
                <span id="uploadObjectMessage"></span>
                <form id="uploadObjectForm" action="../s3/s3Upload.php" method="post" enctype="multipart/form-data" class="dropzone">
                    <div class="form-group">
                        <label for="objectname">Upload Object:</label>
                        <input type="file" title="Upload Object to S3" class="form-control" id="objectname" name="objectname[]" placeholder="Upload Object to S3" required>
                        <span id="helpBlock" class="help-block text-right">you can only upload a file of size 10MB max</span>
                        <input type="hidden" name="bucket" value="'.$bucket.'" required>
                        <input type="hidden" name="prefix" value="'.$prefix.'" required>
                    </div>
                    <div class="form-group">
                        <label for="objectdesc">Object Description:</label>
                        <textarea name="objectdesc" id="objectdesc" cols="30" rows="10" class="form-control" placeholder="Write some description" required></textarea>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Upload" class="btn btn-success btn-lg" id="uploadObjectSubmit" data-loading-text="uploading..." autocomplete="off">
                    </div>
                </form>
              </div>
              <div class="modal-footer">
                <a href="#" type="button" class="btn btn-default" id="uploadObjectClose" data-dismiss="modal">Close</a>
              </div>
            </div>
          </div>
        </div>
        <!-- End uploadObject Modal -->
        ';
    print '<br>
        <table class="table table-responsive table-bordered table-stripped s3ExplorerTable">
            <thead>
             <tr class="bg-primary text-center">
                <th width="25%">File Name</th>
                <th width="12%">User Uploaded</th>
                <th width="14%">Upload On</th>
                <th width="14%">Updated On</th>
                <th width="30%">Description</th>
                <th width="5%">Actions</th>
              </tr>
            </thead>
            <tbody>';
        try {
            $objects = $s3Client->listObjects([
                'Bucket' => $bucket,
                'Delimiter' => "/",
                'Prefix' => $prefix,
            ]);
        } catch (S3Exception $ex) {
            $list_object_error = $ex->getAwsErrorCode();
        } catch (Exception $ex){
            $list_object_error = $ex->getMessage();
        }

        if (count($objects["CommonPrefixes"]) > 0) {
            $no_objects = false;
            foreach ($objects["CommonPrefixes"] as $folder) {
                $foldername = rtrim($folder["Prefix"], '/');
                if (strlen($foldername) > 0) {
                    print '
                    <tr>
                      <td>
                        <a href="../s3/index.php?action=listObjects&bucket=' . $bucket . '&prefix=' . $foldername . '" class="s3Folder">
                            <span class="glyphicon glyphicon-folder-close"></span> &nbsp;' . str_ireplace($prefix, "", $foldername) . '
                        </a>
                      </td>
                       <td></td>
                       <td></td>
                       <td></td>
                       <td></td>
                       <td class="text-center">
                        <span class="text-center">
                            <a href="#" title="Delete Object" data-toggle="modal" data-target="#deleteObject" 
                                data-bucket="'.$bucket.'" data-key="'.$foldername.'" class="text-center text-danger">
                                <i class="fa fa-trash"></i>
                            </a>
                        </span>
                       </td>
                    </tr>';
                }
            }
        }

        if (count($objects["Contents"]) > 0) {
            $no_objects = false;
            $connectionManager = new ConnectionManager();
            $mysqlConnector = $connectionManager->getMysqlConnector();
            foreach ($objects["Contents"] as $file) {
                $filename = $file["Key"];
                $filesize = $file["Size"];
                if ($filesize > 0) {
                    $detailsArray = array("bucket" => "", "object" => "", "uploader" => "-","uploaded" => "-", "updated" => "-", "description" => "-");
                    $query = "select * from "._RDS_DATABASE.".objects where bucket='".$bucket."' and object='".$filename."' limit 1";
                    $result = $mysqlConnector->query($query);
                    if($result->rowCount() > 0){
                        $detailsArray = $result->fetchAll()[0];
                    }
                    // print_r($detailsArray);

                    $fileType = fileTypeIcon($filename);
                    $fileTitle = explode("-",$fileType);
                    print '
                    <tr>
                      <td>
                        <i class="fa fa-'.$fileType.' text-primary " title="'.(isset($fileTitle[1]) ? $fileTitle[1] : $fileTitle[0]).' type"></i> &nbsp;
                        <a href="#" data-toggle="modal" data-target="#downloadObject" 
                            data-bucket="'.$bucket.'" data-key="'.$filename.'" class="s3Object text-primary" 
                            title="Click to generate pre-signed URI for '.str_ireplace($prefix, "", $filename).'">
                            ' . str_ireplace($prefix, "", $filename) . '
                        </a>
                      </td>
                      <td>'.$detailsArray["uploader"].'</td>
                      <td>'.$detailsArray["uploaded"].'</td>
                      <td>'.$detailsArray["updated"].'</td>
                      <td><span class="more">'.$detailsArray["description"].'</span></td>
                      <td class="text-center">
                        <span class="text-center">
                            <a href="#" title="Delete Object" data-toggle="modal" data-target="#deleteObject" 
                                data-bucket="'.$bucket.'" data-key="'.$filename.'" class="text-center text-danger">
                                <i class="fa fa-trash"></i>
                            </a> 
                            <a href="'.getS3PreSignedURL($bucket, $filename).'"
                                target="_blank" download="'.str_ireplace($prefix, "", $filename).'" 
                                title="Download Object" class="text-success">
                                <i class="fa fa-cloud-download"></i>
                            </a>
                        </span>
                      </td>
                    </tr>';
                }
            }

            print '
            <!-- Start Object Download modal-->
            <div class="modal fade" id="downloadObject" tabindex="-1" role="dialog" aria-labelledby="downloadObjectLabel">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="exampleModalLabel">Download Object</h4>
                  </div>
                  <div class="modal-body">
                    <form>
                      <div class="form-group">
                        <label for="object-name" class="control-label">Generated Pre-Signed URL for Object:</label>
                        <input type="text" class="form-control" id="object-name">
                        <small class="text-danger pull-right">Valid for 10mins from now</small>
                      </div>
                    </form>                    
                    <button type="button" class="btn btn-success copylink" data-copytarget="#object-name">Copy Link</button>
                    <a href="#" type="button" class="btn btn-primary openlink" 
                        target="_blank" data-copytarget="#object-name">
                        Download Object
                    </a>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Object Download modal-->
            <!-- Start Object delete modal-->
            <div class="modal fade" id="deleteObject" tabindex="-1" role="dialog" aria-labelledby="deleteObjectLabel">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title text-danger" id="deleteObjectLabel">Delete Object ?</h4>
                  </div>
                  <div class="modal-body">
                    <span id="deleteObjectMessage"></span>
                    <div id="deleteFormWrapper">
                    <form id="deleteObjectForm" action="../s3/s3Delete.php" method="post">
                        <div class="form-group">
                            <label for="object-name" class="control-label text-danger">Do you want to delete this Object ?</label>
                            <input type="text" class="form-control disabled" name="object-name" id="object-name" value="" readonly required>
                        </div>
                        <div class="form-group">
                            <input type="submit" value="Yup, Delete it." class="btn btn-danger btn-lg" id="deleteObjectSubmit">
                        </div>
                    </form>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Object delete modal-->
            ';
        }

        if ($no_objects) {
            print '<tr><td colspan="6" class="text-danger">
                There are no objects under this path (or) you do not have required permissions to list Objects under this bucket
            </td></tr>';
        }
        if ($list_object_error != null) {
            print '<tr><td colspan="6" class="text-danger">' . $list_object_error . '</td></tr>';
        }
        if ($bucket_lifecycle_error != null) {
            // print '<li class="list-group-item text-danger">' . $bucket_lifecycle_error . '</li>';
            // un prettified aws error-code, ignore noSuchLifeCyclePolicy exception
            // --susheel 04/17/2017
        }
        print '</tbody>
              </table>
            ';
    } else {
        print '<ul class="list-group">
                <li class="list-group-item alert-danger">Unauthorized access request for the Bucket \''.$bucket.'\'</li>
               </ul>';
    }
    print '
    </div>
    <div class="col-lg-1 col-md-1"></div>
    ';

    include_once "../root/footer.php";
?>