@php $networkFavicon = \LeadMax\TrackYourStats\System\Company::loadFromSession()->getImgDir() . '/favicon.ico'; @endphp
@if(is_file(public_path($networkFavicon)))
<link rel="icon" type="image/x-icon" href="/{{ $networkFavicon }}?v={{ filemtime(public_path($networkFavicon)) }}">
@endif
