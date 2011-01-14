<?
// ref-index.php:
// Main pledge page for Barnet.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
?>


<div id="ms-pb-status-plaque">
    <?
    pledge_draw_status_plaque($p); # XXX
    ?>
</div>

<div id="ms-pb-pledgepage">

    <div class="ms-pb-top-img" style="background-image:url('/microsites/barnet/<?= strtolower($p->ref()) ?>.jpg');">
        <? $roundel_class = 'ms-pb-signed-roundel-' . ($p->finished()? 'closed':'open') . '-'  . ($p->signers()==1? 'person':'people'); ?>
        <div id="ms-pb-signed-roundel" class="<?= $roundel_class ?>">
            <div id="ms-pb-signed-roundel-ie" class="<?= $roundel_class ?>-ie">
                <p>
                    <?= prettify_num($p->signers()) ?>
                </p>
            </div>
        </div>
    </div>
    <div id="ms-pb-pledge-text">
      
      <?=
          $p->sentence(array('firstperson'=>'includename')); // in Barnet: firstperson is overridden conditionally on pledge name
      ?>

    </div>

    <div class="ms-pb-mid-container">
        <div id="ms-pb-places-left">
            <span id="ms-pb-places-left-count">
                <?
                        if ($p->left() < 0) {
                            printf('%d', -$p->left() ); # over target
                        } else {
                            printf('%d', $p->left() ); # under target/still needed
                        }
                ?>
            </span>
            <span id="ms-pb-places-left-label">
                <?
                    if ($p->left() < 0) {
                        print('over target');
                    } elseif ($p->left() >= 0) {
                        if ($p->finished()) # but we still needed... (NB "0 under target" is right... I think)
                            print('under target' );
                        else # but we still need...
                            print('still needed');
                    }
                ?>
            </span>
        </div>
        <div id="ms-pb-deadline">
            <h3>Deadline:</h3>
            <p>
                <?=$p->h_pretty_date()?>
            </p>
            <div id="ms-pb-deadline-footer"></div>
        </div>
        <div id="ms-pb-spread">
            <ul>
                <li>
                    <a href="http://twitter.com/share" class="twitter-share-button" data-url="<?=$p->url_typein()?>" data-count="none">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>                    
                </li>
                <li>
                    <a class="fb_share" name="fb_share" type="button" share_url="<?=$p->url_typein()?>" href="http://www.facebook.com/sharer.php">Share</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
                </li>
                <?
                    if (!$p->finished()) {
                      print '<li><a href="' . $p->url_flyers() . '" title="Print out customised flyers">Print&nbsp;flyers</a></li>';
                      print '<li><a href="/' . $p->ref() . '/promote" title="Promote on your site or blog">Blog&nbsp;it!</a></li>';
                    } 
                ?>
            </ul>
            <div class="ms-pb-clear"></div>
        </div>           
        <div class="ms-pb-clear"></div>
    </div>

    <div class="ms-pb-bot-container">
        <div id="ms-pb-signatories">
            <div id="ms-pb-sign-here">
                <h2>Sign pledge</h2>
                <form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/<?=$p->ref()?>/sign" method="post">
                    <input type="hidden" name="add_signatory" value="1">
                    <input type="hidden" name="pledge" value="<?=$p->ref()?>">
                    <input type="hidden" name="ref" value="<?=$p->ref()?>">
                    <p id="name_row">
                        <label for="name">Your name:</label> <input size="20" type="text" name="name" id="name" value="">
                    </p>
                    <div id="ms-signup-reveal">
                        <p id="email_row">
                            <label for="email">Your email:</label> <input type="text" size="20" id="email" name="email" value="">
                        </p>
                        <p id="email_blurb">
                            (we only use this to tell you when the pledge is completed and to let the pledge creator get in touch)
                        </p>
                        <p id="showname_row">
                            <label style="float:none"><input type="checkbox" name="showname" value="1" checked> Show my name publicly on this pledge.</label>
                            <br>
                        People searching for your name on the Internet might be able to find your signature.
                        </p>
                    </div>
                    <p id="signpledge_row">
                        <input type="submit" name="submit" id="next_step" value="Sign Pledge">
                    </p>
                </form>
            </div>
            <h2>Signatories</h2>
            <div class="ms-pb-general">
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
            </div>
            <div id="ms-pb-signatories-footer"></div>
        </div>
        <div id="ms-pb-comments">
            <h2>Comments on this pledge</h2>
            <div class="ms-pb-general">
                <?
                    draw_comments($p);
                ?>
            </div>
            <div class="ms-pb-bot-container-footer"></div>
        </div>
        <div class="ms-pb-clear"></div>
    </div>
    
    <div class="ms-pb-clear"></div>
</div>

<script type="text/javascript">    
    $(function() {
        $('#name').focus(function() {$('#ms-signup-reveal').slideDown('slow')});

        $('#next_step').mouseup(function(e){
        	$(this).attr('src','/microsites/barnet/sign-pledge.jpg');
        }).mousedown(function(e){
        	$(this).attr('src','/microsites/barnet/sign-pledge-depressed.jpg');
        }).hover(
          function(e){$(this).attr('src','/microsites/barnet/sign-pledge-hover.jpg')},
          function(e){$(this).attr('src','/microsites/barnet/sign-pledge.jpg')}	
        );
    });
</script>
