<?php
/*
 * pledge.php:
 * Logic for pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pledge.php,v 1.237 2007-07-16 15:02:28 francis Exp $
 * 
 */

require_once 'fns.php';
require_once 'gaze-controls.php'; // for sign_box
require_once 'pbperson.php';

require_once '../../phplib/db.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/rabx.php';

class Pledge {
    // Associative array of parameters about the pledge, taken from database
    var $data;
    // Escaped ref used for URLs
    var $h_ref;

    // Construct from either:
    // - string, a PledgeBank reference
    // - integer, the internal id from the pledges table
    // - array, a dictionary of data about the pledge
    function Pledge($ref) {
        global $pb_today;
        $main_query_part = "SELECT pledges.*, 
                               '$pb_today' <= pledges.date AS open,
                               pledges.date - '$pb_today' AS daysleft,
                               (SELECT count(*) FROM signers WHERE 
                                    signers.pledge_id = pledges.id) AS signers,
                                person.email AS email,
                               country, state, description, method, latitude, longitude
                           FROM pledges
                           LEFT JOIN person ON person.id = pledges.person_id 
                           LEFT JOIN location ON location.id = pledges.location_id";

        if (gettype($ref) == "integer" or (gettype($ref) == "string" and preg_match('/^[1-9]\d*$/', $ref))) {
            $q = db_query("$main_query_part WHERE pledges.id = ?", array($ref));
            if (!db_num_rows($q))
                err(_('PledgeBank reference not known'));
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "string") {
            $q = db_query("$main_query_part WHERE lower(ref) = ?", array(strtolower($ref)));
            if (!db_num_rows($q)) {
                err(_('We couldn\'t find that pledge.  Please check the URL again carefully.  Alternatively, try the search at the top right.'));
            }
            $this->data = db_fetch_array($q);
        } elseif (gettype($ref) == "array") {
            $this->data = $ref;
        } else {
            err("Unknown type '" . gettype($ref) . "' to Pledge constructor");
        }

        $this->_calc();
    }

    /* lock
     * Lock a pledge in the database using SELECT ... FOR UPDATE. */
    function lock() {
        if (!array_key_exists('id', $this->data))
            err(_("Pledge is not present in database"));
        else {
            /* Now we have to grab the data again, since it may have changed
             * since the constructor was called. */
            $d = db_getRow('
                        select *,
                            (select count(id) from signers
                                where signers.pledge_id = pledges.id)
                                    as signers
                        from pledges
                        where id = ?
                        for update of pledges', $this->data['id']);
            foreach ($d as $k => $v)
                $this->data[$k] = $v;
        }
    }

    // Internal function to calculate some values from data
    function _calc() {
        // Fill in partial pledges (ones being made still)
        if (!array_key_exists('signers', $this->data)) $this->data['signers'] = -1;
        if (!array_key_exists('open', $this->data)) $this->data['open'] = 't';
        if (!array_key_exists('cancelled', $this->data)) $this->data['cancelled'] = null;
        if (!array_key_exists('notice', $this->data)) $this->data['notice'] = null;
        if (array_key_exists('country', $this->data)) {
            if ($this->data['country'] == 'GB' && array_key_exists('postcode', $this->data) && $this->data['postcode']) {
                $this->data['description'] = $this->data['postcode'];
                $this->data['method'] = 'MaPit';
            } 
            if (array_key_exists('gaze_place', $this->data) && $this->data['gaze_place']) {
                list($lat, $lon, $desc) = explode('|', $this->data['gaze_place'], 3);
                $this->data['description'] = $desc;
                $this->data['method'] = 'Gaze';
            }
            if ($this->data['country'] == 'Global' || strlen($this->data['country']) == '') {
                $this->data['country'] = null;
            }
            global $countries_code_to_name;
            if (!array_key_exists($this->data['country'], $countries_code_to_name)) {
                $this->data['country'] = null;
            }
        }
        if (!array_key_exists('target_type', $this->data)) $this->data['target_type'] = 'overall';

        // Some calculations 
        $this->data['left'] = $this->data['target'] - $this->data['signers'];
        $this->data['open'] = ($this->data['open'] == 't');
        $this->h_ref = htmlspecialchars($this->data['ref']);
        global $pb_today;
        if (!array_key_exists('daysleft', $this->data))
            $this->data['daysleft'] = (strtotime($this->data['date']) - strtotime($pb_today)) / (60*60*24);

        // "Finished" means closed to new signers
        $finished = false;
        if (!$this->open())
            $finished = true;
        if ($this->is_cancelled())
            $finished = true;
        if ($this->byarea())
            $finished = !$this->open();
        $this->data['finished'] = $finished;

        // Check we know the language of the pledge, otherwise set to default
        // (page) language.
        global $lang, $langs;
        if (!array_key_exists($this->data['lang'], $langs)) {
            $this->data['lang'] = $lang;
        }
    }

    // Basic data access
    function ref() { return $this->data['ref']; }
    function id() { return $this->data['id']; }
    function open() { return $this->data['open']; } // not gone past the deadline date
    // can take no more signers, for whatever reason
    function finished() { 
        return $this->data['finished']; 
    } 

    /* succeeded PLEDGE [LOCK]
     * Has PLEDGE completed successfully? That is, has it as many signers as its
     * target? If LOCK is true then the pledge row is locked (FOR UPDATE) in the
     * query, ensuring that the value of this function will not change for the
     * remainder of this transaction. */
    function succeeded($lock = false) {
        if ($this->byarea()) {
            // Only parts of byarea pledges succeed, never the whole pledge
            return false;
        }

        // TODO: use internal data structures instead of looking up
        // this stuff again, but work out what to do with $lock
        $target = db_getOne('
                        select target
                        from pledges
                        where id = ?
                        ' . ($lock ? 'for update' : ''),
                        $this->id());
        $num = db_getOne('
                        select count(id)
                        from signers
                        where pledge_id = ?', $this->id());

        return $num >= $target;
    }

    function failed() {
        return $this->finished() && !$this->succeeded();
    }

    function byarea() { return ($this->data['target_type'] == 'byarea'); }
    function has_details() { return $this->data['detail'] ? true : false; }
    function is_cancelled() { return $this->data['cancelled'] ? true : false; }

    function target() { return $this->data['target']; }
    function signers() { return $this->data['signers']; }
    function left() { return $this->data['left']; }
    function daysleft() { return $this->data['daysleft']; }

    function byarea_successes() { 
        if (!$this->byarea())
            return null;
        if (!array_key_exists('successful_areas', $this->data)) {
            $this->data['successful_areas'] = 
                    db_getOne("SELECT count(*) FROM byarea_location 
                    WHERE byarea_location.pledge_id = ?
                    AND whensucceeded IS NOT NULL
                    ", $this->id());
        }
        return $this->data['successful_areas']; 
    }
    function byarea_signups() { 
        if (!$this->byarea())
            return null;
        if (!array_key_exists('signup_areas', $this->data)) {
            $this->data['signup_areas'] = 
                    db_getOne("SELECT count(*) FROM byarea_location 
                    WHERE byarea_location.pledge_id = ?
                    ", $this->id());
        }
        return $this->data['signup_areas']; 
    }
    // Checks if the location id is a signup location for the pledge
    function byarea_validate_location($byarea_location_id) {
        if (!$this->byarea()) {
            err("byarea_validate can only be called for byarea pledges");
        }   
        $check = db_getOne("select count(*) from byarea_location 
                where pledge_id = ? and byarea_location_id = ?",
                array($this->id(), $byarea_location_id));
        if ($check != 1) {
            err(_("byarea_location_id not valid"));
        }
    }

    function probable_will_reach() { 
        if (!array_key_exists('probable_will_reach', $this->data)) {
            $this->data['probable_will_reach'] = db_getOne("select 
                    " . pb_chivvy_probable_will_reach_clause() . "
                    from pledges where id = ?", $this->data['id']);
        }
        return $this->data['probable_will_reach'];
    }

    function creator() { return new person($this->data['person_id']); }
    function creator_email() { return $this->data['email']; }
    function creator_name() { return $this->data['name']; }
    function creator_id() { return $this->data['person_id']; }

    function creationtime() { return $this->data['creationtime']; }
    function creationdate() { return substr($this->data['creationtime'], 0, 10); }

    function date() { return $this->data['date']; }

    function pin() { return $this->data['pin']; }
    function closed_for_comments() { return $this->data['closed_for_comments'] == 't' ? true : false; }

    function title() { return $this->data['title']; }
    function type() { return $this->data['type']; }
    function lang() { return $this->data['lang']; }

    function has_picture() { return array_key_exists('picture', $this->data) && $this->data['picture']; }
    function picture_url() { return $this->data['picture']; }

    function categories() {
        $c = array();
        $q = db_query('select category_id, category.name from pledge_category, category where pledge_id = ? and category_id = category.id', $this->id());
        while ($r = db_fetch_row($q))
            $c[$r[0]] = $r[1];
        return $c;
    }

    // Basic data access for HTML display
    function h_title() { return htmlspecialchars($this->data['title']); }
    function h_name() { return htmlspecialchars($this->data['name']); }
    function h_name_and_identity() {
        return $this->h_name().
                ((isset($this->data['identity']) && $this->data['identity']) ? 
                    ', '. htmlspecialchars($this->data['identity'])
                    : '');
    }
    function h_pretty_date() { return prettify(htmlspecialchars($this->data['date'])); }

    function is_global() { return !isset($this->data['country']); }
    function country_code() { return $this->data['country']; }
    function microsite() { return $this->data['microsite']; }
    function h_country() { 
        global $countries_code_to_name;
        if (isset($this->data['country'])) {
            $country = $this->data['country'];
            if (array_key_exists('state', $this->data))
                $state = $this->data['state'];
            else
                $state = null;
            $a = array();
            if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
                list($x, $country, $state) = $a;
            $ret = htmlspecialchars($countries_code_to_name[$country]); 
            if ($state)
                $ret .= ", " . htmlspecialchars($state);
            return $ret;
        } else
            return 'Global';
    }
    function h_country_no_state() { 
        global $countries_code_to_name;
        if (isset($this->data['country']))
            return htmlspecialchars($countries_code_to_name[$this->data['country']]); 
        else
            return null;
    }

    function is_local() { return isset($this->data['description']); }
    function h_local_type() { 
        if (!array_key_exists('method', $this->data) || !$this->data['method']) return;
        if ($this->data['method'] == 'Gaze') {
            return _("Place");
        } elseif ($this->data['method'] == 'MaPit') {
            return _("Postcode area");
        } else {
            err('Unknown method');
        }
    }
    function h_description() { 
        if (isset($this->data['description']))
            return htmlspecialchars($this->data['description']);
        else 
            return 'Whole country';
    }
    
    function latitude() {
        return $this->data['latitude'];
    }
    function longitude() {
        return $this->data['longitude'];
    }

    // Also update has_sms in web/poster.cgi
    function has_sms() {
        // Private pledges have no SMS for now
        if ($this->pin())
            return false;
        // Nor do byarea pledges (too hard to do interface to choose place)
        if ($this->byarea())
            return false;
        // Global pledges, we do show SMS (but will flag UK only)
        $cc = $this->country_code();
        if (!$cc)
            return true;
        // Non-UK countries have no SMS
        if ($cc != 'GB')
            return false;
        // UK countries have SMS
        return true;
    }

    // Links. The semantics here is that the URLs are all escaped, but didn't
    // need escaping. They can safely be used in HTML or plain text. The
    // "typein" URL is a short one for typing in - we use OPTION_BASE_URL 
    // so it is just www.pledgbank.com, rather than containing any overriden
    // language or country codes.
    function url_main() { return pb_domain_url() . $this->h_ref; }
    function url_typein() {
        global $microsite;
        $m = $microsite;
        if ($m == 'everywhere')
            $m = null;
        return pb_domain_url(array('lang'=>'', 'microsite'=>$m, 'path'=>'/')) . $this->h_ref;
    }
    function url_email() { return pb_domain_url() . $this->h_ref . "/email"; }
    function url_flyers() { return pb_domain_url() . $this->h_ref . "/flyers"; }
    function url_flyer($type) { return pb_domain_url() . "flyers/" . $this->h_ref . "_$type"; }
    function url_comments() { return pb_domain_url() . $this->h_ref . "#comments"; }
    function url_comments_rss() { return pb_domain_url() . $this->h_ref . '/rss-comments'; }
    function url_picture() { return pb_domain_url() . $this->h_ref . "/picture"; }
    function url_announce() { return pb_domain_url() . $this->h_ref . "/announce"; }
    function url_info() { return pb_domain_url() . $this->h_ref . "/info"; }
    function url_announce_archive() { return pb_domain_url() . $this->h_ref . "/announcearchive"; }
    function url_facebook() { return OPTION_FACEBOOK_CANVAS . $this->h_ref; }

    // This one needs encoding for use HTML, to escape the &
    function url_place_map() {
        if (!$this->is_local()) 
            return null;
        if (!array_key_exists('latitude', $this->data)) // pledges during creation
            return null;
        locale_push('en-gb');
        $coords_google = round($this->data['latitude'],2).' '.round($this->data['longitude'],2);
        $google_maps_url = 'http://maps.google.com/maps?q='.urlencode($coords_google).'&t=h';
        locale_pop();
        return $google_maps_url;
    }

    // This one needs encoding for use HTML, to escape the &
    function url_translate_pledge() {
        global $locale_current;
        $explicit_url = pb_domain_url(array('lang'=>$this->lang(), 'path'=>"/".$this->ref()));
        $from_lang =  $this->lang(); 
        $split_locale_current = split("-", $locale_current);
        $to_lang = $split_locale_current[0];

        if ($from_lang == $to_lang)
            return false;

        $translate_type = $from_lang."_".$to_lang;
        $babelfish_languages = array("zh_en" => 1, "zt_en" => 1, "en_zh" => 1, "en_zt" => 1, "en_nl" => 1, "en_fr" => 1, "en_de" => 1, "en_el" => 1, "en_it" => 1, "en_ja" => 1, "en_ko" => 1, "en_pt" => 1, "en_ru" => 1, "en_es" => 1, "nl_en" => 1, "nl_fr" => 1, "fr_en" => 1, "fr_de" => 1, "fr_el" => 1, "fr_it" => 1, "fr_pt" => 1, "fr_nl" => 1, "fr_es" => 1, "de_en" => 1, "de_fr" => 1, "el_en" => 1, "el_fr" => 1, "it_en" => 1, "it_fr" => 1, "ja_en" => 1, "ko_en" => 1, "pt_en" => 1, "pt_fr" => 1, "ru_en" => 1, "es_en" => 1, "es_fr");
        if (!array_key_exists($translate_type, $babelfish_languages))
            return false;

        return "http://babelfish.altavista.com/babelfish/tr?doit=done&tt=url&intl=1&trurl=".$explicit_url."&lp=".$translate_type;
    }

    // Rendering the pledge in various ways

    // Draws a plaque containing the pledge.  $params is an array, which
    // can contain the following:
    //     showdetails - if present and true, show "details" field
    //     href - if present must contain a URL, which is used as a link for
    //            the pledge sentence
    //     reportlink - if present and true, show "report this pledge" link
    //     class - adds the given classes (space separated) to the division
    //     facebook-sign - add facebook sign button
    //     facebook-share - add facebook share button

    function render_box($params = array()) {
        $sentence_params = array('firstperson'=>true, 'html'=>true);
        if (array_key_exists('href', $params)) {
            $sentence_params['href'] = $params['href'];
        }
        if (array_key_exists('class', $params))
            print '<div class="pledge ' . $params['class'] . '">';
        else
            print '<div id="pledge">';

        if (array_key_exists('facebook-share', $params) && $params['facebook-share']) {
            print $params['facebook-share'];
        }
?>
<p style="margin-top: 0">
<?      if ($this->has_picture()) { print "<img class=\"creatorpicture\" src=\"".$this->picture_url()."\" alt=\"\">"; } ?>
&quot;<?=$this->sentence($sentence_params) ?>&quot;
<?      if ($this->url_translate_pledge()) { ?>
    (<a title="<?=_("Roughly translate the pledge into your language (using Altavista's Babel Fish machine translator)")?>" href="<?=htmlspecialchars($this->url_translate_pledge())?>"><?=_("translate")?></a>)
<?      }
        $P = pb_person_if_signed_on();
        if (!is_null($P) && !is_null($P->email()) && preg_match('/(@mysociety.org$)|(^francis@flourish.org$)/', $P->email())) { ?>
    (<a href="<?=OPTION_ADMIN_URL?>?page=pb&amp;pledge=<?=$this->ref()?>"><?=_("admin")?></a>)
<?      } ?>
</p>
<?      if (!$this->byarea()) { ?>
<p style="text-align: right">&mdash; <?=$this->h_name_and_identity() ?></p>
<?      }

        global $microsite; # XXX
        if ($microsite == 'o2') {
            $cat_name = '';
            if (isset($this->data['category']))
                $cat_name = db_getOne('select name from category where id=?', $this->data['category']);
            else
                $cat_name = join('', $this->categories());
            if ($cat_name)
                print '<p>My Promise is about <strong>' . $cat_name . '</strong></p>';
        }
?>

<p>
<?=_('Deadline to sign up by:') ?> <strong><?=$this->h_pretty_date()?></strong>
<br>
<?      if ($this->signers() >= 0) {
            print '<i>';
            if ($this->finished())
                printf(ngettext('%s person signed up', '%s people signed up', $this->signers()), prettify_num($this->signers()));
            else
                printf(ngettext('%s person has signed up', '%s people have signed up', $this->signers()), prettify_num($this->signers()));
            if ($this->byarea()) {
                print ', ';
                print sprintf(
                    ngettext('successful in %d place', 'successful in %d places',
                            $this->byarea_successes()), 
                    $this->byarea_successes());
            } elseif (!microsites_no_target()) {
                if ($this->left() < 0) {
                    print ' ';
                    printf(_('(%d over target)'), -$this->left() );
                } elseif ($this->left() > 0) {
                    print ', ';
                    if ($this->finished())
                        printf(ngettext('%d more was needed', '%d more were needed', $this->left()), $this->left() );
                    else
                        printf(ngettext('%d more needed', '%d more needed', $this->left()), $this->left() );
                }
            }
            print '</i>';
        }
        print "</p>";

        $place_lines = array();
        // Microsite
        global $microsite, $microsites_list;
        // TODO: special case this to link to London if location is in correct area
        if ($this->microsite() and $this->microsite() != $microsite) { 
            $microsite_long_name = $microsites_list[$this->microsite()];
            $place_line = sprintf(_('This is a %s pledge'), "<strong>".$microsite_long_name."</strong>");
            $url = pb_domain_url(array('microsite'=>$this->microsite(), 'path'=>'/'));
            $place_line .= ' (<a title="'.sprintf(_("Show all of the %s pledges"), $microsite_long_name)
                    .'" href="'.htmlspecialchars($url).
                        '">'._("show more").'</a>)';
            $place_lines[] = $place_line;
        }
        // Geographical location
        if ($this->is_global()) { 
            // global
        } else { 
            $place_lines[] = _('Country:')." <strong>".$this->h_country()."</strong>";
            if ($this->is_local()) { 
                $place_line = $this->h_local_type();
                $place_line .= ": <strong>".$this->h_description()."</strong>";
                if ($this->url_place_map()) {
                    $place_line .= ' (<a title="'._("Show where exactly this place is (using Google Maps)")
                            .'" href="'.htmlspecialchars($this->url_place_map()).
                                '">'._("view map").'</a>)';
                } 
                $place_lines[] = $place_line;
            } 
        }
        if ($place_lines) {
            print '<p>';
            print join("<br>", $place_lines);
            print '</p>';
        }

        if (array_key_exists('facebook-sign', $params) && $params['facebook-sign']) {
            // We use GET, as adding the application loses POST parameters
?>
<div style="clear:both"></div>
<fb:editor action="<?=OPTION_FACEBOOK_CANVAS?><?=$this->ref()?>?sign_in_facebook=1&amp;csrf=<?=$params['facebook-sign-csrf']?>" labelwidth="170">
  <fb:editor-buttonset>
    <fb:editor-button value="Sign Pledge"/>
  </fb:editor-buttonset>
</fb:editor>
<?
        }


        if (array_key_exists('showdetails', $params) && $params['showdetails'] && isset($this->data['detail']) && $this->data['detail']) {
            $det = htmlspecialchars($this->data['detail']);
            $det = ms_make_clickable($det, array('contract'=>true, 'nofollow'=>true));
            $det = nl2br($det);
            print '<p id="moredetails"><strong>' . _('More details') . '</strong><br>' . $det . '</p>';
        }
?>

<? if ($this->byarea()) { ?>
<p class="byareamadeby"><?=sprintf(_("Pledge originally made by %s"), $this->h_name_and_identity()) ?></p>
<? } ?>

<? 
    if (array_key_exists('reportlink', $params) && $params['reportlink']) { 
    global $contact_ref; ?>
<div id="reportpledge"><a href="/contact<?=$contact_ref?>"><?=_('Anything wrong with this pledge?  Tell us!') ?></a></div>
<? } ?>
<?

?>   
<div style="clear:both"></div>
</div> <?
    }

    /* sentence PLEDGE PARAMS
     * Return a sentence describing what each signer agrees to do ("$pledgecreator
     * will ...  if ...").  PLEDGE is either a pledge id number, or an array of
     * pledge data from the database.  
     * If PARAMS['firstperson'] is true, then the sentence is "I will...", if
     * it is 'includename', says "I, $pledgecreator, will..."
     * If PARAMS['html'] is true, encode entities and add <strong> tags around
     * strategic bits. 
     * If PARAMS['href'] contains a URL, then the main part of the returned
     * sentence will be a link to that URL escaped.
     * XXX i18n -- this won't work at all in other languages */
    function sentence($params = array()) {
        $r = $this->data;
    
        $html = array_key_exists('html', $params) ? $params['html'] : false;
        if (!array_key_exists('firstperson', $params) || !$params['firstperson'])
            err('Explicitly set "firstperson"');
        $firstperson = $params['firstperson'];
        
        if ($html) {
            $r['places'] = null; // is an array during pledge creation
            $r = array_map('htmlspecialchars', $r);
        }
            
        global $lang, $langs;
        if (!array_key_exists($r['lang'], $langs)) {
            $r['lang'] = $lang;
        }
        locale_push($r['lang']);

        if (array_key_exists('href', $params)) {
            $title = sprintf("<a href=\"%s\">%s</a>", $params['href'], $r['title']);
        } else {
            $title = sprintf("<strong>%s</strong>", $r['title']);
        }

        $signup = trim($r['signup']);
        if ($html)
            $signup = ms_make_clickable($signup, array('nofollow'=>true));
        if (microsites_no_target()) {
            if ($firstperson === "includename") {
                $s = sprintf(_("I, %s, will %s."), $r['name'], $title);
            } else {
                $s = sprintf(_("I will %s."), $title);
            }
        } elseif ($firstperson === "includename") {
            $s = sprintf(_("I, %s, will %s but only if <strong>%s</strong> %s will %s."), $r['name'], $title, prettify_num($r['target']), $r['type'], $signup);
        } else {
            $s = sprintf(_("I will %s but only if <strong>%s</strong> %s will %s."), $title, prettify_num($r['target']), $r['type'], $signup);
        }

        if (!$html or array_key_exists('href', $params))
            $s = preg_replace('#</?strong>#', '', $s);

        // Tidy up
        $s = str_replace('..', '.', $s);

        locale_pop();
        return $s;
    }


    function h_sentence($params = array()) {
        $params['html'] = true;
        return $this->sentence($params);
    }

    function rss_entry() {
        return array(
          'title' => htmlspecialchars(trim_characters($this->title(), 0, 80)),
          'link' => pb_domain_url(array('explicit'=>true, 'path'=>"/".$this->ref())),
          'description' => "'" . 
                htmlspecialchars($this->sentence(array('firstperson'=>true, 'html'=>false)))
                . "' -- " . $this->h_name_and_identity(),
          'latitude' => $this->data['latitude'],
          'longitude' => $this->data['longitude'],
          'creationtime' => $this->data['creationtime']
        );
    }

    /* last_change_time
     * Return the time that the pledge was last changed in any way. */
    function last_change_time() {
        return intval(db_getOne('select extract(epoch from pledge_last_change_time(?))', $this->data['id']));
    }

    /* Display form for pledge signing. */
    function sign_box($errors = array()) {
        if (get_http_var('add_signatory'))
            $showname = get_http_var('showname') ? ' checked' : '';
        else
            $showname = ' checked';

        $email = get_http_var('email');
        $name = get_http_var('name', true);

        $P = pb_person_if_signed_on();
        if (!is_null($P)) {
            if (is_null($email) || !$email)
                $email = $P->email();
            if (is_null($name) || !$name)
                $name = $P->name_or_blank();
        } else {
            // error_log("nobody signed on");
        }

        // error_log("$email $name");
    ?>
    <form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/<?=htmlspecialchars($this->ref()) ?>/sign" method="post">
    <input type="hidden" name="add_signatory" value="1">
    <input type="hidden" name="pledge" value="<?=htmlspecialchars($this->ref()) ?>">
    <input type="hidden" name="ref" value="<?=htmlspecialchars($this->ref()) ?>">
    <?  print h2($this->byarea() ? _('Sign up where you live') : _('Sign up now'));
        if (get_http_var('pin', true)) print '<input type="hidden" name="pin" value="'.htmlspecialchars(get_http_var('pin', true)).'">';
        $namebox = '<input onblur="fadeout(this)" onfocus="fadein(this)" size="20" type="text" name="name" id="name" value="' . htmlspecialchars($name) . '">';
        print '<p><strong>';
        printf(_('I, %s, sign up to the pledge.'), $namebox);
        print '</strong><br></p>';
        if (microsites_intranet_site()) {
            print '<p><input type="hidden" name="showname" value="1">
    <small>People are able to search for Promises you have signed.</small></p>';
        } else {
            print '<p>
    <small>
    <strong><input type="checkbox" name="showname" value="1"' . $showname . '> ' . _('Show my name publically on this pledge.') . '</strong>
    '._('People searching for your name on the Internet will be able
    to find your signature, unless you uncheck this box.').'</small>
    </p>';
            }
        if ($this->byarea()) {
            // Pledges where target is per town, rather than overall
            if ($this->is_global()) {
                ?> <p><strong><?=_('Your country:') ?></strong>&nbsp;<? 
                gaze_controls_print_country_choice(microsites_site_country(), null, array(), array('noglobal' => true, 'gazeonly' => true));
            } else {
?>            <p><input type="hidden" name="prev_country" value="<?=$this->country_code()?>"> 
              <input type="hidden" name="country" value="<?=$this->country_code()?>"> <?
            }
?>
<span style="white-space: nowrap"><strong><?=_('Your town:')?>
&nbsp;<input <? /* onkeypress="byarea_town_keypress(this)" */?> type="text" size="20" name="place" value="<?=htmlspecialchars(get_http_var("place"))?>" <?=array_key_exists('place', $errors) ? ' class="error"' : ''?> ></strong></span>
                <? if (!$this->is_global()) { ?>
<br><small><?=sprintf(_('(%s only)'), $this->h_country_no_state())?></small>
                <? } 
?> <div id="byarea_town_ajax"></div> <?
        }
        print '
    <p><strong>' . _('Your email') . '</strong>: <input'. (array_key_exists('email', $errors) ? ' class="error"' : '').' type="text" size="30" name="email" value="' . htmlspecialchars($email) . '"><br><small>'.
    _('(we only use this to tell you when the pledge is completed and to let the pledge creator get in touch)') . '</small> </p>';
        microsites_signup_extra_fields($errors);
        print '<p><input type="submit" name="submit" value="' . ($this->byarea() ? _('Sign Pledge') : _('Sign Pledge')) . '"></p>';
        print '<p>';
        // Display SMS if we are sure it makes sense - i.e. we support SMS for
        // the pledge country (or it is global) and we support SMS for the site
        // country.
        if ($this->has_sms() && sms_site_country() && microsites_has_sms()) {
            printf(_("Or, text '<strong>%s %s</strong>' to <strong>%s</strong>"), OPTION_PB_SMS_PREFIX, $this->ref(), OPTION_PB_SMS_DISPLAY_NUMBER);
            print " ";
            printf(_("(in %s only)"), sms_countries_description());
            print "<br>";
        }
        printf(_('Or, share and sign this pledge <strong><a href="%s">in Facebook</a></strong>'), $this->url_facebook());
        print '</p>';
        print '</form>';
    }

    /* summary PLEDGE PARAMS
     * Return pledge text in a format suitable for a (long) summary on a list of
     * pledges, such as the front page.  PLEDGE is an array of info about the
     * pledge.  PARAMS are passed to sentence, and also:
     * 'showcountry' - display country as well
     */
    function summary($params) {
        $text = '';
        if (array_key_exists('showcountry', $params) && $params['showcountry'] && $this->country_code()) {
            global $countries_code_to_name;
            $text .= $countries_code_to_name[$this->country_code()] . ": ";
        }
        $params['firstperson'] = 'includename';
        $text .= $this->sentence($params) . ' ';
        if (microsites_no_target()) { # XXX O2
            if ($this->daysleft() == 0)
                $text .= 'Promise open until midnight tonight, London time.';
            elseif ($this->daysleft() < 0)
                $text .= 'Promise closed.';
            else
                $text .= "(";
                if ($this->daysleft() <= 3) {
                    $text .= sprintf(ngettext('just %d day left', 'just %d days left', $this->daysleft()), $this->daysleft());
                } else {
                    $text .= sprintf(ngettext('%d day left', '%d days left', $this->daysleft()), $this->daysleft());
                }
                $text .= ')';
        } elseif ($this->byarea()) {
            if ($this->daysleft() > 0) $text .= '(';
            if ($this->byarea_successes() == 0) 
                $text .= _('Target met nowhere');
            else
                $text .= sprintf(ngettext('Target met in %d place', 'Target met in %d places',
                        $this->byarea_successes()), $this->byarea_successes());
            $text .= ', ';
            if ($this->daysleft() == 0) {
                $hours = 24 - date('G');
                $text .= sprintf(ngettext('pledge open for %d hour.', 'pledge open for %d hours.', $hours), $hours);
            } elseif ($this->daysleft() < 0) {
                $text .= 'pledge closed.';
            } else {
                if ($this->daysleft() <= 3) {
                    $text .= sprintf(ngettext('just %d day left', 'just %d days left', $this->daysleft()), $this->daysleft());
                } else {
                    $text .= sprintf(ngettext('%d day left', '%d days left', $this->daysleft()), $this->daysleft());
                }
            }
            if ($this->daysleft() > 0) $text .= ')';
        } elseif ($this->left() <= 0) {
            if ($this->daysleft() == 0) {
                $hours = 24 - date('G');
                $text .= sprintf(ngettext('Target met, pledge still open for %d hour.', 'Target met, pledge still open for %d hours.', $hours), $hours);
            } elseif ($this->daysleft() < 0)
                $text .= _('Target met, pledge closed.');
            else
                $text .= sprintf(ngettext('Target met, pledge still open for %d day.', 'Target met, pledge still open for %d days.', $this->daysleft()), $this->daysleft());
        } else {
            if ($this->daysleft() == 0) {
                $hours = 24 - date('G');
                $text .= '(';
                $text .= sprintf(ngettext('Just %d hour left', 'Just %d hours left', $hours), $hours);
                $text .= sprintf(ngettext(', %d more signature needed', ', %d more signatures needed', $this->left()), $this->left());
                $text .= ')';
            } elseif ($this->daysleft() < 0)
                $text .= _('Deadline expired, pledge failed.');
            else {
                $text .= "(";
                if ($this->daysleft() <= 3) {
                    $text .= sprintf(ngettext('Just %d day left', 'Just %d days left', $this->daysleft()), $this->daysleft());
                } else {
                    $text .= sprintf(ngettext('%d day left', '%d days left', $this->daysleft()), $this->daysleft());
                }
                $text .= sprintf(ngettext(', %d more signature needed', ', %d more signatures needed', $this->left()), $this->left());
                $text .= ')';
            }
        }
        return $text;
    }

}

/* PLEDGE_...
 * Various codes for things which can happen to pledges. All such error codes
 * must be nonpositive. */
define('PLEDGE_OK',          0);
define('PLEDGE_NONE',       -1);    /* Can't find that pledge */
define('PLEDGE_FINISHED',   -2);    /* Pledge has expired */
define('PLEDGE_FULL',       -3);    /* All places taken */
define('PLEDGE_SIGNED',     -4);    /* Email address is already on pledge */
define('PLEDGE_DENIED',     -5);    /* Permission denied */
define('PLEDGE_BYAREA',     -6);    /* Not supported for byarea pledges */

    /* codes <= -100 represent temporary errors */
define('PLEDGE_ERROR',    -100);    /* Some sort of nonspecific error. */

/* pledge_is_error RESULT
 * Does RESULT indicate an error? */
function pledge_is_error($res) {
    return (is_int($res) && $res < 0);
}

/* pledge_strerror CODE
 * Return a description of the error CODE. */
function pledge_strerror($e) {
    switch ($e) {
    case PLEDGE_OK:
        return _("Success");

    case PLEDGE_FINISHED:
        return _("That pledge has already finished");

    case PLEDGE_FULL:
        return _("That pledge is already full");

    case PLEDGE_BYAREA:
        return _("Signing up with a place name is supported only by email (not mobile phones or Facebook)");

    case PLEDGE_SIGNED:
        return _("You've already signed that pledge");

    case PLEDGE_DENIED:
        return _("Permission denied");

    case PLEDGE_ERROR:
    default:
        return _("Something went wrong unexpectedly");
    }
}

/* pledge_is_permanent_error CODE
 * Return true if CODE represents a permanent error (i.e. one which won't go
 * away by itself). */
function pledge_is_permanent_error($e) {
    return ($e > PLEDGE_ERROR);
}

/* pledge_dbresult_to_code RESULT
 * Convert a string result from the database (e.g. 'ok', 'none', etc.) into a
 * PLEDGE_... code. */
function pledge_dbresult_to_code($r) {
    $resmap = array(
            'ok' => PLEDGE_OK,
            'none' => PLEDGE_NONE,
            'finished' => PLEDGE_FINISHED,
            'signed' => PLEDGE_SIGNED,
            'full' => PLEDGE_FULL,
            'byarea' => PLEDGE_BYAREA
        );
    if (array_key_exists($r, $resmap))
        return $resmap[$r];
    else
        err("Bad result $r in pledge_dbresult_to_code");
}

/* pledge_is_valid_to_sign PLEDGE EMAIL MOBILE FACEBOOK_ID
 * Return a PLEDGE_... code describing whether EMAIL/MOBILE/FACEBOOK_ID may
 * validly sign PLEDGE. This function locks rows in pledges and signers with
 * select ... for update / lock tables. */
function pledge_is_valid_to_sign($pledge_id, $email, $mobile = null, $facebook_id = null) {
    return pledge_dbresult_to_code(
                db_getOne('select pledge_is_valid_to_sign(?, ?, ?, ?)',
                    array($pledge_id, $email, $mobile, $facebook_id))
            );
}

/* check_pin REF ACTUAL_PIN
   Checks to see if PIN submitted is correct, returns true if it is and false
   for wrong or no PIN.  */
function check_pin($ref, $actual) {
    $raw = get_http_var('pin', true);
    $entered = $raw ? sha1($raw) : $raw;
    if (!$actual) 
        return true;

    if ($entered) {
        if ($entered == $actual) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/* deal_with_pin LINK REF ACTUAL_PIN
   Calls check_pin and if necessary returns HTML form for asking for pin.
   Otherwise returns false.
       LINK url for pin form to post back to
       REF pledge reference
       ACTUAL_PIN actual pin
*/
function deal_with_pin($link, $ref, $actual) {
    if (check_pin($ref, $actual)) {
        return false;
    }

    $html = "";
    if (get_http_var('pin', true)) {
        $html .= '<p id="error">' . _('Incorrect PIN!') . '</p>';
    }
    $html .= '<form class="pledge" name="pledge" action="'.$link.'" method="post">' .
        h2(_('PIN Protected Pledge')) . '<p>' . _('This pledge is protected.  Please enter the PIN to proceed.') . '</p>';
    $html .= '<p><strong>PIN:</strong> <input type="password" name="pin" value=""><input type="submit" name="submitpin" value="' . _('Submit') . '"></p>';
    $html .= '</form>';
    return $html;
}

/* print_link_with_pin
   Prints out a link, normally just using <a href=...>.  Title is for
   the title= attribute, and text is the actual text body of the link.
   If this page has a PIN, then instead of a link prints a button which
   also transmits the passowrd to the link page.  Text to this function
   should be already escaped, or not need escaping, for display in URLs or
   HTML.*/
function print_link_with_pin($link, $title, $text) {
    if (get_http_var('pin', true)) {
?> 
    <form class="buttonform" name="buttonform" action="<?=$link?>" method="post" title="<?=$title?>">
    <input type="hidden" name="pin" value="<?=htmlspecialchars(get_http_var('pin', true))?>">
    <input type="submit" name="submitbuttonform" value="<?=$text?>">
    </form>
<?
    } else {
?><a href="<?=$link?>" title="<?=$title?>"><?=$text?></a><?
    }
}

/* Sends a message to pledge creator with URL containing link
 * to let them make an announcement to all signers. */
function send_announce_token($pledge_id) {
    $max_circumstance = db_getOne("select max(circumstance_count) from message
        where pledge_id = ? and circumstance = ?", array($pledge_id, 'announce-post'));
    db_query("
            insert into message (
                pledge_id, circumstance, circumstance_count,
                sendtocreator, sendtosigners, sendtolatesigners,
                emailtemplatename
            ) values (
                ?, 'announce-post', ?,
                true, false, false,
                'announce-post'
            )", array($pledge_id, $max_circumstance + 1));
    db_commit();
}


/* post_confirm_advertise PLEDGE_ROW
   Print relevant advertising */
function post_confirm_advertise() {
    if (!microsites_local_alerts()) return;
    print _("<p>PledgeBank can send you email alerts when people
        create local pledges in your area. Sign up - you don't know
        what interesting things people might be getting up to.</p>");
    pb_view_local_alert_quick_signup("localsignupfrontpage", array());
}

/* post_confirm_advertise_flyers PLEDGE_ROW
 * Print some stuff advertising flyers for PLEDGE. */
function post_confirm_advertise_flyers($r) {
    $png_flyers8_url = url_new("/flyers/{$r['ref']}_A4_flyers8.png", false);
?>
<p class="noprint noisymessage" align="center">
<?
    # TRANS: Esperanto translated this as "In order to massive increase the chance of this pledge succeeding, " to keep it in the imperative
    print _('You will massively increase the chance of this pledge succeeding if you ');
    if (!$r['pin']) {
        # TRANS: This phrase is used in two different places, once in present tense, once in imperative. Shout if this is a problem.
        print print_this_link(_("print this page out"), "");
        $flyerurl = '<a href="/' . htmlspecialchars($r['ref']) . '/flyers">' . _('these more attractive PDF and RTF (Word) versions') . '</a>';
        # TRANS: Use the imperative here to go with msgid "You will massively increase......." and msgid "print this page out". (Tim Morley, 2005-11-30)
        printf(_('(or use %s), cut up the flyers and stick them through your neighbours\' letterboxes.'), $flyerurl);
   } else {
        // TODO - we don't have the PIN raw here, but really want it on
        // form to pass on for link to flyers page.  Not sure how best to fix
        // this up.
        print_link_with_pin("/".htmlspecialchars($r['ref'])."/flyers", "", _("print these pages out"));
        print _(", cut up the flyers and stick them through your neighbours' letterboxes.");
   }
    print ' ';
    print _('We cannot emphasise this enough &mdash; print them NOW and post them next time you
go out to the shops or your pledge is unlikely to succeed.');
    // Show inline graphics only for PINless pledges (as PNG doesn't
    // work for the PIN protected ones, you can't POST a PIN
    // into an IMG SRC= link)
    if (!$r['pin']) { ?>
<p align="center"><a href="<?=$png_flyers8_url?>"><img width="595" height="842" src="<?=$png_flyers8_url?>" border="0" alt="<?=_('Graphic of flyers for printing') ?>"></a></p>
<?  }
}

/* pledge_delete_pledge ID
 * Delete the pledge with the given ID, and all its signers and comments. */
function pledge_delete_pledge($id) {
    db_query('select pb_delete_pledge(?)', $id);
}

/* pledge_delete_signer ID
 * Delete the siger with the given ID. */
function pledge_delete_signer($id) {
    db_query('select pb_delete_signer(?)', $id);
}

/* pledge_delete_comment ID
 * Delete the comment with the given ID. */
function pledge_delete_comment($id) {
    db_query('select pb_delete_comment(?)', $id);
}

/* pledge_is_local R
 * Given pledge data, returns true if local pledge (where flyers
 * are useful), or false if it isn't. */
function pledge_is_local($r) {
    return isset($r['description']); 
}

/* percent_success_above TARGET
 * Return % of pledges successful above that target */
function percent_success_above($threshold) {
    $total_pledges_above = db_getOne("select count(*) from pledges where target > ? and ms_current_date() > pledges.date", array($threshold));
    $successful_pledges_above = db_getOne("select count(*) from pledges where target > ? and ms_current_date() > pledges.date and whensucceeded is not null", array($threshold));
    if ($total_pledges_above == 0)
        $percent_successful_above = 0.0;
    else
        $percent_successful_above = 100.0 * $successful_pledges_above / $total_pledges_above;
    return $percent_successful_above;
}

# params must have:
# 'global' - true or false, whether global pledges to be included
# 'main' - true or false, whether site country pledges to be included
# 'foreign' - true or false, whether pledges from other countries (or all countries if no site country) to be included
# 'showcountry' - whether to display country name in summary
function pledge_get_list($where, $params) {
    $query = "SELECT pledges.*, pledges.ref, country,
                    (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges LEFT JOIN location ON location.id = pledges.location_id
                         LEFT JOIN person ON person.id = pledges.person_id
            WHERE ";
    $sql_params = array();
    
    $queries = array();
    if ($params['main'])
        $queries[] = pb_site_pledge_filter_main($sql_params);
    if ($params['foreign'])
        $queries[] = pb_site_pledge_filter_foreign($sql_params);
    if ($params['global'])
        $queries[] = pb_site_pledge_filter_general($sql_params);
    $query .= '(' . join(" OR ", $queries) . ')';

    $query .= " AND " . $where;
    #print "<p>query: $query</p>"; print_r($sql_params);
    $q = db_query($query, $sql_params);
    $pledges = array();
    while ($r = db_fetch_array($q)) {
        $pledge = new Pledge($r);
        $pledges[] = $pledge;
    }
    return $pledges;
}

# Get list of prioritised important pledges for frontpage, and Facebook featured pledges page.
# $pledges_required_fp -- number of pledges to show on main part of front page if frontpaged
# $pledges_required_n -- number of pledges below which we show normal pledges, rather than just frontpaged ones
# Returns the array of pledges in order, and a boolean saying if there are more (if you need a more link).
function pledge_get_frontpage_list($pledges_required_fp, $pledges_required_n) {
    global $pb_today;

    // We take one too many, and pop one at the end (or something?)
    $pledges_required_fp++;
    $pledges_required_n++;

    $more_threshold = $pledges_required_fp;
    $pledges = pledge_get_list("
                cached_prominence = 'frontpage' AND
                date >= '$pb_today' AND 
                pin is NULL AND 
                whensucceeded IS NULL
                ORDER BY RANDOM()
                LIMIT $pledges_required_fp", array('global'=>false,'main'=>true,'foreign'=>false));
    //print "<p>main frontpage: ".count($pledges);
    if (count($pledges) < $pledges_required_fp) {
        // If too few, show some global frontpage pledges
        $more =$pledges_required_fp - count($pledges);
        $global_pledges = pledge_get_list("
                    cached_prominence = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT '$more'", array('global'=>true,'main'=>false,'foreign'=>false));
        $pledges = array_merge($pledges, $global_pledges);
        //print "<p>global frontpage: ".count($global_pledges);
    }
    if (count($pledges) <= $pledges_required_n) 
        $more_threshold = $pledges_required_n;
    
    if (count($pledges) < $pledges_required_n) {
        // If too few, show a few of the normal pledges for the country
        $more = $pledges_required_n - count($pledges);
        $normal_pledges = pledge_get_list("
                    ".microsites_normal_prominences()." AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT $more", array('global'=>false,'main'=>true,'foreign'=>false));
        $pledges = array_merge($pledges, $normal_pledges);
        //print "<p>main normal: ".count($normal_pledges);
    }
    if (count($pledges) < $pledges_required_n) {
        // If too few, show some global normal pledges
        $more =$pledges_required_n - count($pledges);
        $global_normal_pledges = pledge_get_list("
                    ".microsites_normal_prominences()." AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT '$more'", array('global'=>true,'main'=>false,'foreign'=>false));
        $pledges = array_merge($pledges, $global_normal_pledges);
        //print "<p>global normal: ".count($global_normal_pledges);
    }

    $more = false;
    if (count($pledges) == $more_threshold) {
        $more = true;
        array_pop($pledges);
    }
    
    return array($pledges, $more);
}


# Draw part at top of pledge page which says pledge has succeeded/failed etc.
function pledge_draw_status_plaque($p) {
    if ($p->is_cancelled()) {
        print '<p class="cancelled">' . comments_text_to_html($p->data['cancelled']) . '</p>';
        return;
    }
    if ($p->data['notice']) {
        print '<p class="notice">' . comments_text_to_html($p->data['notice']) . '</p>';
    }
    if (!$p->open()) {
        print '<p class="finished">' . microsites_pledge_closed_text() . '</p>';
    }
    if ($p->byarea()) {
        if ($p->byarea_successes() > 0) {
            print '<p class="success">';
            print sprintf(
                ngettext('This pledge has been successful in <strong>%d place</strong>!',
                        'This pledge has been successful in <strong>%d places</strong>!',
                        $p->byarea_successes()), 
                $p->byarea_successes());
            if (!$p->finished()) {
                print '<br>' . _('<strong>You can still sign up</strong>, to help make it successful where you live.');
            }
            print '</p>';
        }
    } elseif ($p->left() <= 0 && !microsites_no_target()) {
        print '<p class="success">' . _('This pledge has been successful!');
        if (!$p->finished()) {
            print '<br>' . _('<strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.');
        }
        print '</p>';
    }
}


?>
