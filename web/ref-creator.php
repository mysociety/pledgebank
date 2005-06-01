<?php
/*
 * ref-creator.php:
 * Various tools for the pledge creator.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-creator.php,v 1.5 2005-06-01 17:02:07 francis Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/db.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$picture_size_limit = 200 * 1024;

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err("Missing pledge reference");

$pledge = new Pledge($q_ref);

$P = person_if_signed_on();
if (!$P || $P->id() != $pledge->creator_id()) {
    $errs = importparams(
                array('email',  '/^[^@]+@[^@]+$/',  ''),
                array('LogIn',  '/./',              '', false)
            );

    if (!is_null($errs) || !$q_LogIn) {
        page_header("Pledge creator's page");
    if ($P) {
?>
    <p>
    The email address you are logged in with does not seem to be the same as
    the pledge creator. Please enter that email address to log in as them.
    </p>
<?
    }  else {
        print <<<EOF
<p>To access the pledge creator's page, please type in your email address
and click "Continue".  You can only access the page if you created the pledge.
</p>
EOF;
    }
        print <<<EOF
<form class="pledge" name="logIn" method="POST">
<div class="form_row">
    <label for="email"><strong>Email address</strong></label>
    <input type="text" size="20" name="email" id="email" value="$q_h_email">
    <input type="submit" name="LogIn" value="Continue &gt;&gt;">
</div>
</form>
EOF;
        /* XXX needs "send me a reminder" email */
        page_footer(array('nonav' => 1));
        exit();
    } else {
        $P = person_signon(array(
                        'reason' => "access the pledge creator's page",
                        'template' => 'creator-confirm'
                    ), $q_email);
    }
}

page_header("Pledge creator's page");

// Upload picture
$picture_upload_allowed = is_null($pledge->pin());
if (array_key_exists('userfile', $_FILES)) {
    if (!$picture_upload_allowed) {
        err("Picture not available for private pledge");
    }
    $tmp_name = $_FILES['userfile']['tmp_name'];
    if (!is_uploaded_file($tmp_name)) {
        err("Failed to upload the picture, please try again.");
    }
    if ($_FILES['userfile']['size'] > $picture_size_limit) {
        err("Please use a smaller picture.  Try scaling it down in a paint program, reducing the number of colours, or saving it as a JPEG or PNG.  Files of up to size $picture_size_limit bytes are allowed.  Your picture is size " . $_FILES['userfile']['size']);
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
        err("Please upload pictures of type GIF, JPEG or PNG.  You can use a paint program to convert them before uploading.");
    }
    $base_name =  $pledge->ref() . "." . $ext;
    $upload_file = OPTION_PB_PICTURE_DIR . "/" . $base_name;

    if (move_uploaded_file($tmp_name, $upload_file)) {
        db_query("update pledges set picture = ? where ref = ?",
            array(OPTION_PB_PICTURE_URL . "/" . $base_name, $pledge->ref()));
        db_commit();
        print "Thanks for uploading your picture to the pledge.  You can see below what it now looks like.";
    } else {
       err("Failed to upload the file.");
    }
}

// Display admin page
?>
<h2>Admin page for your pledge</h2>
<?
$pledge->render_box(array('showdetails' => true));

?>
    <h2>Things you can do</h2>
    <a href="/<?=$q_h_ref?>/announce">Send announcement to all pledge signers</a>
    <br>
<?
    if ($picture_upload_allowed) { 
?>
    <form enctype="multipart/form-data" action="/<?=$q_h_ref?>/creator" method="POST">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?=$picture_size_limit?>" />
        Upload picture to be displayed on your pledge: <input name="userfile" type="file" />
        <input type="submit" value="Add picture to pledge" />
    </form>
<?
    }

#header("Location: /$q_ref/announce");
page_footer();

?>
