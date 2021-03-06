<?php
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
ini_set('upload_max_filesize', '20000M');
ini_set('post_max_size', '20000M');
ini_set('max_input_time', 30000);
ini_set('max_execution_time', 30000);
error_reporting(-1);
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Video Uploader</title>

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
    <script src="http://local.denverpost.com/common/jquery/jquery-min.js"></script>
</head>
<body>


<?php
require('constants.php');
$conn_id = ftp_connect($FTP_SERVER) or die("Couldn't connect to $ftp_server");
ftp_login($conn_id,$FTP_USER_NAME,$FTP_USER_PASS);
ftp_pasv($conn_id, TRUE);
//$year = date("Y");
//if (!file_exists($FTP_DIRECTORY."/".$year)) { @ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year); }
//ftp_chdir($conn_id, $FTP_DIRECTORY."/".$year);
ftp_chdir($conn_id, $FTP_DIRECTORY);




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

  if (empty($text)) return '';

  return $text;
}

if(isset($_FILES["video"])) {
    echo "<div id='message'>";
    if ($_FILES["video"]["error"] > 0):
        // Here's a list of video error codes and what they mean:
        // http://php.net/manual/en/features.file-upload.errors.php
        if ($_FILES["video"]["error"]==4) { echo "<div style='background-color:red'>No file was chosen to be uploaded</div>"; exit; } // No file was uploaded
        else { echo "<div style='background-color:red'>Error Code: " . $_FILES["video"]["error"] . ". Please email jmurphy@denverpost.com the contents of this error message.</div>"; exit; } // Another error occurred
    else :

        //if (!file_exists($FTP_DIRECTORY."/".$year.$project)) { @ftp_mkdir($conn_id, $FTP_DIRECTORY."/".$year.$project); }

        #if ($_FILES["video"]["type"]=="video/mp3"):
        move_uploaded_file($_FILES["video"]["tmp_name"], $_FILES["video"]["name"]);
        if ( isset($_FILES["image"]["tmp_name"]) ) move_uploaded_file($_FILES["image"]["tmp_name"], $_FILES["image"]["name"]);

        $path = $FTP_DIRECTORY."/".$_FILES["video"]["name"];
        if (ftp_put($conn_id, $path, $_FILES["video"]["name"], FTP_BINARY)):
            $videopath = "http://extras.denverpost.com/media/video/inform/" . $_FILES["video"]["name"];
            echo "<div class='alerts' style='background-color:#a2ff96;'>File created and uploaded to: " . $videopath . "</div>";
            
            if ( isset($_FILES["image"]["tmp_name"]) ):
                $path = $FTP_DIRECTORY."/".$_FILES["image"]["name"];
                if ( ftp_put($conn_id, $path, $_FILES["image"]["name"], FTP_BINARY) ):
                    $imagepath = "http://extras.denverpost.com/media/video/inform/" . $_FILES["image"]["name"];
                    echo "<div class='alerts' style='background-color:#a2ff96;'>File created and uploaded to: " . $imagepath . "</div>";
                endif;
            endif;

            // Put together the markup for the freeform
            $markup = file_get_contents('video.html');

            if ( $_POST['title'] === '' ) $title = 'TITLE';
            else $title = htmlspecialchars($_POST['title']);
            $markup = str_replace('{{TITLE}}', $title, $markup);

            if ( $_POST['description'] === '' ) $description = 'DESCRIPTION';
            else $description = htmlspecialchars($_POST['description']);
            $markup = str_replace('{{DESCRIPTION}}', $description, $markup);

            // There's a field for all keywords, we also insert them as categories.
            if ( $_POST['keywords'] === '' ) $keywords = '';
            else $keywords = explode(',', htmlspecialchars($_POST['keywords']));
            $markup = str_replace('{{KEYWORDS}}', implode(',', $keywords), $markup);

            $categories = '';
            foreach ( $keywords as $keyword ):
                if ( trim($keyword) !== '' ):
                    $categories .= '<media:category>' . $keyword . '</media:category>';
                endif;
            endforeach;
            $markup = str_replace('{{CATEGORIES}}', $categories, $markup);
    
            

            if ( !isset($imagepath) ) $imagepath = '';
            $markup = str_replace('{{IMAGE}}', $imagepath, $markup);

            $markup = str_replace('{{VIDEO}}', $videopath, $markup);
            $markup = str_replace('{{TIMESTAMP}}', date('c', time()), $markup);
            file_put_contents('video.xml', $markup);

            $path = $FTP_DIRECTORY.'/video.xml';
            if ( ftp_put($conn_id, $path, 'video.xml', FTP_BINARY) ):
                $filepath = 'http://extras.denverpost.com/media/video/inform/video.xml';
                echo "<div class='alerts' style='background-color:#a2ff96;'>File created and uploaded to: " . $filepath . "</div>";
            endif;
        else:
            echo "<div class='alerts' style='background-color:red'><span style='font-weight:bold'>ERROR</span> :: The file did not upload to " . $path . "!</div>";
        endif;

        unlink($_FILES["video"]["name"]);     // delete the file in current folder
        unlink($_FILES["image"]["name"]);     // delete the file in current folder

        #else: 
        #    echo "<div class='alerts' style='background-color:red'>File must be a mp3 file!<br> This file is: ".$_FILES["video"]["type"]."</div>";
        #endif;
        echo "</div>";
    endif;
ftp_close($conn_id);
}
?>
<style>
#uploadImage           { float: left; width: 220px; overflow: visible; }
#uploadHere            { float: left; width: 600px; margin-top: 30px; }
#message               { }
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
<h1>Video File Uploader</h1>
<form action="" id="up" name="up" method="post" enctype="multipart/form-data">
    <h2 style="display:none;">Describe the video</h2>
<!--
    <p id="project_label">
        <label for="project">Project Name:</label> <input name="project" id="project" type="text" maxlength="50" value="" />
    </p>
    <h5>Existing Projects</h5>
    <ul id="project_list"><?php //foreach ( $project_list as $item ) echo $item; ?></ul>
    <hr noshade>
-->
    <p id="video_label">
        <label for="video">Video File:</label> <input name="video" type="file" />
    </p>
    <p id="image_label">
        <label for="image">Image Thumbnail File:</label> <input name="image" type="file" />
    </p>
    <p id="title_label">
        <label for="title">Title:</label> <input name="title" id="title" type="text" maxlength="70" value="" />
    </p>
    <p id="description_label">
        <label for="description">Description:</label> <input name="description" id="description" type="text" maxlength="500" value="" />
    </p>
    <p id="keywords_label">
        <label for="keywords">Keywords (comma-separated):</label> <input name="keywords" id="keywords" type="text" maxlength="500" value="" />
    </p>
    <pre>
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'dpnews,');">dpnews</a> - any news video without Molly
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'dpent,');">dpent</a> - any entertainment video without Molly
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'dptvmolly,');">dptvmolly</a> - anything with Molly
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'sportsshow,');">sportsshow</a> - anything from The Sports Show
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'dpmarijuana,');">dpmarijuana</a> - anything about marijuana
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'cannabistfull,');">cannabistfull</a> - the full episode of The Cannabist Show
<a href="javascript:$('#keywords').attr('value', $('#keywords').attr('value') + 'culturs,');">culturs</a> - any Culturs video (you will probably never use this)
</pre>
<!--
    <hr noshade>
    <p>These fields are optional:</p>
    <p id="thumbnail_url_label">
        <label for="thumbnail_url">Thumbnail URL:</label> <input name="thumbnail_url" id="thumbnail_url" type="text" maxlength="100" value="" />
    </p>
-->
   <input type="submit" name="submit" value="Upload">
</form>
</body>
</html>
