<?php

/*********************helper functions******************/

function clean($string){
  return htmlentities($string);
}

function redirect($location){
  return header("Location: {$location}");
}

function set_message($message){
  if(!empty($message)){
    $_SESSION['$message']=$message;
  }
  else {
    $message="";
  }
}

function validate_errors($string){
  $message =<<<DELIMETER
<div class="alert alert-success alert-dismissible" style="border-radius: 25px;" role="alert">
    <button type="button" class="close" data-dismiss="alert">
      <span aria-hidden="true">×</span><span class="sr-only">Close</span>
    </button>$string
  </div>
DELIMETER;
return $message;
}

function display_message(){
  if(isset($_SESSION['$message'])){
    $string=$_SESSION['$message'];
    $message =<<<DELIMETER
<div class="alert alert-success alert-dismissible" style="border-radius: 25px;" role="alert">
      <button type="button" class="close" data-dismiss="alert">
        <span aria-hidden="true">×</span><span class="sr-only">Close</span>
      </button>$string
    </div>
DELIMETER;
    echo $message;
    unset($_SESSION['$message']);
  }
}

function token_generator(){
   $token=$_SESSION['token'] =md5(uniqid(mt_rand(),true));
   return $token;
}

function email_exist($email){
  $sql="SELECT id FROM users WHERE email ='$email'";
  $result=query($sql);
  if(row_count($result)==1){
    return true;
  }
  else {
    return false;
  }
}

function user_exist($user){
  $sql="SELECT id FROM users WHERE username ='$user'";
  $result=query($sql);
  if(row_count($result)==1){
    return true;
  }
  else {
    return false;
  }
}

function send_email($email,$subject,$msg,$headers){
  return mail($email,$subject,$msg,$headers);
  }



/*********************validation functions******************/

function validate_user_reg(){
  $errors = [];
  $min=3;
  $max=20;

  if($_SERVER['REQUEST_METHOD']=="POST"){
    $first=clean($_POST['firstname']);
    $last=clean($_POST['lastname']);
    $user=clean($_POST['username']);
    $email=clean($_POST['email']);
    $pw=clean($_POST['password']);
    $confirm=clean($_POST['confirm_password']);
 
    if(email_exist($email)){
      $errors[]="email exist already";
    }

    if(user_exist($user)){
      $errors[]="username exist already";
    }

    if(strlen($first)<$min){
      $errors[]="your first name cannot be < than {$min} characters";
    }

    if(strlen($last)<$min){
      $errors[]="your last name cannot be < than {$min} characters";
    }

    if(strlen($last)>$max){
      $errors[]="your last name cannot be greater than {$max} characters";
    }

    if(strlen($first)>$max){
      $errors[]="your first name cannot be greater than {$max} characters";
    }
    if($pw !== $confirm){
      $errors[]="password doesnt match";
    }

    if(!empty($errors)){
      foreach ($errors as $error) {
echo validate_errors($error);
      }
    }
    else {
      if(register_user($first,$last,$user,$email,$pw)){
      set_message("please check your inbox/spam folder for activation");
      redirect("login.php");
      }
      else {
        set_message("Registered successfully");
        redirect("login.php");
      }
    }


  }
}

function register_user($first,$last,$user,$email,$pw){
  global $con;

  $first=escape($first);
  $last=escape($last);
  $user=escape($user);
  $email=escape($email);
  $pw=escape($pw);

  if(email_exist($email)){
    return false;
  }
  elseif (user_exist($user)) {
     return false;
    }
   else {
    $pw = md5($pw);
    $validation=md5($user . str_replace(' ', '', microtime()));
    $sql="INSERT INTO users(first,last,username,email,password,valid_code,active)";
    $sql.=" VALUES ('$first','$last','$user','$email','$pw','$validation',0)";
    $result=query($sql);

    $subject="activate account";
    $headers="From : tarun3run@gmail.com";
    $msg="please click the link below to activate your account
    http://localhost:8080/login/activate.php?email=$email&code=$validation";
    if(send_email($email,$subject,$msg,$headers)){
      return true;
    }
  }
}

/*******************************Activate user functions******************************/

function activate_user(){
    if($_SERVER['REQUEST_METHOD']=="GET"){
      if(isset($_GET['email'])){
        $email=clean($_GET['email']);
        $validation=clean($_GET['code']);
        $sql= "SELECT id FROM users WHERE email= '".escape($email)."' AND valid_code = '".escape($validation)."'";
        $result=query($sql);

        if(row_count($result)==1){
          $sql1="UPDATE users SET active=1,valid_code=0 WHERE email= '".escape($email)."' AND valid_code='".escape($validation)."'";
            $result1=query($sql1);

        set_message("activated please login");
        redirect("login.php");
        }
        else{
          set_message("Registration Error try again");
          redirect("login.php");
        }

      }

    }
}
/*******************************validate user login functions******************************/

function validate_user_login(){
  $errors = [];
  $min=3;
  $max=20;

if($_SERVER['REQUEST_METHOD']=='POST'){
  $email=clean($_POST['email']);
  $pw=clean($_POST['password']);
  $remember= isset($_POST['remember']);



  if(empty($email)){
    $errors[]="Emails cannot be empty";
  }
  if(empty($pw)){
    $errors[]="Enter password";
  }

  if(!empty($errors)){
    foreach ($errors as $error) {
echo validate_errors($error);
    }
  }
  else {
    if(login_user($email,$pw,$remember)){
      redirect("admin.php");
    }
    else {

validate_errors("your credentials is incorrect");

    }
  }

}

}

/*******************************login functions******************************/

function login_user($email,$pw,$remember){

  $sql="SELECT password, id FROM users WHERE email = '".escape($email)."' AND active=1";
  $result=query($sql);
  if(row_count($result)==1){

    $row=fetch_array($result);
    $dbpw=$row['password'];

    if(md5($pw)==$dbpw){
        if($remember =='on'){
          setcookie('email',$email,time()+86400);
        }

      $_SESSION['email']=$email;
      return true;
    }
    else {
      echo validate_errors("Password Incorrect");
      return false;
    }


  }
  else {
    echo validate_errors("credentials wrong");
    return false;
  }

}

function loggedin(){
  if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
    return true;
  }
  else {
    return false;
  }
}

/*******************************recover functions******************************/


function recover_password(){
  if($_SERVER['REQUEST_METHOD']=="POST"){
    if(isset($_SESSION['token']) && $_POST['token']===$_SESSION['token']){
      $email = escape($_POST['email']);
        if(email_exist($email)){
          $val = md5($email. str_replace(' ', '', microtime()));
          setcookie('temp_access_code',$val,time()+900);

          $sql="UPDATE users SET valid_code = '".escape($val)."' WHERE email = '".escape($email)."'";
          $result=query($sql);

          $subject="reset password";
          $headers="From : vishnuthumpudi@gmail.com";
          $msg="here is your code {$val} click this link to reset your password http://localhost:8080/login/code.php?email=$email&code=$val";
          if(!send_email($email,$subject,$msg,$headers)){
          echo validate_errors("email not found");
          }
          else {
          //  set_message("<p class='bg-success'>please check spam folder for recover code</p>");
            $_SESSION['email']=$email;
            redirect("code.php");
          }


        }
        else {
          echo validate_errors("email not found");
        }
      }
  }

if(isset($_POST['cancel_submit'])){

  redirect("login.php");

}


}


/*******************************valicate code functions******************************/

function validate_code(){

  if(isset($_COOKIE['temp_access_code'])){

      // if(!isset($_GET['email']) && !isset($_GET['code'])){
      //   redirect("index.php");
      //
      // }
      // else if(empty($_GET['email']) || empty($_GET['code'])){
      //
      //   redirect("index.php");
      // }
      // else {
        if(isset($_POST['code'])){
          $email=clean($_GET['email']);
          $validation=clean($_POST['code']);
          $sql = "SELECT id FROM users WHERE valid_code ='".escape($validation)."' AND email='".escape($email)."'";
          $result =query($sql);
          if(row_count($result)==1){
            setcookie('temp_access_code',$validation,time()+500);
            redirect("reset.php?email=$email&code=$validation");
          }
          else {
          echo validate_errors("sorry wrong validation");
          }

        // }
      }
    }

  else {
    set_message("validation expires");
    redirect("recover.php");
  }
}

/*******************************password Reset functions******************************/

function password_reset(){
  if(isset($_COOKIE['temp_access_code'])){


    if(isset($_GET['email'])&& isset($_GET['code'])){


      if(isset($_SESSION['token']) && isset($_POST['token'])){


          if($_POST['token']===$_SESSION['token']){

            if($_POST['confirm_password'] === $_POST['password']){
            $updated_pw=md5($_POST['password']);

            $sql="UPDATE users SET password = '".escape($updated_pw)."',valid_code=0 WHERE email='".escape($_GET['email'])."'";
            $result=query($sql);
            set_message("updated successfully");
            redirect("login.php");

           }
           else {
             echo validate_errors("password not match");
           }
         }
        }
      }
   }


      else{
        set_message("sorry unable to process");
        redirect("recover.php");
        echo "not work";
      }
    }

/*******************************password Reset functions******************************/

function validate_user_data(){
  if($_SERVER['REQUEST_METHOD']=="POST" && isset($_SESSION['email'])){
    $email=clean($_SESSION['email']);
    $interest=clean($_POST['interest']);
    $bio=clean($_POST['bio']);
    $work=clean($_POST['work']);
    $contact=clean($_POST['contact']);
    $img=clean($_POST['img']);

  $sql="UPDATE users SET interest='".escape($interest)."',bio='".escape($bio)."',work='".escape($work)."',contact='".escape($contact)."',img='".escape($img)."',complete=1 WHERE email='".escape($email)."'";
  $result=query($sql);
  redirect("index.php");
  }

}
function entered(){
  $email=clean($_SESSION['email']);
  $sql="SELECT complete FROM users WHERE email='".escape($email)."' ";
  $result=query($sql);
  $row=fetch_array($result);
  if($row['complete']==1){

    return true;
  }

}
 ?>
