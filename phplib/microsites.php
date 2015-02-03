<?php
/*
 * microsites.php:
 * Microsites are special sub-sites for London, Global Cool etc.
 * This file contains lots of functions which return the values appropriate
 * to each microsite. 
 *  
 * The idea is that you can create a new microsite by entirely editing this
 * file, and the rest of the code just has hooks calling functions here, rather
 * than lots of messy if statements. 
 * 
 * In practice, there are some if statements elsewhere for really special
 * cases. For example, page.php has some if statements for the Global Cool
 * style, because it requires inclusion of templates after our headers,
 * in a very particular place, which would be hard to understand if partly
 * explained in functions here.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: microsites.php,v 1.126 2008-09-15 13:34:29 matthew Exp $
 * 
 */

#############################################################################
# Name and domain information

/* Codes of microsites, and name displayed next to PledgeBank logo */
$microsites_list = array('everywhere' => _('Everywhere'),
                         'catcomm' => 'CatComm',
                         'barnet' => 'Barnet',
                         'rbwm' => 'RBWM',
);

/* Other domains which refer to microsites (must be one-to-one as reverse map used to make URLs) */
# If you alter this, also alter web/poster.cgi which has a microsites_from_extra_domains variable
$microsites_from_extra_domains = array(
    'pledgebank.barnet.gov.uk' => 'barnet',
    'pledge.rbwm.gov.uk' => 'rbwm'
);
$microsites_to_extra_domains = array_flip($microsites_from_extra_domains);

/* These are listed on /where */
$microsites_public_list = array('everywhere' => _('Everywhere &mdash; all countries in all languages'),
                                'catcomm' => _('Catalytic Communities')
                                );

/* Include a template for a particular microsite. */
function microsite_template($template) {
    global $microsite;
    $site = $microsite;
    if (!$site || $site == 'everywhere' || !file_exists("../templates/$site/$template.php"))
        $site = 'website';
    return "../templates/$site/$template.php";
}

/* As sometimes microsites.php is included before the locale is set... */
function microsites_for_locale() {
    global $microsites_list, $microsites_public_list;
    $microsites_list['everywhere'] = _('Everywhere');
    foreach ($microsites_public_list as $key => $value) {
        $microsites_public_list[$key] = _($value);
    }
}

/* Pledges made from these microsites are not marked as such in the microsite
 * field in the pledges table. */
$microsites_no_pledge_field = array(
    'everywhere', 
);

/* microsites_get_name 
 * Returns display name of microsite if we are on one. e.g. Glastonbury */
function microsites_get_name() {
    global $microsite, $microsites_list;
    if (array_key_exists($microsite, $microsites_list))
        return $microsites_list[$microsite];
    return null;
}

/* microsites_google_conversion_tracking LABEL
 * Whether to use Google AdWords conversion tracking for signers/new pledges.
 * Outputs Javascript code if yes.  Label is the code used by adverts, things
 * like "signup", "default", "lead".
 */
function microsites_google_conversion_tracking($label) {
    global $microsite;

    // Only do it on the main site.
    if (OPTION_BASE_URL != "http://www.pledgebank.com")
        return false;

    if (!$microsite || $microsite == 'everywhere') {
?>
<!-- Google Code for signup Conversion Page -->
<script language="JavaScript" type="text/javascript">
<!--
var google_conversion_id = 1067468161;
var google_conversion_language = "en_GB";
var google_conversion_format = "1";
var google_conversion_color = "666666";
if (1) {
  var google_conversion_value = 1;
}
var google_conversion_label = "<?=$label?>";
//-->
</script>
<script language="JavaScript" src="http://www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<img height=1 width=1 border=0 src="http://www.googleadservices.com/pagead/conversion/1067468161/imp.gif?value=1&label=signup&script=0">
</noscript>
<?
    return true;
}

    return false;
}

/* microsites_redirect PLEDGE
 * When going to some pledges, a redirect is done so the URL is
 * that of a particular microsite. */
function microsites_redirect($p) {
    global $microsite;
    $redirect_microsite = $microsite;

    # Microsites for which all pledges marked in the database as belonging to
    # that microsite do a redirect
    $redirect_microsites = array();
    if (in_array($p->microsite(), $redirect_microsites)) {
        $redirect_microsite = $p->microsite();
    }
    # Redirect back again, if on, for example, a non-global-cool pledge on
    # global-cool domain
    if ($microsite && in_array($microsite, $redirect_microsites)) {
        $redirect_microsite = $p->microsite();
    }

    # If necessary, do the redirect
    if ($microsite != $redirect_microsite) {
        $newurl = pb_domain_url(array('path' => $_SERVER['REQUEST_URI'], 'microsite' => $redirect_microsite));
        header("Location: $newurl");
        exit;
    }
}

/* microsites_site_country
 * Default country for microsite.  Used for search, and for default country on
 * alerts / new pledge form */
function microsites_site_country() {
    global $site_country, $microsite;
    if ($microsite) {
        return null;
    }
    return $site_country;
}

/* microsite_requires_abuse_test
 * by default should be doing abuse test
 * except in Barnet, where all pledges are going out from the same email which is effectively
 * an admin-authorised action
 */
function microsite_requires_abuse_test() {
    global $microsite;
    if ($microsite == 'barnet') return false;
    return true;
}

/* microsites_admin_announce_link
 * show a link to "Send message to signers" in the admin
 * Doesn't really make sense in normal deployments, as admin is not the pledge creator
 * But in Barnet's case, admin is probably same staff, and this avoids putting the announce
 * link on the pledge page.
 */
function microsites_admin_announce_link($pledge_microsite) {
    global $microsite;
    if ($microsite == 'barnet' || $pledge_microsite == 'barnet') return true;
    return false;    
}

#############################################################################
# Styling

/* microsites_navigation_menu
 * Returns array of text to links for the main site navigation menu.
 */
function microsites_navigation_menu($contact_ref) {
    $P = pb_person_if_signed_on(true); /* Don't renew any login cookie. */
    debug_timestamp(true, "retrieved person record");

    $menu = array();
    $menu[_('All Pledges')] = "/list";
    if ($P)
        $menu[_('My Pledges')] = "/my";
    else
        $menu[_('Login')] = "/my";
    $menu[_('About')] = "/faq";

    return $menu;
}

/* produces HTML for picture (used in pledge->render_box())
   args: picture_url
         pledge_microsite passed in so this works even when we're in an environment without
                          microsites (e.g., admin)
*/
function microsite_render_picture($picture_url, $pledge_microsite) {
    global $microsite;
    if ($microsite == 'barnet' ||  $pledge_microsite == 'barnet') {
        return "<div class='ms-pb-top-img' style='width: 100%;height:360px;background-position:50% 50%; background-repeat:repeat;background-image:url(" 
            . $picture_url . ");margin-bottom:1em;'></div>";
    } else {
        return "<img class=\"creatorpicture\" src=\"".$picture_url."\" alt=\"\">"; 
    }
}

/* microsite_picture_width_limit & microsite_picture_height_limit
 * Returns the maximum size (in pixels) of an uploaded picture
 *
 * The Barnet design currently requires pictures that are 1087 x 360 pixels so we must allow
 * files to be that wide. Letting the height get much bigger may encourage
 * oversized images (really they should be cropped), but for now we're letting 
 * that through -- the filesize is capped anyway, and images with smaller sizes or ratios
 * will be tiled, which is OK if the pictures have been designed with that in mind.
*/
function microsite_picture_dimension_limit(){
    global $microsite;
    if ($microsite == 'barnet') return 1087;
    return 250;
}

function microsite_picture_upload_advice(){
    global $microsite;
    if ($microsite == 'barnet'){
      return _("Please choose an image that you would like to display on the pledge.
        The page layout will work best with an image that is <b>1087&nbsp;pixels&nbsp;wide</b> and 
        <b>360&nbsp;pixels&nbsp;high</b>. If your image is bigger, it will be cropped; if either
        dimension is smaller, it will be tiled. Use GIF, JPEG or PNG. ");  
    };    
    return _('Choose the photo, logo or drawing that you would like to display on
    your pledge.  Keep it small so it fits well on the page &mdash; it will be
    automatically shrunk if it is too big.  You can use an image saved as
    either GIF, JPEG or PNG type.');
}

/* microsite_preloaded_images
 * returns either an array of filename -> description mappings,
 * or nothing
 * If a key is provided, returns the value for that key, or None
 * Special case: key 'exists' will return array of those images that were found 
 */
function microsite_preloaded_images($key, $pledge_microsite = NULL){
    global $microsite;
    if ($microsite == 'barnet' || $pledge_microsite == 'barnet'){
        $files = array(              // alphabetic display order, fwiw
            "adopt_a_street.jpg" => "Adopt a Street",
            "barnet_offices.jpg" => "Barnet NLBP Offices",
            "diamond_jubilee.jpg" => "Diamond Jubilee street party",
            "the_big_lunch.jpg"  => "The Big Lunch street party",
            "daisy_field.jpg"    => "Field of dog daisies",
            "frosty_flower.jpg"  => "Frosty flower",
            "frosty_mimosa.jpg"  => "Frosty mimosa",
            "hendon_library.jpg" => "Hendon library",
            "olympics2012.jpg"   => "Olympics 2012",
            "purple_flower.jpg"  => "Purple flower", 
            "royal_wedding.jpg"  => "Royal Wedding street party",
            "trees_and_leaves.jpg"=> "Trees and leaves",
            "water_lilies.jpg"   => "Water lilies",
            "yellow_flower.jpg"  => "Yellow flower",
        );
        if ($key == 'exists'){
            foreach ($files as $filename => $desc){
                if (! file_exists(OPTION_PB_PRELOADED_IMAGES_DIR . $filename)){
                    unset($files[$filename]);
                }
            }
            return $files;
        } elseif ($key){
            if (array_key_exists($key, $files)) {
                return $files[$key];                
            } else {
                return "";
            }
        } else {
          return $files;
        }
    }
    return array();
}

/* microsite_preloaded_image_url()
 * args: filename of image
 * Returns; URL of preloaded image 
 */
function microsite_preloaded_image_url($filename){
    return rtrim(OPTION_PB_PRELOADED_IMAGES_URL, '/'). '/' . $filename;
}

function microsite_preloaded_image_select($pledge_microsite = NULL){
    $html = "";
    $images_available = microsite_preloaded_images('exists', $pledge_microsite);
    if (count($images_available)>0){
        $html ='<select name="preloaded_image" id="preload-select"><option value="0"> </option>';
        foreach ($images_available as $filename => $desc) {
            $html .= "<option value='$filename'>$desc</option>\n";
        }
        $html .= '</select>';
    }
    return $html;
}

/* microsite_picture_extra_form()
 * Adds an extra form to the picture upload form -- specifically, for Barnet, adds the preloaded images input
 */
function microsite_picture_extra_form(){
    global $microsite;
    $html = "";
    if ($microsite == 'barnet'){
        $images_available = microsite_preloaded_images('exists');
        if (count($images_available)>0){
            $html ='<p style="padding-top:1em;">If you don\'t have a suitable image of your own, you can choose one of the pre-loaded images instead.</p>'
                    . '<label for="preloaded_image">Pre-loaded images</label>'
                    . microsite_preloaded_image_select()
                    . '<div style="clear:both"></div>';
        }
    }
    return $html;
}

function microsites_pledge_created_message($pledge){
    global $microsite;
    if ($microsite == 'barnet'){
      return microsite_picture_upload_advice() . 
          _("A default image will be used until you provide a picture.") . 
          sprintf("<a href='%s'>%s</a>.", $pledge->url_picture(), _("Click here to add a picture"));  
    };    
    return "";
}

# Whether a site has local alerts at all!
function microsites_local_alerts() {
    global $microsite;
    if ($microsite == 'rbwm'){
        return false;
    }
    return true;
}

/* microsites_default_location
 * returns a default location for new pledges -- user can override this.
 */
function microsites_default_location() {
    global $microsite;
    if ($microsite == 'barnet'){
        return array('local'=> 1, 'place'=> 'Barnet', 'country' => 'GB', 'state' => '', 'gaze_place' => '', 'postcode' => '', 'places' => null);
    }
    if ($microsite == 'rbwm'){
        return array('local'=> 1, 'place'=> 'Windsor', 'country' => 'GB', 'state' => '', 'gaze_place' => '', 'postcode' => '', 'places' => null);
    }
    return array(); /* empty array, not null */
}


/* microsites_new_pledges_toptips
 * Tips on making a pledge that will work, for top of new pledge page.
 */
function microsites_new_pledges_toptips() {
    print '<div id="tips">';
    print microsites_toptips_normal();
    print '</div>';
}

function microsites_toptips_normal() {
    global $site_country;
    global $microsite;
    $percent_successful_above_100 = percent_success_above(100);
    $out = '<h2>' . _('Top Tips for Successful Pledges') . '</h2>';
    $out .= '<ol>';
    $out .= '<li>' . sprintf(_('<strong>Keep your ambitions modest</strong> &mdash; why ask for 50 people
        to do something when 5 would be enough? Every extra person makes your pledge
        harder to meet. Only %0.0f%% of pledges asking for more than 100 people succeed.'), $percent_successful_above_100) . '</li>';
    $out .= '<li>' . _("<strong>Get ready to sell your pledge, hard</strong>. Pledges don't
        sell themselves just by sitting on this site. In fact your pledge won't even
        appear to general site visitors until you've got a few people to sign up to it
        yourself. Think hard about whether people you know would want to sign up to
        your pledge!") . '</li>';
    $out .= '<li>' . _("<strong>Think about how your pledge reads.</strong> How will it look to
        someone who picks up a flyer from their doormat? Read your pledge to the person
        next to you, or to your mother, and see if they understand what you're talking
        about. If they don't, you need to rewrite it.") . '</li>';
    /* $out .= '<li>' . _("<strong>Search first</strong>. Enter a keyword or two related to your pledge
in the search box in the top righthand corner. If there's already a pledge related to yours, consider
joining in on that pledge.") . '</li>'; */
    if ($microsite == 'barnet'){
        $out .= '<li>' . _("<strong>You can add a picture when the pledge has been created</strong>.
            You can upload your own, or choose from the pre-loaded images that are already the right
            size for the Barnet page design.") . '</li>';
    } else {
        $out .= '<li>' . _("<strong>A picture &ndash; or audio clip, or video &ndash; is worth a thousand
            words</strong>. You can add a picture to your pledge once you've created it, or consider including a
            link in your pledge to a picture, audio, or video if you have one.") . '</li>';
    }
    /* if ($site_country == 'US') {
        $out .= '<li>' . "If your pledge is about raising money and you want people to be able to
donate straight away, think about using <a href=\"http://www.changingthepresent.org/PledgeBank\">Changing
the Present</a> if you're giving to a registered non-profit or <a href=\"http://www.chipin.com/\">ChipIn</a>
if you're raising money for something else. Pledges are more successful when signers can process a donation
online, rather than sending a check." . '</li>';
    } */
    $out .= '</ol>';
    return $out;
}

/* microsites_pledge_closed_text
 * Text to display in red box when a pledge is closed.
 */
function microsites_pledge_closed_text() {
    return strong(_('This pledge is now closed, as its deadline has passed.'));
}

/* microsites_signup_extra_fields
 * Add any extra input fields or text to the pledge signup box.
 */
function microsites_signup_extra_fields($errors) {
}

/* microsites_signup_extra_fields_validate
 * Validate the values of any extra fields during signup.
 */
function microsites_signup_extra_fields_validate(&$errors) {
}

function microsites_new_pledges_terms_and_conditions($data, $v, $local, $errors) {
    $P = person_if_signed_on();
    if (!$P) {
        print p(_('Do you have a PledgeBank password?'));
        print '<p><input type="radio" name="loginradio" id="loginradio2"> <label for="password">' . _('Yes, please enter it:') . '</label> <input type="password" name="password" id="password" value=""
onchange="check_login_password_radio()" onfocus="check_login_password_radio()"></p>
<p id="email_row"><input type="radio" name="loginradio" id="loginradio1"> <label for="loginradio1">' . _('No, or you&rsquo;ve forgotten it') . '</label>'.
    '<p id="email_blurb"><small>&mdash; ' . _('we&rsquo;ll send you a confirmation email instead.') . '</small>';
    }
?>
<p style="text-align: right;">
<input id="next_step" type="submit" name="tocreate" value="<?=_('Create pledge') ?>">
</p>
<?
    print '<p><small>' . _('You confirm that you wish PledgeBank to display the pledge in your name, and that you agree to the terms and conditions below.') . '</small></p>';;

    print h3(_('The Dull Terms and Conditions'));
    print '<input type="hidden" name="agreeterms" value="1">';

    print "<p>";
    if ($v == 'pin') { ?>
<!-- no special terms for private pledge -->
<?  } else {
        print _('By creating your pledge you also consent to the syndication of your pledge to other sites &mdash; this means that other people will be able to display your pledge and your name');
        if ($data['country'] == "GB" && $local) {
            print _(', and use (but not display) your postcode to locate your pledge in the right geographic area');
        }
        print '. ';
        print _('The purpose of this is simply to give your pledge
greater publicity and a greater chance of succeeding.');
        print ' ';
    }
    print _("Rest assured that we won't ever give or sell anyone your email address.");
}

#############################################################################
# Features

function microsites_location_allowed() {
    global $microsite;
    if ($microsite == 'rbwm') return false;
    return true;
}

/* microsites_denied_access_redirect_url
 * Returns a URL for microsites that need to redirect forbidden pages: 
 * specifically this is for temp. Barnet block  */
function microsites_denied_access_redirect_url() {
    global $microsite;
    if ($microsite == 'barnet'){
         /* only allow barnet login with Barnet team email, or mysociety staff ... IP restrictions too? */
        $P =  pb_person_signon(array(
                         'reason_web' => _("You need to be logged in as an authorised user to create pledges."),
                         'reason_email' => _("Then you will be able to create pledges."),
                         'reason_email_subject' => _('Create pledges at Barnet PledgeBank.')
                     ));
        if ($P) {
            $email_lc = strtolower($P->email());
            if ($email_lc=='barnet.pledgebank@barnet.gov.uk')
                return '';
            if (preg_match('/^(matthew|tom|dave)@mysociety.org$/', $email_lc))
                return '';
        }
        return OPTION_BASE_URL; 
    } 
    return '';
}

/* overrides the default pledge prefix of "I will" */
function microsites_pledge_prefix($prefix, $name) {
    global $microsite;
    if ($microsite == 'barnet'){
      if ($name == "Barnet Council"){
        return 'Barnet Council will'; /* or "We will"? */
      } else {
        return _('I will');
      }
    }
    return $prefix;
}

/* allow firstname (used in pledge->sentence()) to be overridden, conditional on specific names:
  * args: 
  *       name -- name of pledger
  *       pledge_microsite -- explicit microsite of the pledge (so this works even
  *              if we're not in a microsite environment, e.g., admin)
  * Returns a special value, if the name requires it
 */
function microsite_conditional_firstperson($name, $pledge_microsite) {
    global $microsite;
    if ($microsite == 'barnet' || $pledge_microsite == 'barnet') {
        if ($name == "Barnet Council") {
            return 'onlyname';
        } else {
            return 'includename';
        }
    }
    return "";
}

/* microsites_private_allowed
 * Returns whether private pledges are offered in new pledge dialog. */
function microsites_private_allowed() {
    global $microsite;
    if ($microsite == 'rbwm') return false;
    if ($microsite == 'barnet') return false;
    return true;
}

/* microsites_categories_allowed
 * Returns whether categories are used for this microsite at all */
function microsites_categories_allowed() {
    global $microsite;
    if ($microsite == 'barnet') return false;
    if ($microsite == 'rbwm') return false;
    return true;
}

/* microsites_categories_page3
 * Returns whether categories are offered in the usual place in the new pledge dialog. */
function microsites_categories_page3() {
    if (!microsites_categories_allowed()) return false;
    return true;
}

/* microsites_postal_address_allowed
 * Returns whether the creator's postal address is asked for in new pledge
 * dialog. */
function microsites_postal_address_allowed() {
    return false;
}

/* For displaying the address fetching page (LiveSimply and O2 only) */
function microsites_new_pledges_stepaddr($data, $errors) {
?>
<p>Please take a moment to fill in this form. It's not obligatory but the
information you provide us will help us in evaluating the success of the
<em>live</em>simply challenge.

<p><strong><?=_('Your address:') ?></strong> 
<br><input<? if (array_key_exists('address_1', $errors)) print ' class="error"' ?> type="text" name="address_1" id="address_1" value="<? if (isset($data['address_1'])) print htmlspecialchars($data['address_1']) ?>" size="30">
<br><input<? if (array_key_exists('address_2', $errors)) print ' class="error"' ?> type="text" name="address_2" id="address_2" value="<? if (isset($data['address_2'])) print htmlspecialchars($data['address_2']) ?>" size="30">
<br><input<? if (array_key_exists('address_3', $errors)) print ' class="error"' ?> type="text" name="address_3" id="address_3" value="<? if (isset($data['address_3'])) print htmlspecialchars($data['address_3']) ?>" size="30">
<br><strong><?=_('Town:') ?></strong> 
<br><input<? if (array_key_exists('address_town', $errors)) print ' class="error"' ?> type="text" name="address_town" id="address_town" value="<? if (isset($data['address_town'])) print htmlspecialchars($data['address_town']) ?>" size="20">
<br><strong><?=_('County:') ?></strong> 
<br><input<? if (array_key_exists('address_county', $errors)) print ' class="error"' ?> type="text" name="address_county" id="address_county" value="<? if (isset($data['address_county'])) print htmlspecialchars($data['address_county']) ?>" size="20">
<br><strong><?=_('Postcode:') ?></strong> 
<br><input<? if (array_key_exists('address_postcode', $errors)) print ' class="error"' ?> type="text" name="address_postcode" id="address_postcode" value="<? if (isset($data['address_postcode'])) print htmlspecialchars($data['address_postcode']) ?>" size="20">
<br><strong><?=_('Country:') ?></strong> 
<br><? 
    gaze_controls_print_country_choice(microsites_site_country(), null, $errors, array('noglobal'=>true, 'fieldname' => 'address_country')); ?>
</p>

<?
}

/* microsites_new_pledges_prominence
 * Returns prominence that new pledges have by default.
 */
function microsites_new_pledges_prominence() {
    return 'calculated';
}

/* microsites_other_people 
 * Returns text to describe other people by default when making
 * a new pledge.
 */
function microsites_other_people() {
    global $microsite;
    if ($microsite == 'catcomm')
        return 'other CatComm supporters'; // deliberately not translated
    else
        return _('other local people');
}

/* microsites_comments_allowed
 * Whether or not comments are displayed for the microsite.
 */
function microsites_comments_allowed() {
    return true;
}

function microsites_send_to_signers() { # set to false to never send auto emails to signers
    global $microsite;
    if ($microsite == 'barnet' || OPTION_WEB_DOMAIN == 'barnet.gov.uk') { # use OPTION, since this is called from scripts
        return false;
    }
    return true;
}


/* microsites_chivvy_sql
 * SQL fragment for whether a pledge gets any chivvy emails.
 */
function microsites_chivvy_sql() {
    return "microsite is null or (microsite <> 'livesimply' and microsite <> 'o2' and microsite <> 'barnet')";
}

/* microsites_email_subject_by_topic
 * returns subject for email based on topic 
 * subject is returned unchanged if there's no topic
 */
function microsites_email_subject_by_topic($topic, $subject) {
    global $microsite;
    if ($microsite == 'barnet'){
        if (microsites_get_pledge_type_details($topic, 'is_valid')) {
            return 'Barnet PledgeBank: ' . microsites_get_pledge_type_details($topic, 'title') . ' request';
        } else {
            return 'Barnet PledgeBank suggestion';
        }        
    }
    return $subject;
}        

/* microsites_email_message_body_by_topic
 * returns message for email based on topic (or passes message back unchanged if there is no work to be done)
 */
function microsites_email_message_body_by_topic($topic, $message, $name, $email, $custom_field) {
    global $microsite;
    if ($microsite == 'barnet'){
        if ($message && microsites_get_pledge_type_details($topic, 'is_valid')) {
            $pledge_details = microsites_get_pledge_type_details($topic);
            $topicTitle = $pledge_details['title'];
            $topicAction = $pledge_details['action'];            
            $url_for_new_pledge = OPTION_PB_FIXED_SITE_URL . "/new?new_pledge_type=$topic";
            $url_for_logout = OPTION_PB_FIXED_SITE_URL . "/logout";
            # number: arbitrary limit, to prevent bloated URLs: this is typically just a street name, after all
            if (strlen($message) < 64 && preg_match("/\w/", $message)) { 
                $url_for_new_pledge .= "&ref=" . urlencode(trim($message));
            }
            $custom_field_str = "(not provided)";
            if ($pledge_details['use_custom_field']) {
                $custom_field_str = $pledge_details['custom_field_name'] . ": " 
                    . ($custom_field? $custom_field : "(not provided)");
            }
            $indefinite_article = preg_match("/^[aeiou]/i", $topicTitle)? "an" : "a"; 
            $summary = sprintf($pledge_details["summary_f"], $topicAction, $message);
            $message = "

Request for $indefinite_article $topicTitle pledge for: \"$message\".

Submitted by: $name ($email)
$custom_field_str

If there's not already $indefinite_article $topicTitle pledge in this area, please make one!
$url_for_new_pledge

Note: if you don't see a \"new pledge\" form there, you're probably not logged in with the PledgeBank team account: click on $url_for_logout first.

You can reply to $name at $email with one of these two templated emails:




- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
Hello $name

Thank you for stepping up to the mark to try to $summary.

We've made a new pledge for you here: 

http://pledgebank.barnet.gov.uk/FILL_IN_NAME_OF_PLEDGE

Please sign up to that pledge and share that with as many of your neighbours as you can!

Best wishes
Barnet Council PledgeBank team
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

  or

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
Hello $name

Thank you for volunteering to $summary.

We're pleased to tell you such a pledge has already been started:

http://pledgebank.barnet.gov.uk/FILL_IN_NAME_OF_PLEDGE

Please sign up to that pledge and share that with as many of your neighbours as you can!

Best wishes
Barnet Council PledgeBank team
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
";
        }
    }
    return $message;
}

/* microsites_email_errors_by_topic
 * returns error message for blank email field based on topic 
 * (or passes error message back unchanged if there is none)
 * 
 * Currently this is only used for Barnet's pledge_types, where 
 * the message payload is actually the street name
 */
function microsites_email_error_msg_by_topic($topic, $subject, $error_message) {
    global $microsite;
    if ($microsite == 'barnet'){
        if (microsites_get_pledge_type_details($topic, 'is_valid') && $subject == 'message') {
            return microsites_get_pledge_type_details($topic, 'ref_error_msg'); 
        }
    }
    return $error_message;
}

/* microsites_email_send_from_users_address
 * returns true if contact-us emails should use the user's email address as the From: address
 *  
 * Normally this is what you want, so staff can simply hit reply.
 * But if the message isn't a simple contact (e.g., Barnet's RoyalWedding) we can suppress this, to 
 * discouarge accidental replies that quote the full message.
 */
function microsites_email_send_from_users_address($topic) {
    global $microsite;
    if ($microsite == 'barnet' && microsites_get_pledge_type_details($topic, 'is_valid')) return false;
    return true;
}

#############################################################################
# pledge_type stuff
# It's increasingly clear this should really be in the database, but for
# now just hard-coding it so we can see how it pans out. Furthermore, 
# pledge_type isn't really a microsite issue, but the fact is that it is only 
# being used on the Barnet microsite right now.
#----------------------------------------------------------------------------
# microsites_get_pledge_type_details
# returns customisable things that depend on pledge_type, either as a complete
# hash (if no key is provided) or as a single value if a key is provided.
#
# Note i:  preloaded_data is itself a hash: keys are the http query vars that
#          will be populated by the values; "%s" is special in there and will
#          be replaced with the ref_in_pledge_type (e.g., the street name) if
#          it is available.
# Note ii: beware name clash: $details['title'] != the title in $details['preloaded_data']

function microsites_get_pledge_type_details($pledge_type, $key=null, $secondary_key=null) {
    $details = null;
    global $microsite;
    $defaults = array(
                              # note: we could run  
                              # is_valid => microsites_valid_custom_pledge_type($pledge_type)
                              # which tests for the presence of the template, but seems a bit heavy-handed
        "is_valid"         => true,
        "use_custom_field" => true,
        "custom_mandatory" => false, # not used, but should be
        "custom_field_name"=> "Phone number",
        "custom_label"     => "Your phone number",
        "custom_note"      => "(optional, but it’s really handy if we can call you too)",
        "preloaded_data"   => null,
        "ref_label"        => "Your street",
        "ref_error_msg"    => "Please enter the name of your street",
        "ref_note"         => "(it helps us if you include your postcode)",
        "default_image_url"=> "",
        "summary_f"        => "%s (%s)" # sprintf(this, action, message) -- clumsy way of displaying "Street Party in Acacia Avenue"
    );
    if ($microsite == 'barnet') {
        switch ($pledge_type) {
            case "adoptastreet":
                $details = array_merge($defaults, array(
                    "title"     => "Adopt-a-Street",
                    "action"    => "adopt your street",
                    "default_image_url" => microsite_preloaded_image_url('adopt_a_street.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "provide training and equipment to %s",
                        "target" => 3,
                        "type"   => "residents",
                        "signup" => "agree to adopt their street"
                    )
                ));
                break;
            case "biglunch2013":
                $details = array_merge($defaults, array(
                    "title"     => "The Big Lunch Street Party 2013",
                    "action"    => "organise a Big Lunch street party",
                    "default_image_url" => microsite_preloaded_image_url('the_big_lunch.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "arrange free public liability insurance for a Big Lunch street party in %s",
                        "target" => 3,
                        "type"   => "other households",
                        "signup" => "volunteer to organise the party",
                        "detail" => "Please note:\nIf you agree to become a volunteer, we will automatically share your contact " .
                                    "details with other participants in your street."
                    )
                ));
                break;

            case "diamondjubilee":
                $details = array_merge($defaults, array(
                    "title"     => "Diamond Jubilee Street Party",
                    "action"    => "organise a Diamond Jubilee street party",
                    "default_image_url" => microsite_preloaded_image_url('diamond_jubilee.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "arrange free public liability insurance for a Diamond Jubilee street party in %s",
                        "target" => 3,
                        "type"   => "other households",
                        "signup" => "volunteer to organise the party",
                        "detail" => "Please note:\nIf you agree to become a volunteer, we will automatically share your contact " .
                                    "details with other participants in your street."
                    )
                ));
                break;
            case "diamondparkparty":
                $details = array_merge($defaults, array(
                    "title"     => "Diamond Jubilee party in the Park",
                    "action"    => "organise a Diamond Jubnilee party in the park",
                    "ref_label" => "The park",
                    "ref_note"  => "(it helps us if you include the park's postcode, but don't worry if you don't know it)",
                    "ref_error_msg" => "Please enter the name of the park you want to use",
                    "default_image_url" => microsite_preloaded_image_url('trees_and_leaves.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "arrange free public liability insurance for a Diamond Jubilee/Olympics party in %s,",
                        "target" => 3,
                        "type"   => "households",
                        "signup" => "volunteer to organise the party",
                        "detail" => "Please note:\nIf you agree to become a volunteer, we will automatically share your contact " .
                                    "details with other participants in your street.",
                        "date"   => "13 July 2012"
                    )
                ));
                break;
            case "grit_my_school":
            case "gritmyschool2012":
            case "gritmyschool2013":
                
                $gritting_year = preg_match("/(20\d\d)$/i", $pledge_type, $year_found)? $year_found[0] : '2011';
                $details = array_merge($defaults, array(
                    "title"     => "Grit My School " . $gritting_year,
                    "action"    => "grit your school",
                    "ref_label" => "Your school",
                    "ref_note"  => "(it helps us if you include the school's postcode, but don't worry if you don't know it)",
                    "ref_error_msg" => "Please enter the name of your school",
                    "default_image_url" => microsite_preloaded_image_url('frosty_mimosa.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "provide grit, spreading equipment and public liability insurance (where needed) to %s",
                        "target" => 4,
                        "type"   => "volunteers",
                        "signup" => "agree to spread the grit",
                        "detail" => "To apply for grit for your school you will need:\n\n * Enough space on your" .
                                     "school (approximately 1.5m cubed in size). If you do not have the appropriate" .
                                     "storage space, we can put you in touch with the other volunteers that have" .
                                     "signed up to your pledge for you to check if they can store the grit" .
                                     "supplies.\n\n * To share your contact details with other volunteers. If you" .
                                     "agree to become a Community Grit Keeper or volunteer, we will automatically" .
                                     "share your contact details that with other participants in your school.\n\n *" .
                                     "To use your grit supplies on public land only. If you are requesting to become" .
                                     "a Community Grit Keeper, you need to ensure that you are pledging to grit" .
                                     "public land, i.e. public roads or footways, NOT playgrounds.\n\n * Insurance -" .
                                     "Schools should note that they must cover all volunteers under their own public" .
                                     "liability insurance\n\nThis offer is limited to one delivery of grit per" .
                                     "school. We cannot supply refills."
                    )
                ));
                break;
            case "grit_my_street":
            case "gritmystreet2012":
                $details = array_merge($defaults, array(
                    "title"     => "Grit My Street" . ($pledge_type == 'grit_my_street'? '2011':'2012'),
                    "action"    => "grit your street",
                    "default_image_url" => microsite_preloaded_image_url('frosty_flower.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "provide grit, spreading equipment and public liability insurance (where needed) to %s",
                        "target" => 4,
                        "type"   => "residents",
                        "signup" => "agree to spread the grit",
                        "detail" => "To apply for grit for your street or school you will need:\n\n* To share your contact details " .
                                     "with other volunteers.\nIf you agree to become a Community Grit Keeper or volunteer, we will " .
                                     "automatically share your contact details with other participants in your street or school.\n\n" .
                                     "* Enough space on your property/school to store a one ton bag of grit (approximately 1.5m cubed " .
                                     "in size).\nIf you do not have the appropriate storage space, we can put you in touch with the " .
                                     "other volunteers that have signed up to this pledge for you to check if they can store the grit " .
                                     "supplies.\n\n* To use your grit supplies on public land only.\n If you are requesting to become " .
                                     "a Community Grit Keeper, you need to ensure that you are pledging to grit public land, i.e. " .
                                     "public roads or footways. If you live on a private road you are not eligible to sign up to this " .
                                     "pledge but you can buy grit supplies from the council\'s Highways Team by calling 020 8359 7200.\n\n" .
                                     "* Insurance\nAny resident who pledges to spread grit on a public residential street will benefit " .
                                     "from the council\'s public liability insurance policy as if they are a volunteer. Schools should " .
                                     "note that they must cover all volunteers under their own public liability insurance."
                    )
                ));
                break;
            case "olympicparkparty":
                $details = array_merge($defaults, array(
                    "title"     => "London Olympic 2012 Party in the Park",
                    "action"    => "organise a London Olympic 2012 party in the park",
                    "ref_label" => "The park",
                    "ref_note"  => "(it helps us if you include the park's postcode, but don't worry if you don't know it)",
                    "ref_error_msg" => "Please enter the name of the park you want to use",
                    "default_image_url" => microsite_preloaded_image_url('trees_and_leaves.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "arrange free public liability insurance for a Diamond Jubilee/Olympics party in %s,",
                        "target" => 3,
                        "type"   => "households",
                        "signup" => "volunteer to organise the party",
                        "detail" => "Please note:\nIf you agree to become a volunteer, we will automatically share your contact " .
                                    "details with other participants in your street.",
                        "date"   => "13 July 2012"
                    )
                ));
                break;
            case "olympics2012":
                $details = array_merge($defaults, array(
                    "title"     => "London Olympic 2012 Street Party",
                    "action"    => "organise a London Olympic street party",
                    "default_image_url" => microsite_preloaded_image_url('olympics2012.jpg'),
                    "preloaded_data" => array(
                        "name"   => "Barnet Council",
                        "title"  => "arrange free public liability insurance for an Olympic street party in %s,",
                        "target" => 3,
                        "type"   => "households",
                        "signup" => "volunteer to organise the party",
                        "detail" => "Please note:\nIf you agree to become a volunteer, we will automatically share your contact " .
                                    "details with other participants in your street.",
                        "date"   => "13 July 2012"
                    )
                ));
                break;
            case "thebiglunch":
                $details = array_merge($defaults, array(
                    "title"     => "The Big Lunch Street Party",
                    "action"    => "organise a Big Lunch street party",
                    "summary_f" => "%s in %s"
                ));
                break;
            case "royalwedding":
                $details = array_merge($defaults, array(
                    "title"     => "Royal Wedding Street Party",
                    "action"    => "organise a Royal Wedding street party",
                    "summary_f" => "%s in %s"
                ));
                break;
        }
    }
    if ($key) { 
      if (is_array($details)) {
        if ($secondary_key and is_array($details[$key])) {
          if (array_key_exists($secondary_key, $details[$key])) {
            return $details[$key][$secondary_key];
          }
        } elseif (array_key_exists($key, $details)) {
          return $details[$key];
        }
      }
      // Policy here: if the key cannot be found, return null (rather than the whole array if it exists)
      return null; 
    } else {
      return $details; //note this may be an array (of pledge types) in which case caller must *not* provide a key
    }
}

/* microsite_contact_title
 * return the page title for the contact page (needed for pledge-type pages, because they are
 * really hijacking the contact page)
 */
function microsite_contact_title($pledge_type) {
  $contact_title = '';
  global $microsite;
  if ($microsite == 'barnet') {
    if (microsites_get_pledge_type_details($pledge_type, 'is_valid')) {
      $contact_title = microsites_get_pledge_type_details($pledge_type, 'title');
    }
  }
  if (!$contact_title) {
    $contact_title = _("Contact Us");
  }
  return($contact_title);
}


#############################################################################
# Pledge indices

/* microsites_filter_main
 * Criteria for most important pledges to show on front page / list pages.
 */
function microsites_filter_main(&$sql_params) {
    global $microsite;
    if (!$microsite)
        die("Internal error, microsites_filter_main should only be called for microsite");

    if ($microsite == 'everywhere')
        return "(1=1)";
    $sql_params[] = $microsite;
    return "(microsite = ?)";
}

/* microsites_filter_general
 * Criteria for pledges to show on list pages, in addition to the 
 * microsites_filter_main ones above
 */
function microsites_filter_general(&$sql_params) {
    global $microsite;
    if (!$microsite)
        die("Internal error, microsites_filter_general should only be called for microsite");

    return "(1=0)";
}

/* microsites_filter_foreign
 * Criteria for other pledges to show, if there aren't enough main/general
 * ones for the front page to look busy. */
function microsites_filter_foreign(&$sql_params) {
    global $microsite;
    if (!$microsite)
        die("Internal error, microsites_filter_foreign should only be called for microsite");

    if ($microsite == 'everywhere')
        return "(1=0)";
    $sql_params[] = $microsite;
    return "(microsite <> ? or microsite is null)";
}

/* microsites_normal_prominences
 * Returns SQL fragment which selects normal prominence pledges. Normally
 * this is just 'normal', but for some microsites may want to include 'backpage'.
 */
function microsites_normal_prominences() {
    global $microsite;
    if (($microsite == 'catcomm') || ($microsite == 'barnet'))
        return " (cached_prominence = 'normal' or cached_prominence = 'backpage') ";
    return " (cached_prominence = 'normal') ";
}

/* microsites_list_views
 * Return an array of views available on the All Pledges page. Array
 * is a dictionary with keys the names of pages in list.php, and values the
 * text to describe them as.
 */
function microsites_list_views() {
    return array('open'=>_('Pledges which need signers'), 'succeeded_open'=>_('Successful open pledges'), 
        'succeeded_closed'=>_('Successful closed pledges'), 'failed' => _('Failed pledges'));
}

# Valid sort options for the All Pledges page
function microsites_list_sort_options() {
    $sort = array(
        'creationtime' => _('Start date'), 
        'date'=>_('Deadline')
    );
    if (!microsites_no_target())
        $sort['percentcomplete'] = _('Percent signed');
    if (microsites_categories_allowed())
        $sort['category'] = _('Category');
    if (microsites_sort_by_signers())
        $sort['signers'] = 'Signers';
    return $sort;
}

#############################################################################
# Login - some microsites get authentication from other sites

/* Person object, stores logged in user who is externally authenticated */
$microsites_external_auth_person = null;

/* microsites_read_external_auth
 * Peform any authentication for microsite. Should create appropriate person
 * record in the database using person_get_or_create, if the authentication
 * succeeds. And store the person object in $microsites_external_auth_person.
 * Return true if authentication is overriden for this microsite.
 * Return false if normal PledgeBank should be used if external one fails.
 */ 
function microsites_read_external_auth() {
    global $microsites_external_auth_person;

    if ($microsites_external_auth_person)
        return true;

    // Use normal authentication
    return false;
}

/* microsites_redirect_external_login
 * Return true if auth has been redirected to elsewhere.
 * Return false if normal auth is to be used.*/
function microsites_redirect_external_login() {
    return false;
}

/* microsites_invalid_email_address
 * Returns whether an email address is valid to use for the microsite or not.
 * Return false if it is valid, or a string containing an error message for the
 * user if it is invalid.
 */
function microsites_invalid_email_address($email) {
    return false;
}

# Display the More Details box during pledge creation
function microsites_new_pledges_detail_textarea($data) {
    $detail = isset($data['detail']) ? htmlspecialchars($data['detail']) : '';
    return '<textarea name="detail" rows="10" cols="40">' . $detail . '</textarea>';
}

# Extra checks for step 1 of pledge creation for microsites;
function microsites_step1_error_check($data) {
    $error = array();
    if ($email_err = microsites_invalid_email_address($data['email']))
        $error['email'] = $email_err;
    return $error;
}

# For displaying extra bits on the preview pledge page
function microsites_new_pledges_preview_extras($data) {
    return "";
}

/* microsites_change_microsite_allowed
 * Returns whether or not you should display the "change/choose site"
 * links around the place.
 */
function microsites_change_microsite_allowed() {
    global $microsite;
    if ($microsite == 'barnet') return false;
    if ($microsite == 'rbwm') return false;
    return true;
}

/* microsites_show_translate_blurb()
 * Returns whether or not we should display the available languages
 * and "translate into your own language" at the bottom of every page.
 */
function microsites_show_translate_blurb() {
    global $microsite;
    if ($microsite == 'barnet') return false;
    if ($microsite == 'rbwm') return false;
    return true;
}

/* microsites_show_alert_advert()
 * Returns whether or not we should display a HFYMP advert when you've
 * just signed up for an email alert
 */
function microsites_show_alert_advert() {
    global $microsite;
    if ($microsite == 'barnet') return false;
    if ($microsite == 'rbwm') return false;
    return true;
} 

/* microsites_show_area()
 * arg: pledge_microsite -- pledge's microsite value passed in so this works in admin
 */
function microsites_show_area($pledge_microsite) {
    global $microsite;
    if ($microsite == 'barnet') return false;
    if ($microsite == 'rbwm') return false;
    return true;    
}

/* microsites_sort_by_signers
 * If a microsite has an extra sort-by-signers option on list pages
 */
function microsites_sort_by_signers() {
    return false;
}

/* For if a microsite has a special example date on the new pledge page
 */
function microsites_example_date() {
    global $pb_time, $lang;
    print '"';
    if ($lang=='en-gb')
        print date('jS F Y', $pb_time+60*60*24*28); // 28 days
    elseif ($lang=='eo')
        print strftime('la %e-a de %B %Y', $pb_time+60*60*24*28);
    elseif ($lang=='de' || $lang=='sk')
        print strftime('%e. %B %Y', $pb_time+60*60*24*28);
    elseif ($lang=='zh')
        print strftime('%Y&#24180;%m&#26376;%d&#26085;', $pb_time+60*60*24*28);
    else
        print strftime('%e %B %Y', $pb_time+60*60*24*28);
    print '"';
}

# Return true if all target functionality should be disabled
function microsites_no_target() {
    return false;
}

# Return true if microsite has SMS at all
# NB: you will also need to edit web/poster.cgi
function microsites_has_sms() {
    global $microsite;
    if ($microsite == 'barnet') return false;
    if ($microsite == 'rbwm') return false;
    return true;
}

# Return true if microsite has flyers
function microsites_has_flyers() {
    return true;
}

# Help for blank searches
function microsites_search_help() {
    print p(_('You can search for:'));
    print "<ul>";
    print li(_("The name of a <strong>town or city</strong> near you, to find pledges in your area"));
    if (!microsites_site_country() || microsites_site_country() == 'GB')
        print li(_("A <strong>postcode</strong> or postcode area, if you are in the United Kingdom"));
    print li(_("<strong>Any words</strong>, to find pledges and comments containing those words"));
    print li(_("The name of <strong>a person</strong>, to find pledges they made or signed publicly"));
    print "</ul>";
}

function microsites_has_survey() {
    return true;
}

function microsites_new_breadcrumbs($page) {

    $steps = [];
    $steps[] = [
        'step' => 'basics',
        'text' => _('Basics')
    ];
    if (microsites_location_allowed())
        $steps[] = [
            'step' => 'location',
            'text' => _('Location')
        ];
    if (microsites_private_allowed()) {
        $steps[] = [
            'step' => 'category',
            'text' => _('Category/Privacy')
        ];
    } elseif (microsites_categories_allowed()) {
        $steps[] = [
            'step' => 'category',
            'text' => _('Category'),
        ];
    }
    if (microsites_postal_address_allowed())
        $steps[] = [
            'step' => 'address',
            'text' => _('Address')
        ];
    $steps[] = [
        'step' => 'preview',
        'text' => _('Preview')
    ];

    $str = '<ol id="breadcrumbs">';
    $num = 1;
    foreach ($steps as $item) {
        $li = '<!--[if lte IE 6]>' . ($num++) . '. <![endif]-->' .
              htmlspecialchars($item['text']);

        if ($item['step'] == $page) {
            $str .= sprintf('<li class="hilight"><em> %s </em></li>', $li);
        }
        else {
            $str .= sprintf('<li> %s </li>', $li);
        }
    }
    $str .= "</ol>";
    print $str;
}

function microsites_custom_pledge_template_path($topic) {
  if (preg_match("/^[_a-zA-Z0-9-]+$/i", $topic) && OPTION_PB_CUSTOM_TYPE_TEMPLATES_DIR) {
    return OPTION_PB_CUSTOM_TYPE_TEMPLATES_DIR . $topic . ".php";
  }
  return '';
}

# whether or not this is an attempt to create a custom pledge type, and if that
# pledge type is supported by this installation
# returns null if not, otherwise returns the pledge type
function microsites_valid_custom_pledge_type($pledge_type) {
  if ($pledge_type) {
    $pledge_type_canonical = preg_replace("/[^-a-z0-9_]/", '', strtolower($pledge_type));
    if (is_readable(microsites_custom_pledge_template_path($pledge_type_canonical))) {
      return $pledge_type_canonical;
    }
  }
  return null;
}

# returns list of valid pledge_types as an array (might be empty if there are none)
# or null if this option is not enabled for this site
# A 'valid pledge type' is (currently) simply one for which there is a XXX.php template in the designated directory
function microsites_get_custom_pledge_types() {
  $pledge_types = null;
  if (OPTION_PB_CUSTOM_TYPE_TEMPLATES_DIR) {
    $pledge_types = array();
    $files = scandir(OPTION_PB_CUSTOM_TYPE_TEMPLATES_DIR);
    if ($files) {
      # in later version of PHP, could use pgrep_filter
      $pledge_types = preg_grep("/^[-a-z0-9_]+\.php$/", $files);
      $pledge_types = preg_replace("/\.php$/", "", $pledge_types );      
    }
  }
  return $pledge_types;    
}

function microsites_site_name() {
    global $microsite;
    if ($microsite == 'barnet') return 'Barnet PledgeBank';
    if ($microsite == 'rbwm') return 'RBWM PledgeBank';
    return _('PledgeBank.com');
}

function microsites_show_banner() {
    global $microsite;
    if (! $microsite) {
        return true;
    }
    if ($microsite == 'rbwm') return true;
    return false;
}


function microsites_banner_source() {
    global $microsite, $lang;

    if ($microsite == 'rbwm') return 'howitworks_rbwm.png';

    if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr' || $lang == 'sk')
        return 'howitworks_' . $lang . '.png';
    return 'howitworks.png';
}

function microsites_no_success_stories() {
    global $microsite;
    if ($microsite == 'rbwm') return true;
    return OPTION_NO_SUCCESS_STORIES;;
}

function microsites_comments_on_new_row() {
    global $microsite;
    if ($microsite == 'rbwm') return true;
    return OPTION_COMMENTS_ON_NEW_ROW;
}
