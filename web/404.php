<?php

require_once '../phplib/pb.php';
require_once '../phplib/page.php';

$uri = substr($_SERVER['REQUEST_URI'], 1);
page_check_ref($uri);

page_header(_('Page Not Found'));
print p(_("We couldn't find the page you were looking for."));
page_footer();

?>
