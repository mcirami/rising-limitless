<?php
//ini_set('display_errors', 1);

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('layouts.partials.network-favicon')
    <link href="{{$webroot}}css/bootstrap.min.css" rel="stylesheet">
    <!--    <link href="css/bootstrap-theme.min.css" rel="stylesheet">-->
    <link href="{{$webroot}}css/animate.css" rel="stylesheet">


    <link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/default.css?v=1.8"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/tablesorter.default.css"/>
    <link rel="stylesheet" href="<?php echo $webroot; ?>css/company.css">
    <link href="<?php echo $webroot; ?>css/responsive_table.css?v=1.1" rel="stylesheet" type="text/css"/>
    <link href="<?php echo $webroot; ?>css/drawer.min.css?v=1.3" rel="stylesheet">

    <link href="<?php echo $webroot; ?>css/jquery-ui-timepicker-addon.css" rel="stylesheet" type="text/css"/>

    <link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>css/font-awesome/css/all.css">
    <link rel="stylesheet" href="<?php echo $webroot; ?>css/magic.min.css">

    <script type="text/javascript" src="<?php echo $webroot; ?>js/moment.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/jquery_2.1.3_jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/jquery-ui.min.js"></script>

    <script type="text/javascript" src="<?php echo $webroot; ?>js/jscolor.min.js"></script>
    <link rel="stylesheet" href="{{$webroot}}css/jquery-ui.min.css"/>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/main.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/drawer.js"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/iscroll.min.js"></script>

    <script type="text/javascript" src="<?php echo $webroot; ?>js/tables.js?v=1.1"></script>
    <script type="text/javascript" src="<?php echo $webroot; ?>js/bootstrap-notify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

@if(!env('APP_DEBUG') && env('APP_ENV') == 'production')
    <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-127417577-1"></script>
        <script>window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', 'UA-127417577-1');</script>
    @endif


    <title><?php echo \LeadMax\TrackYourStats\System\Company::loadFromSession()->getShortHand(); ?></title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ $webroot }}css/network.css?v={{ filemtime(public_path('css/network.css')) }}">
    <script src="{{ $webroot }}js/network.js?v={{ filemtime(public_path('js/network.js')) }}" defer></script>
</head>

<body class="rl-app">
@include('layouts.partials.network-shell')
<main class="panels_wrap" id="network-content" tabindex="-1">

    @include('layouts.errors')

    <div id="app">

    </div>
    @yield('content')
    @if(isset($notify))
        <?php \LeadMax\TrackYourStats\System\Notify::info($notify, ''); ?>
    @endif


    @if(isset($message))
        <?php \LeadMax\TrackYourStats\System\Notify::info($message, ''); ?>
    @endif

</main>

@include('layouts.footer')

@yield('footer')

</body>
</html>
