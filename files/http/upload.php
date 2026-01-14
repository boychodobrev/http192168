<?php
session_start();
$_SESSION['movefile'] = false;
$_SESSION['moveback'] = false;

   if(isset($_FILES['file'])) {
     if (is_uploaded_file($_FILES['file']['tmp_name'])) {
      $errors = "";
      $file_name = $_FILES['file']['name'];
      $file_size =$_FILES['file']['size'];
      $file_tmp =$_FILES['file']['tmp_name'];
      $file_type=$_FILES['file']['type'];
      $file_path = ($_POST['dir']=='' ? $file_name : $_POST['dir']."/".$file_name);

     //  $extensions= array("html","php","js","jpeg","jpg","png","webp","gif","xcf");

      if($file_size < 1){
         $errors ='Something went wrong!';
      }

      if (strpbrk($file_name, "\\\\/:%&*?\"<>|")) {
         $errors ='Unacceptable File name!';
      }

      if($errors == "") {
          if (move_uploaded_file($file_tmp,$file_path)) {
           echo '<p style="color:#4caf50;padding:5px 0">'.$file_name.'</p>';
           $_SESSION["ucount"] += 1;

           $mime = mime_content_type($file_path);
           if(strstr($mime, "video/")) {
           $fileinfo = pathinfo($file_path);
           $command = 'ffmpeg -loglevel quiet -y -ss "$(bc -l <<< "$(ffprobe -loglevel quiet -of csv=p=0 -show_entries format=duration "'.$file_path.'")*0.5")" -i "'.$file_path.'" -vf "scale=320:-1" -frames:v 1 "'.$fileinfo['dirname'].'/'.$fileinfo['filename'].'--th.png"';
           system($command);

          }
         }

         else {
          echo 'Something went wrong!';
          $_SESSION["uerrors"] += 1;
         }
        }

       else {
        echo $errors;
        $_SESSION["uerrors"] += 1;
       }
      }
     }

if ($_SESSION["ucount"]==$_POST['fu'] && $_POST['badfiles'] < 1) {
 $_SESSION["ucount"]=0;
 $_SESSION["uerrors"] = 0;
 echo "<meta http-equiv='refresh' content='0;url=".$_SESSION["self"]."?dir=".urlencode($_POST['dir'])."&sort=time&desc=1&page=1'>";
}

if ($_SESSION["ucount"]==$_POST['fu'] && $_POST['badfiles'] >= 1) {
 $_SESSION["ucount"] = 0;
 $_SESSION["uerrors"] = 0;
 echo "<meta http-equiv='refresh' content='5;url=".$_SESSION["self"]."?dir=".urlencode($_POST['dir'])."&sort=time&desc=1&page=1'>";
}

if ($_SESSION["ucount"] + $_SESSION["uerrors"] == $_POST['fu']) {
 $_SESSION["ucount"] = 0;
 $_SESSION["uerrors"] = 0;
 echo "<meta http-equiv='refresh' content='5;url=".$_SESSION["self"]."?dir=".urlencode($_POST['dir'])."&sort=time&desc=1&page=1'>";
}





?>
