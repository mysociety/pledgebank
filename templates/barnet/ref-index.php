<?
// ref-index.php:
// Main pledge page for Barnet.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//

pledge_draw_status_plaque($p); # XXX
?>
    <div class="ms-signers-unfinished"> <!-- across top -->
        <span class="ms-signed-up">
<?
        if ($p->finished())
            printf(ngettext('<span class="ms-qty">%s</span> person signed up',
                '<span class="ms-qty">%s</span> people signed up', $p->signers()), prettify_num($p->signers()));
        else
            printf(ngettext('<span class="ms-qty">%s</span> person has signed up',
                '<span class="ms-qty">%s</span> people have signed up', $p->signers()), prettify_num($p->signers()));
?>
        </span>
        <span class="ms-target">
<?
        if ($p->left() < 0) {
            printf('<span class="ms-qty">%d</span> over target', -$p->left() );
        } elseif ($p->left() > 0) {
            if ($p->finished())
                printf(ngettext('...but we still needed <span class="ms-qty">%d</span>', '...but we still needed <span class="ms-qty">%d</span>', $p->left()), $p->left() );
            else
                printf(ngettext('...but we still need <span class="ms-qty">%d</span>', '...but we still need <span class="ms-qty">%d</span>', $p->left()), $p->left() );
        }
?>
         </span>
    </div> <!-- ms-signers-[un]finished -->

<div id="ms-right-col">
  <div id="ms-signup-box">    
    <img class="ms-signup-img" src="/microsites/barnet/sign-up.jpg" alt="Sign Up Here">

    <!--<h2>Sign up now</h2> moved h2 out of form -->
    <form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/<?=$p->ref()?>/sign" method="post">
      <input type="hidden" name="add_signatory" value="1">
      <input type="hidden" name="pledge" value="<?=$p->ref()?>">
      <input type="hidden" name="ref" value="<?=$p->ref()?>">
      <p id="name_row">
        <label for="name">Your name:</label> <input size="30" type="text" name="name" id="name" value="">
      </p>

      <div id="ms-signup-reveal"><!-- hide until the form is focussed -->
        <p id="email_row">
          <label for="email">Your email:</label> <input type="text" size="30" id="email" name="email" value="">
        </p>
        <p id="email_blurb">
          <small>(we only use this to tell you when the pledge is completed and to let the pledge creator get in touch)</small> 
        </p>
        <p id="showname_row">

          <small>
            <label style="float:none"><input type="checkbox" name="showname" value="1" checked> Show my name publicly on this pledge.</label>
            <br>People searching for your name on the Internet might be able to find your signature.
          </small>
        </p>
      </div>
      <p id="signpledge_row">
        <input type="submit" name="submit" id="next_step" value="Sign Pledge">
      </p>
    </form>

    <div class="ms-deadline">
      <p>
        Deadline to sign up by: 
        <br/> <!-- added br -->
        <strong><?=$p->h_pretty_date()?></strong>
      </p>
    </div>

  </div><!-- signup box -->
  
  <div id="col1">
    <div id="signatories">
      <h2>
        <a name="signers">Current signatories</a> 
      </h2>

      <!-- moved outside of the h2 -->
        (<span style="color:#006600"><img alt="Green text " src="http://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Yes_check.svg/16px-Yes_check.svg.png">= they've done it</span>)
      
<?
    $P = pb_person_if_signed_on();
    $nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
    if ($nsigners == 0) {
        print p('No one so far.');
    }

    $anon = 0;
    $anon_done = 0;
    $mobilesigners = 0;
    $facebooksigners = 0;
    
    $query = "SELECT signers.*, person.mobile as mobile
        from signers 
        LEFT JOIN person on person.id = signers.person_id 
        WHERE signers.pledge_id = ? ORDER BY id";
    $q = db_query($query, $p->id());
    $in_ul = false;
    while ($r = db_fetch_array($q)) {
        $showname = ($r['showname'] == 't');
        if (!$in_ul) {
            print "<ul>";
            $in_ul = true;
        }
        if ($showname) {
            if (isset($r['name'])) {
                print '<li id="signer' . $r['id'] . '"';
                if ($r['done']=='t') print ' class="done"';
                print '>';
                if (microsites_has_survey() && !is_null($P) && $r['person_id'] == $P->id()) {
                    print '<form method="post" action="' . $p->url_survey() . '"><input type="hidden" name="r" value="pledge">';
                }
                print htmlspecialchars($r['name']);
                if (microsites_has_survey() && !is_null($P) && $r['person_id'] == $P->id()) {                
                    if ($r['done']=='f' ) {
                        print ' &ndash; <input type="submit" value="'._("I have now done what I pledged").'">';                    
                    } else {
                        print ' &ndash; <input type="hidden" name="undopledge" value="1"><input type="submit" value="'._("Click this button if in fact you have NOT done what you pledged").'">';
                    }
                    print '</form>';
                }
                print '</li>';
            } else {
                err('showname set but no name');
            }
        } elseif (isset($r['mobile'])) {
            $mobilesigners++;
        } else {
            $anon++;
            if ($r['done']=='t') $anon_done++;
        }
    }
    display_anonymous_signers($p, $anon, $anon_done, $mobilesigners, $facebooksigners, $in_ul);
    if ($in_ul) {
        print "</ul>";
        $in_ul = false;
    }
?>

    </div>
  </div>
</div> <!-- ms-col-right -->

<div id="pledge_main">
    <h2 style="margin-bottom:0.5em"><span><?=sprintf(_('Pledge &ldquo;%s&rdquo;'), $p->ref())?></span></h2>
<?
    $r = array_map('htmlspecialchars', $p->data);
?>
    <p class="head_mast">
        <span class='ms-pledger'><?=$r['name']?></span> will
        <span class='ms-pledger-action'><strong><?=$r['title']?></strong></span>
        but only if <span class='ms-pledge-people'><span class='ms-qty'><strong><?=prettify_num($r['target'])?></strong></span>
        <?=$r['type']?></span> will <span class='ms-signatory-action'><?=$r['signup']?></span>.
    </p>
</div>

<div id="col2">
<?
    draw_comments($p);
?>
</div>

<script type="text/javascript">    
    $(function() {
        $('#name').focus(function() {$('#ms-signup-reveal').slideDown('slow')});
    });
</script>

<?
    draw_spreadword($p);

