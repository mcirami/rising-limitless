@extends('layouts.master')
@section('content')
<div class="right_panel rl-settings">
    <div class="rl-page-heading"><div><h1>Network settings</h1><p>Company details, brand colors, and appearance</p></div></div>
    @if(session('settings_saved'))<div class="rl-settings-message" role="status">{{ session('settings_saved') }}</div>@endif
    @if($errors->any())<div class="rl-settings-message" role="alert"><strong>Changes were not saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(!$company)<div class="rl-settings-message" role="alert">No company record matches this installation. Saving is disabled until the database company record is configured.</div>@endif
    <form method="post" action="/settings.php" id="network-settings-form">
        @csrf
        <fieldset @disabled(!$company)>
        <div class="rl-settings-grid">
            <section class="rl-card rl-settings-card"><h2>Company details</h2><p>Shared across the network. Appearance mode is a separate preference for each browser.</p>
                @foreach(['shortHand'=>'Network name', 'email'=>'Contact email', 'skype'=>'Skype', 'login_url'=>'Login address', 'landing_page'=>'Landing page address'] as $name=>$label)
                    <label for="setting-{{ $name }}">{{ $label }}</label>
                    <input id="setting-{{ $name }}" name="{{ $name }}" type="{{ $name === 'email' ? 'email' : 'text' }}" value="{{ old($name, $company->$name ?? '') }}" maxlength="{{ in_array($name,['shortHand','skype']) ? 100 : 255 }}" @required($name === 'shortHand')>
                @endforeach
                <p class="rl-settings-help">Addresses do not configure DNS or your web server. Leave them unchanged unless you are changing your network’s domains.</p>
            </section>
            <section class="rl-card rl-settings-card"><h2>Appearance</h2><p>Brand colors apply to the signed-in workspace in both modes. Dark mode keeps its own readable backgrounds and text. The public landing page retains its separate design.</p>
                <div class="rl-settings-colors">
                @foreach(\App\Support\NetworkTheme::LABELS as $i=>$label)
                    <label class="rl-settings-color" for="setting-color-{{ $i }}"><span>{{ $label }}</span><input type="color" id="setting-color-{{ $i }}" name="valueSpan{{ $i+1 }}" value="#{{ ltrim(old('valueSpan'.($i+1), $colors[$i]), '#') }}" data-default="#{{ \App\Support\NetworkTheme::DEFAULTS[$i] }}"></label>
                @endforeach
                </div>
                <button type="button" class="rl-button" data-reset-colors>Use redesign colors</button><p class="rl-settings-help">Reset fills the form only. Choose Save settings to apply it. Legacy colors remain available for older pages.</p>
            </section>
        </div>
        <div class="rl-settings-actions"><span>Changes apply after saving.</span><button type="submit" class="rl-button rl-primary">Save settings</button></div>
        </fieldset>
    </form>
    <section class="rl-card rl-settings-card"><h2>Brand assets</h2><p>Uploads save separately. The logo appears in the workspace sidebar; the favicon appears in browser tabs.</p>
        <div class="rl-settings-grid">
        @foreach(['logo'=>['PNG logo','file1','.png','image/png'], 'favicon'=>['ICO favicon','file2','.ico','image/x-icon']] as $kind=>$asset)
            <form method="post" action="/upload_{{ $kind }}.php" enctype="multipart/form-data">
                @csrf
                <label for="{{ $asset[1] }}">{{ $asset[0] }} · maximum 2 MB</label><input id="{{ $asset[1] }}" type="file" name="{{ $asset[1] }}" accept="{{ $asset[2] }},{{ $asset[3] }}" required @disabled(!$company)>
                <button type="submit" class="rl-button" @disabled(!$company)>Upload {{ $kind }}</button>
            </form>
        @endforeach
        </div>
    </section>
</div>
@endsection
@section('footer')
<script>document.querySelector('[data-reset-colors]').addEventListener('click', function () { document.querySelectorAll('[data-default]').forEach(function (input) { input.value = input.dataset.default; }); });</script>
@endsection
