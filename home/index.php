<?php
  include_once('../root/header.php');
    print '
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 banner">
         <h1>AWS Assignment</h1>
         <img src="../resources/images/frontpage_banner.jpg" alt="">
      </div>
      <div class="clearfix"></div><br>
      <div class="col-lg-1 col-md-1"></div>
      <div class="col-lg-10 col-md-10 col-sm-12 col-xs-12 contentBody">
          <h1 class="text-success">Hello '.(isset($_SESSION["DatalakeUser"])? $_SESSION["DatalakeUser"] : "").'!</h1>
          <p class="text-justified">
            Welcome to my Portal.
          </p>';
          if(!isset($_SESSION["DatalakeUser"])) {
            print '<p class="text-justified">Hello there, Please use the below buttons to register or sign - in to my portal <br/><br/>
                <a href="#" data-toggle = "modal" data-target="#myLoginModal" title="Login" class="btn btn-success btn-lg" ><i class="fa fa-sign-in" ></i > Sign - in</a > &nbsp;
                <a href="#" data-url = "../authenticate/register.php" title="Register" class="customMessage btn btn-warning btn-lg" ><i class="fa fa-plus-circle" ></i > Register</a >
                </p>
          ';
          }
      print '
        <br>
      </div>
      <div class="col-lg-1 col-md-1"></div>
    ';
  include_once('../root/footer.php');
?>
