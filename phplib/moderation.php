<?
// moderation.php: functions for moderation
//
// Copyright (c) 2014 Hakim Cassimally for mySociety

/* moderation_redirect 
 * Given a pledge object, check if we need to redirect for moderation.
 * if OPTION_MODERATE_PLEDGES is unset, then return
 * otherwise redirect if the pledge is hidden
 */
function moderation_redirect($p) {
    if (! OPTION_MODERATE_PLEDGES) {
        return;
    }

    if ($p->ishidden()) {
        header( sprintf( 'Location: /fuzzyref.cgi?ref=%s&m=1', $p->ref() ) );
        exit();
    }

    return;
}

?>
