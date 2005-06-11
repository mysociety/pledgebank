<?php
/*
 * ref-picture.php:
 * Alter picture on a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-picture.php,v 1.6 2005-06-11 19:54:01 chris Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/db.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$picture_size_limit = 200; // kilobytes

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err("Missing pledge reference");

$pledge = new Pledge($q_ref);

$P = person_if_signed_on();
if (!$P) {
    $P = person_signon(array(
                    'reason' => $pledge->has_picture() ?
                        "add a picture to the pledge" :
                        "change the pledge's picture",
                    'template' => 'creator-confirm'
                ));
}

if ($P->id() != $pledge->creator_id()) {
    page_header("Add picture to pledge", array('ref' => $pledge->url_main()) );
    print "You must be the pledge author to add a picture to a pledge.  Please
        <a href=\"/logout\">log out</a> and log in again as them.";
    page_footer();
    exit;
}

page_header($pledge->has_picture() ? "Change pledge picture" : "Add picture to pledge",
    array('ref' => $pledge->url_main()));

$picture_upload_allowed = is_null($pledge->pin());

// Upload picture
function upload_picture() {
    global $picture_upload_allowed, $picture_size_limit, $pledge;

    if (get_http_var('removepicture')) {
        db_query("update pledges set picture = null where ref = ?",
            array($pledge->ref()));
        db_commit();
        print "The picture has been removed.  Below you can see what the pledge now looks like.";
        $pledge = new Pledge($pledge->ref());
        return true;
    }

    if (!array_key_exists('userfile', $_FILES))
        return false;

    if (!$picture_upload_allowed) {
        return "Picture not available for private pledge";
    }
    $tmp_name = $_FILES['userfile']['tmp_name'];
    if ($_FILES['userfile']['error'] > 0) {
        $errors = array(
            UPLOAD_ERR_INI_SIZE => "There was an internal error uploading the picture.  The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "Please use a smaller picture.  Try scaling it down in a paint program, reducing the number of colours, or saving it as a JPEG or PNG.  Files of up to $picture_size_limit kilobytes are allowed.",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded, please try again.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded, please try again.",
            UPLOAD_ERR_NO_TMP_DIR => "There was an internal error uploading the picture.  Missing a temporary folder."
        );
        return $errors[$_FILES['userfile']['error']];
    }
    if (!is_uploaded_file($tmp_name)) {
        return "Failed to upload the picture, please try again.";
    }
    if ($_FILES['userfile']['size'] > $picture_size_limit * 1024) {
        return "Please use a smaller picture.  Try scaling it down in a paint program, reducing the number of colours, or saving it as a JPEG or PNG.  Files of up to $picture_size_limit kilobytes are allowed.  Your picture is about " . intval($_FILES['userfile']['size'] / 1024) . " kilobytes in size.";
    }
    // TODO: Add BMP, and convert them to PNG.

    $picture_type = exif_imagetype($tmp_name);
    if ($picture_type == IMAGETYPE_GIF) {
        $ext = "gif";
    } elseif ($picture_type == IMAGETYPE_JPEG) {
        $ext = "jpeg";
    } elseif ($picture_type == IMAGETYPE_PNG) {
        $ext = "png";
    } else {
        return "Please upload pictures of type GIF, JPEG or PNG.  You can use a paint program to convert them before uploading.";
    }
    $base_name =  $pledge->ref() . "." . $ext;
    $upload_file = OPTION_PB_PICTURE_DIR . "/" . $base_name;

    if (move_uploaded_file($tmp_name, $upload_file)) {
        db_query("update pledges set picture = ? where ref = ?",
            array(OPTION_PB_PICTURE_URL . "/" . $base_name, $pledge->ref()));
        db_commit();
        print "Thanks for uploading your picture to the pledge.  You can see below what it now looks like.";
        $pledge = new Pledge($pledge->ref());
    } else {
       return "Failed to upload the file.";
    }
    return true;
}

$error = upload_picture();
if (gettype($error) == "string") {
    print "<div id=\"errors\"><ul><li>$error</li></ul></div>";
}

// Display admin page
?>
<?
$pledge->render_box(array('showdetails' => true));

?>
<?
    if ($picture_upload_allowed) { 
?>
    <form id="pledgeaction" enctype="multipart/form-data" action="/<?=$q_h_ref?>/picture" method="POST">
    <? if ($pledge->has_picture()) { ?>
        <h2>Change pledge picture</h2>
    <? } else { ?>
        <h2>Add picture to your pledge</h2>
    <? } ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?=$picture_size_limit*1024?>" />
        <p>Choose the photo, logo or drawing that you would like to display on
        your pledge.  Keep it small so it fits well on the page.  You can use
        an image saved as either GIF, JPEG or PNG type.
        <p><input name="userfile" type="file" /><input type="submit" value="Submit" />
    <? if ($pledge->has_picture()) { ?>
        <p>Or you can <input name="removepicture" type="submit" value="Remove the picture" />
        if you don't want any image on your pledge any more.
    <? } ?>
    </form>
<?
    } else {
        print "<p>Pictures are not available for private pledges.  Please let us know if this is a problem.";
    }

#header("Location: /$q_ref/announce");
page_footer();

?>
