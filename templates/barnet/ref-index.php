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
        <label for="name">Your name:</label> <input size="20" type="text" name="name" id="name" value="">
      </p>

      <div id="ms-signup-reveal"><!-- hide until the form is focussed -->
        <p id="email_row">
          <label for="email">Your email:</label> <input type="text" size="20" id="email" name="email" value="">
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
      <a name="signers"><img class="ms-signatories-img" src="/microsites/barnet/current-signatories.jpg" alt="Current Signatories"></a>

      <!--<h2><a name="signers">Current signatories</a> </h2>-->
      <p>
        (<span style="color:#006600"><img alt="Green text " src="http://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Yes_check.svg/16px-Yes_check.svg.png">= they've done it</span>)
      </p>
      
<?
    $nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
    if ($nsigners == 0) {
        print p('No one so far.');
    } else {
        draw_signatories_list($p, $nsigners, false);
    }
?>
      <div id="ms-signatories-bottom"></div>
    </div>
    <img src="/microsites/barnet/barnet-pb-small.jpg" class="ms-pledgbank-logo">
    
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

<div id="spreadword">
<ul>
<li>
    <a href="http://twitter.com/share" class="twitter-share-button" data-url="<?=$p->url_typein()?>" data-count="none">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
</li>
<li>
    <a class="fb_share" name="fb_share" type="button" share_url="<?=$p->url_typein()?>" href="http://www.facebook.com/sharer.php">Share</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
</li>
<?
    if (!$p->finished()) {
      print '<li><a href="' . $p->url_flyers() . '" title="Print out customised flyers"><img src="/microsites/barnet/icon-print-flyers.png" id="ms-icon-print-flyers" alt="Print flyers" /></a></li>';
      print '<li><a href="/' . $p->ref() . '/promote" title="Promote on your site or blog"><img src="/microsites/barnet/icon-blog.png"  id="ms-icon-blog" alt="Promote on your site or blog"/></a></li>';
    } 
?>
</ul>
</div>

<div id="col2">
<?
    draw_comments($p);
?>
</div>

<?
    if (!$p->finished()) { /* fancy glowing on blog/print-flyer icons */
?>
  <div style="display:none;">
    <img id="ms-icon-print-flyers-active" src="/microsites/barnet/icon-print-flyers-active.png" width="1px" height="1px" />
    <img id="ms-icon-blog-active" src="/microsites/barnet/icon-blog-active.png" width="1px" height="1px" />
  </div>
  <script type="text/javascript">    
      $(function() {
        $('#ms-icon-blog').data('inactive', $('#ms-icon-blog').attr("src"));
        $('#ms-icon-blog').data('active', $('#ms-icon-blog-active').attr("src"));
        $('#ms-icon-print-flyers').data('inactive', $('#ms-icon-print-flyers').attr("src"));
        $('#ms-icon-print-flyers').data('active', $('#ms-icon-print-flyers-active').attr("src"));

        $('#ms-icon-print-flyers').hover(
          function(){$(this).attr("src",$(this).data("active"))},
          function(){$(this).attr("src",$(this).data("inactive"))}
        )
        $('#ms-icon-blog').hover(
          function(){$(this).attr("src",$(this).data("active"))},
          function(){$(this).attr("src",$(this).data("inactive"))}
        )
      });
  </script>
<? } ?>

<script type="text/javascript">    
    $(function() {
        $('#name').focus(function() {$('#ms-signup-reveal').slideDown('slow')});
    });
</script>
