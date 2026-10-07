@extends('layouts.master')
@section('content')
<link rel="stylesheet" href="{{ asset('css/offer-flows.css') }}?v={{ filemtime(public_path('css/offer-flows.css')) }}">
<div class="right_panel flow-editor">
    <nav class="rl-inline-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('offer-flows.index') }}">Offer Routing Flows</a><span aria-hidden="true">›</span><span>{{ $flow->exists ? 'Edit flow' : 'New flow' }}</span></nav>
    <div class="rl-page-heading"><div><h1>{{ $flow->exists ? $flow->name : 'New routing flow' }}</h1><p>Work down the list until the click’s country matches an offer.</p></div></div>
    @if(session('flow_saved'))<div class="rl-settings-message" role="status">{{ session('flow_saved') }}</div>@endif
    @if(session('flow_rule_skips'))
        <div class="rl-form-errors" role="alert"><strong>Some individual offer rules were skipped.</strong>
            <p>Those offers keep their existing rules, so their direct-click routing may differ from this flow.</p>
            <ul>@foreach(session('flow_rule_skips') as $offerId => $reasons)<li><a href="/offer_edit_rules.php?offid={{ $offerId }}">Offer #{{ $offerId }} — review rules</a>: {{ implode(' ', $reasons) }}</li>@endforeach</ul>
        </div>
    @endif
    @if($errors->any())<div class="rl-form-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form id="flow-form" method="post" action="{{ $flow->exists ? route('offer-flows.update', $flow) : route('offer-flows.store') }}">
        @csrf @if($flow->exists) @method('PUT') @endif
        <section class="rl-card flow-section">
            <h2>1. Choose the entry offer</h2>
            <div class="flow-grid"><label>Flow name<input class="form-control" name="name" value="{{ old('name', $flow->name) }}" maxlength="150" required placeholder="Dates Cozy — Country Routing"></label>
            <label>Entry offer<input class="form-control" type="search" data-entry list="flow-offers" placeholder="Search by offer name or ID" autocomplete="off"><input type="hidden" name="entry_offer_id" data-entry-id></label></div>
            <p class="help-block">Only clicks entering through this offer use this flow. You can leave a draft unassigned.</p>
            <input type="hidden" name="is_active" value="0"><label class="flow-toggle"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $flow->is_active))> Enable this flow</label>
        </section>
        <section class="rl-card flow-section">
            <div class="flow-section-heading"><div><h2>2. Arrange offers by priority</h2><p>Drag the handle or use the arrows. Countries repeated below an earlier match go to that earlier offer.</p></div><button class="rl-button" type="button" data-add-step>＋ Add offer</button></div>
            <div data-steps></div>
            <p data-overlap role="status" class="help-block"></p>
            <label class="flow-fallback">Final fallback offer<input type="search" class="form-control" data-fallback list="flow-offers" placeholder="Choose an offer for unmatched or unknown countries" required autocomplete="off"><input type="hidden" name="fallback_offer_id" data-fallback-id></label>
            <p class="help-block">All unmatched countries, including an unknown location, use this fallback.</p>
        </section>
        <section class="rl-card flow-section">
            <h2>3. Test a country</h2>
            <div class="flow-test"><select class="form-control" data-test-country aria-label="Country to test"><option value="UNKNOWN">Unknown country</option>@foreach($countries as $code => $name)<option value="{{ $code }}">{{ $code }} — {{ $name }}</option>@endforeach</select><button type="button" class="rl-button" data-test>Test routing</button></div>
            <p data-test-result role="status" aria-live="polite">Test your current edits before saving.</p>
            <p class="help-block">The test checks country matching. Live clicks still require an active destination and affiliate access. Destination caps, device rules and repeat-click rules can redirect further. For clicks using this flow, its country list replaces the destination’s GEO rules.</p>
        </section>
        <section class="rl-card flow-section">
            <h2>4. Create individual offer rules (optional)</h2>
            <p>Use <strong>Save flow &amp; create offer rules</strong> to create a GEO rule under each offer: allow its selected countries, otherwise redirect to the next offer. The last offer redirects to the final fallback. This also works for flows you saved previously.</p>
            <p class="help-block">These rules are active for all direct traffic to each offer, even when this flow is paused. “Allow all countries” creates a rule that accepts unknown locations too. Generating again updates this flow’s rules and removes its generated rules for rows you removed. Normal Save flow does not change individual rules. Deleting the flow leaves its individual rules in place.</p>
            <label class="flow-toggle"><input type="checkbox" name="replace_geo_rules" value="1" @checked(old('replace_geo_rules'))> Deactivate existing GEO rules on these offers</label>
            <p class="help-block">Exact active matches are kept, even when this box is checked. Matching compares allowed countries, redirect offer and caps—not the rule name. Only different manual GEO rules are deactivated. Offers with different existing rules are skipped and reported while the other offers are processed. Different rules from another flow cannot be overwritten. New rules are named by their allowed countries.</p>
            @if(isset($generatedRules) && $generatedRules->isNotEmpty())
                <h3>Previously generated rules</h3>
                <p class="help-block">These are the last generated rules. Use the create offer rules button to apply your current edits.</p>
                <div class="rl-table-scroll"><table class="table"><thead><tr><th>Offer</th><th>Rule</th><th>Redirect if unmatched</th><th>Status</th></tr></thead><tbody>
                    @foreach($generatedRules as $rule)
                        <tr><td><a href="/offer_edit_rules.php?offid={{ $rule->offer_idoffer }}">Offer #{{ $rule->offer_idoffer }} — view rules</a></td><td>{{ $rule->name }}</td><td>#{{ $rule->redirect_offer }}</td><td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>
        <div data-step-inputs></div>
        <div class="flow-actions"><a class="rl-button" href="{{ route('offer-flows.index') }}">Back to flows</a><button class="rl-button" type="submit">Save flow</button><button class="rl-button rl-primary" type="submit" name="create_offer_rules" value="1">Save flow &amp; create offer rules</button></div>
    </form>
    <datalist id="flow-offers">@foreach($offers as $offer)@if($offer->status)<option value="{{ $offer->idoffer.' — '.html_entity_decode($offer->offer_name, ENT_QUOTES, 'UTF-8') }}"></option>@endif @endforeach</datalist>
    <template id="flow-step-template">
        <article class="flow-step">
            <div class="flow-step-heading"><button type="button" class="flow-drag btn" draggable="true" aria-label="Drag to reorder offer" title="Drag to reorder">⠿</button><strong data-position></strong><div class="flow-row-actions"><button type="button" class="btn btn-sm" data-up aria-label="Move offer up">↑</button><button type="button" class="btn btn-sm" data-down aria-label="Move offer down">↓</button><button type="button" class="btn btn-sm" data-remove>Remove</button></div></div>
            <div class="flow-grid"><label>Offer<input type="search" class="form-control" data-offer list="flow-offers" placeholder="Search offers…" required autocomplete="off"></label>
            <div><label class="flow-toggle"><input type="checkbox" data-allow-all> Allow all countries</label><p class="help-block">Includes unknown locations. Later offers will not receive clicks that reach this step.</p><div data-country-controls><label>Accepted countries<textarea class="form-control" data-codes rows="3" placeholder="AT, BE, CH, DE, NL" required autocomplete="off"></textarea></label><p class="help-block">Paste codes separated by spaces, commas, or line breaks.</p><div class="flow-chips" data-chips></div><label class="sr-only">Add a country<select data-country-picker></select></label><button type="button" class="btn btn-sm" data-suggest>Suggest from offer name</button><p class="help-block" data-suggestion></p></div></div></div>
        </article>
    </template>
</div>
<script type="application/json" id="flow-data">{!! json_encode(['offers' => $offers, 'countries' => $countries, 'steps' => old('steps', $flow->steps), 'entry' => old('entry_offer_id', $flow->entry_offer_id), 'fallback' => old('fallback_offer_id', $flow->fallback_offer_id), 'previewUrl' => route('offer-flows.preview')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
@section('footer')
<script src="{{ asset('js/offer-flows.js') }}?v={{ filemtime(public_path('js/offer-flows.js')) }}" defer></script>
@endsection
