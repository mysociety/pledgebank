<?
// index.php:
// Front page template for Barnet PledgeBank.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

?>

<div style="font-size: 200%">
<p>Barnet PledgeBank is a site where your council is offering a service,
but needs the help and pledge of residents for the service to be used and
worthwhile.
</div>

<div id="currentpledges">
<?
    global $pb_today;
    $pledges = pledge_get_list("date >= '$pb_today'",
        array('global'=>false, 'main'=>true, 'foreign'=>false)
    );
    print format_pledge_list($pledges, array('firstperson' => 'onlyname'));
?>
</div>

