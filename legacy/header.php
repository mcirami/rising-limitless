<?php


ini_set('display_errors', 1);

$webroot = getWebRoot();


//verify user session
$user = new \LeadMax\TrackYourStats\User\User;
if (!$user->verify_login_session()) {
    send_to("login?redirectUri=" . urlencode(findProtocol() . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]));
}


$notifications = new \LeadMax\TrackYourStats\System\Notifications(\LeadMax\TrackYourStats\System\Session::userID());

$notifications->fetchUsersNotifications();

$navBar = new \LeadMax\TrackYourStats\System\NavBar(\LeadMax\TrackYourStats\System\Session::userType(), \LeadMax\TrackYourStats\System\Session::permissions());


?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer-when-downgrade">
    <?php echo view('layouts.partials.network-favicon')->render(); ?>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!--    <link href="css/bootstrap-theme.min.css" rel="stylesheet">-->
    <link href="css/animate.css" rel="stylesheet">

	<meta name="csrf-token" content="<?php echo csrf_token(); ?>">

    <link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/default.css?v=1.6"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/tablesorter.default.css"/>

    <link rel="stylesheet" href="<?php echo $webroot; ?>css/company.css">
    <link href="<?php echo $webroot; ?>css/responsive_table.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo $webroot; ?>css/drawer.min.css" rel="stylesheet">

    <link href="<?php echo $webroot; ?>css/jquery-ui-timepicker-addon.css" rel="stylesheet" type="text/css"/>

    <link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/font-awesome/css/all.css">
    <link rel="stylesheet" href="<?php echo $webroot; ?>css/magic.min.css">
	<link rel="stylesheet" href="css/jquery-ui.min.css"/>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
	<link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/tailwind.css?v=1"/>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/moment.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/jquery_2.1.3_jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/jscolor.min.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/main.js?v=2.5"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/drawer.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/iscroll.min.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/tables.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/bootstrap-notify.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js" defer></script>

	<?php
    if (!env('APP_DEBUG') && env('APP_ENV') == 'production') {
        echo "
           <!-- Global site tag (gtag.js) - Google Analytics -->
           <script async src=\"https://www.googletagmanager.com/gtag/js?id=UA-127417577-1\"></script><script>window.dataLayer = window.dataLayer || [];function gtag()

            {dataLayer.push(arguments);}

            gtag('js', new Date());

            gtag('config', 'UA-127417577-1');</script>
           ";
    }
    ?>


    <title><?php echo \LeadMax\TrackYourStats\System\Company::loadFromSession()->getShortHand(); ?></title>
    <link rel="stylesheet" href="<?= $webroot ?>css/network.css?v=<?= filemtime(public_path('css/network.css')) ?>">
    <script src="<?= $webroot ?>js/network.js?v=<?= filemtime(public_path('js/network.js')) ?>" defer></script>
</head>

<body class="rl-app">
<?php echo view('layouts.partials.network-shell', ['navBar' => $navBar])->render(); ?>
<div class="panels_wrap" id="network-content" role="main" tabindex="-1">
