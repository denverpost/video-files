<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
// NOT MY CODE JUST WORKING WITH IT
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Audio Uploader - The Denver Post</title>

    <link rel="shortcut icon" href="http://extras.mnginteractive.com/live/media/favIcon/dpo/favicon.ico" type="image/x-icon" />

    <meta name="distribution" content="global" />
    <meta name="robots" content="noindex" />
    <meta name="language" content="en, sv" />
    <meta name="Copyright" content="Copyright &copy; The Denver Post." />

    <meta name="description" content="">
    <meta name="news_keywords" content="">

    <meta name="google-site-verification" content="2bKNvyyGh6DUlOvH1PYsmKN4KRlb-0ZI7TvFtuKLeAc" />
    <style type="text/css">
        footer 
        {
            clear: both; 
            margin: auto;
            text-align: center;
        }
        body { margin: 20px!important; }
    </style>
    <link rel="stylesheet" type="text/css" href="http://extras.mnginteractive.com/live/css/site67/bartertown.css" />
    <script src="http://local.denverpost.com/common/jquery/jquery-min.js"></script>
</head>
<body>


<?php
require('constants.php');
$conn_id = ftp_connect($FTP_SERVER) or die("Couldn't connect to $ftp_server");
ftp_login($conn_id,$FTP_USER_NAME,$FTP_USER_PASS);
ftp_pasv($conn_id, TRUE);
$year = date("Y");
if (!file_exists($FTP_DIRECTORY."/".$year)) { @ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year); }
ftp_chdir($conn_id, $FTP_DIRECTORY."/".$year);


// We'll need a list of the existing directories (i.e. projects)
$file_and_dir_list = ftp_nlist($conn_id, ".");
$project_list = Array();
foreach ( $file_and_dir_list as $item ):
    if ( strpos($item, '.') === FALSE ):
        $item = '<li><a href="#" onClick="$(\'#project\').attr(\'value\', \'' . $item . '\');">' . $item . '</a></li>';
        array_push($project_list, $item);
    endif;
endforeach;
sort($project_list);



function slugify($text)
{ 
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

  // trim
  $text = trim($text, '-');

  // transliterate
  //$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // lowercase
  $text = strtolower($text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  if (empty($text))
  {
    return '';
  }

  return $text;
}

if(isset($_FILES["audio"])) {
    echo "<div id='message'>";
    if ($_FILES["audio"]["error"] > 0):
        if ($_FILES["audio"]["error"]==4) { echo "<div style='background-color:red'>No file was chosen to be uploaded</div>"; exit; } // No image file was uploaded
        else { echo "<div style='background-color:red'>Error Code: " . $_FILES["audio"]["error"] . "</div>"; exit; } // Another error occurred
    else :
        $project = '';
        if ( array_key_exists('project', $_POST) ):
            $project = '/' . slugify($_POST['project']);
        endif;

        if (!file_exists($FTP_DIRECTORY."/".$year.$project)) { @ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year.$project); }

        if ($_FILES["audio"]["type"]=="audio/mp3"):
            move_uploaded_file($_FILES["audio"]["tmp_name"], $_FILES["audio"]["name"]);

            $path = $FTP_DIRECTORY."/".$year.$project.'/'.$_FILES["audio"]["name"];
            if (ftp_put($conn_id, $path, $_FILES["audio"]["name"], FTP_BINARY)):
                $filepath = "http://extras.denverpost.com/media/mp3/" . $year . $project . "/" . $_FILES["audio"]["name"];
                echo "<div class='alerts' style='background-color:#a2ff96;'>File created and uploaded to: " . $filepath . "</div>";

                // Put together the markup for the freeform
                $markup = file_get_contents('audio.html');
                $markup = str_replace('<', '&lt;', $markup);
                $markup = str_replace('{{URL}}', $filepath, $markup);

                if ( $_POST['title'] === '' ) $title = 'TITLE';
                else $title = htmlspecialchars($_POST['title']);
                $markup = str_replace('{{TITLE}}', $title, $markup);

                if ( $_POST['description'] === '' ) $description = 'DESCRIPTION';
                else $description = htmlspecialchars($_POST['description']);
                $markup = str_replace('{{DESCRIPTION}}', $description, $markup);

                if ( $_POST['thumbnail_url'] === '' ) $thumbnail_url = 'THUMBNAIL_URL';
                else $thumbnail_url = htmlspecialchars($_POST['thumbnail_url']);
                $markup = str_replace('{{THUMBNAIL_URL}}', $thumbnail_url, $markup);

                $markup = '<pre>' . $markup . '</pre>';
            else:
                echo "<div class='alerts' style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The file did not upload to " . $path . "!</div>";
            endif;

            unlink($_FILES["audio"]["name"]);     // delete the file in current folder

        else: 
            echo "<div class='alerts' style='background-color:red'>File must be a mp3 file!<br> This file is: ".$_FILES["audio"]["type"]."</div>";
        endif;
        echo "</div>";
    endif;
ftp_close($conn_id);
}
?>
<style>
#uploadImage           { float: left; width: 220px; overflow: visible; }
#uploadHere            { float: left; width: 600px; margin-top: 30px; }
#message               { position: absolute; margin-top: 250px; }
#message div           { padding: 0px 10px; }
.alerts
{
    margin-top:20px;
}
#project_list li
{
    float: left;
}
#project_list li::after
{
    content: ",\00A0";
}
</style>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/jquery-ui.css">
<script src="js/jquery-latest.min.js"></script>
<script src="js/jquery-ui.js"></script>

<?php if ( isset($markup) ) echo $markup; ?>
<h1>Audio File Uploader</h1>
<form action="" id="up" name="up" method="post" enctype="multipart/form-data">
    <h2 style="display:none;">Describe the audio</h2>
    <p id="project_label">
        <label for="project">Project Name:</label> <input name="project" id="project" type="text" maxlength="50" value="" />
    </p>
    <h5>Existing Projects</h5>
    <ul id="project_list"><?php foreach ( $project_list as $item ) echo $item; ?></ul>
    <hr noshade>
    <p id="audio_label">
        <label for="audio">Audio File:</label> <input name="audio" type="file" />
    </p>
    <hr noshade>
    <p>These fields are optional:</p>
    <p id="title_label">
        <label for="title">Title:</label> <input name="title" id="title" type="text" maxlength="50" value="" />
    </p>
    <p id="thumbnail_url_label">
        <label for="thumbnail_url">Thumbnail URL:</label> <input name="thumbnail_url" id="thumbnail_url" type="text" maxlength="100" value="" />
    </p>
    <p id="description_label">
        <label for="description">Description:</label> <input name="description" id="description" type="text" maxlength="500" value="" />
    </p>
   <input type="submit" name="submit" value="Upload">
</form>
</body>
</html>
