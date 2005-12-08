<?php
/*
 * ref-picture.php:
 * Alter picture on a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-picture.php,v 1.21 2005-12-08 01:37:20 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../../phplib/db.php';

require_once '../phplib/page.php';
require_once '../../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$picture_size_limit = 1000; // kilobytes
$picture_dimension_limit = 250; // pixels, width and height limit

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err(_("Missing pledge reference"));

page_check_ref(get_http_var('ref'));
$pledge = new Pledge($q_ref);

$P = person_if_signed_on();
if (!$P) {
    if ($pledge->has_picture()) {
        $reason_clause = "change the pledge's picture";
        $reason_clause_you = "change your pledge's picture";
        $P = person_signon(array(
                    "reason_web" => _("Before you can change the pledge's picture, we need to check that you created the pledge."),
                    "reason_email" => _("Then you will be able to change your pledge's picture."),
                    "reason_email_subject" => _("Change your pledge's picture at PledgeBank.com"))
                );
    } else {
        $P = person_signon(array(
                    "reason_web" => _("Before you can add a picture to the pledge, we need to check that you created the pledge."),
                    "reason_email" => _("Then you will be able to add a picture to your pledge."),
                    "reason_email_subject" => _('Add a picture to your pledge at PledgeBank.com'))
                );
    }
}

if ($P->id() != $pledge->creator_id()) {
    page_header("Add picture to pledge", array('ref' => $pledge->url_typein()) );
    print _("You must be the pledge creator to add a picture to a pledge.  Please
        <a href=\"/logout\">log out</a> and log in again as them.");
    page_footer();
    exit;
}

page_header($pledge->has_picture() ? _("Change pledge picture") : _("Add picture to pledge"),
    array('ref' => $pledge->url_typein()));

$picture_upload_allowed = is_null($pledge->pin());

// Upload picture
function upload_picture() {
    global $picture_upload_allowed, $picture_size_limit, $picture_dimension_limit, $pledge;

    if (get_http_var('removepicture')) {
        db_query("update pledges set picture = null where ref = ?",
            array($pledge->ref()));
        db_commit();
        print _("The picture has been removed.  Below you can see what the pledge now looks like.");
        $pledge = new Pledge($pledge->ref());
        return true;
    }

    if (!array_key_exists('userfile', $_FILES))
        return false;

    if (!$picture_upload_allowed) {
        return _("Picture not available for private pledge");
    }
    $tmp_name = $_FILES['userfile']['tmp_name'];
    if ($_FILES['userfile']['error'] > 0) {
        $errors = array(
            UPLOAD_ERR_INI_SIZE => _("There was an internal error uploading the picture.  The uploaded file exceeds the upload_max_filesize directive in php.ini"),
            UPLOAD_ERR_FORM_SIZE => sprintf(_("Please use a smaller picture.  Try scaling it down in a paint program, reducing the number of colours, or saving it as a JPEG or PNG.  Files of up to %d kilobytes are allowed."), $picture_size_limit),
            UPLOAD_ERR_PARTIAL => _("The uploaded file was only partially uploaded, please try again."),
            UPLOAD_ERR_NO_FILE => _("No file was uploaded, please try again."),
            UPLOAD_ERR_NO_TMP_DIR => _("There was an internal error uploading the picture.  Missing a temporary folder.")
        );
        return $errors[$_FILES['userfile']['error']];
    }
    if (!is_uploaded_file($tmp_name)) {
        return _("Failed to upload the picture, please try again.");
    }
    if ($_FILES['userfile']['size'] > $picture_size_limit * 1024) {
        return sprintf(_("Please use a smaller picture.  Try scaling it down in a paint program, reducing the number of colours, or saving it as a JPEG or PNG.  Files of up to %d kilobytes are allowed. Your picture is about %d kilobytes in size."), $picture_size_limit, intval($_FILES['userfile']['size'] / 1024) );
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
        return _("Please upload pictures of type GIF, JPEG or PNG.  You can use a paint program to convert them before uploading.");
    }

    list($width, $height) = getimagesize($tmp_name);
    if ($width > $picture_dimension_limit
       || $height > $picture_dimension_limit) {
       // Calculate new sizes
       $fraction = floatval($picture_dimension_limit) / floatval(max($width, $height));
       $newwidth = $width * $fraction;
       $newheight = $height * $fraction;
       // Resize image
       $dest = imagecreatetruecolor($newwidth, $newheight);
       $source = imagecreatefromjpeg($tmp_name);
       imagecopyresized($dest, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
       imagejpeg($dest, $tmp_name);
       $ext = "jpeg";
    } 
    
    $base_name =  $pledge->ref() . "." . $ext;
    $picture_contents = file_get_contents($tmp_name);
    if (!$picture_contents)
        err("Failed to read file into memory");

    db_query("delete from picture where filename = ?", array($base_name));
    db_query("insert into picture (filename, data) values ('$base_name', ".
        "'".pg_escape_bytea($picture_contents)."')");
    db_query("update pledges set picture = ? where ref = ?",
        array(OPTION_BASE_URL . "/pics/". $base_name, $pledge->ref()));
    db_commit();
    print _("Thanks for uploading your picture to the pledge.  You can see below what it now looks like.");
    $pledge = new Pledge($pledge->ref());
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
    <? if ($pledge->has_picture()) {
        print h2(_('Change pledge picture'));
    } else {
        print h2(_('Add a picture to your pledge'));
    } ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?=$picture_size_limit*1024?>">
    <?  print p(_('Choose the photo, logo or drawing that you would like to display on
        your pledge.  Keep it small so it fits well on the page &mdash; it will be
        automatically shrunk if it is too big.  You can use an image saved as
        either GIF, JPEG or PNG type.')); ?>
    <p><input name="userfile" type="file"><input type="submit" value="<?=_('Submit') ?>">
    <?  if ($pledge->has_picture()) {
            printf(p(_('Or you can %s if you don\'t want any image on your pledge any more.')), '<input name="removepicture" type="submit" value="' . _('Remove the picture') . '">');
        }
        print '</form>';
    } else {
        print p(_("Pictures are not available for private pledges.  Please let us know if this is a problem."));
    }

#header("Location: /$q_ref/announce");
page_footer();

?>
