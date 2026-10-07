<?php

namespace App\Http\Controllers;

use App\Offer;
use App\OfferRoutingFlow;
use App\Support\OfferFlowRouter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LeadMax\TrackYourStats\Offer\Rules\Geo;

class OfferRoutingFlowController extends Controller
{
    public function index()
    {
        return view('offer-flows.index', ['flows' => OfferRoutingFlow::with(['entryOffer', 'fallbackOffer'])->orderBy('name')->get()]);
    }

    public function create()
    {
        return $this->editor(new OfferRoutingFlow(['steps' => [], 'is_active' => false]));
    }

    public function edit(OfferRoutingFlow $flow)
    {
        return $this->editor($flow);
    }

    private function editor(OfferRoutingFlow $flow)
    {
        return view('offer-flows.editor', [
            'flow' => $flow,
            'offers' => Offer::orderBy('offer_name')->get(['idoffer', 'offer_name', 'status']),
            'countries' => Geo::$countries,
        ]);
    }

    public function store(Request $request)
    {
        $flow = OfferRoutingFlow::create($this->validated($request));
        return redirect()->route('offer-flows.edit', $flow)->with('flow_saved', 'Flow saved.');
    }

    public function update(Request $request, OfferRoutingFlow $flow)
    {
        $flow->update($this->validated($request, $flow));
        return redirect()->route('offer-flows.edit', $flow)->with('flow_saved', 'Flow saved.');
    }

    public function duplicate(OfferRoutingFlow $flow)
    {
        $copy = $flow->replicate();
        $copy->name = mb_substr($flow->name, 0, 143).' (copy)';
        $copy->entry_offer_id = null;
        $copy->is_active = false;
        $copy->save();
        return redirect()->route('offer-flows.edit', $copy)->with('flow_saved', 'Copy created as a draft. Choose its entry offer before enabling it.');
    }

    public function destroy(OfferRoutingFlow $flow)
    {
        $flow->delete();
        return redirect()->route('offer-flows.index')->with('flow_saved', 'Flow deleted. Its entry offer now uses its existing rules.');
    }

    public function preview(Request $request)
    {
        $data = $this->validated($request, null, true);
        $request->validate(['country' => ['nullable', Rule::in(array_merge(array_keys(Geo::$countries), ['UNKNOWN']))]]);
        $result = OfferFlowRouter::resolve($data['steps'], $data['fallback_offer_id'], $request->input('country'));
        $result['offer_name'] = Offer::findOrFail($result['offer_id'])->offer_name;
        return response()->json($result);
    }

    private function validated(Request $request, ?OfferRoutingFlow $flow = null, bool $preview = false): array
    {
        $offer = fn () => Rule::exists('offer', 'idoffer')->where('status', 1);
        $rules = [
            'steps' => ['required', 'array', 'min:1', 'max:50'],
            'steps.*.offer_id' => ['required', 'integer', 'distinct', $offer()],
            'steps.*.allow_all_countries' => ['sometimes', 'boolean'],
            'steps.*.countries' => ['sometimes', 'array', 'max:250'],
            'steps.*.countries.*' => ['required', 'string', Rule::in(array_keys(Geo::$countries))],
            'fallback_offer_id' => ['required', 'integer', $offer()],
        ];
        if (!$preview) {
            $rules += [
                'name' => ['required', 'string', 'max:150'],
                'is_active' => ['required', 'boolean'],
                'entry_offer_id' => ['nullable', 'required_if:is_active,1', 'integer', $offer(), Rule::unique('offer_routing_flows', 'entry_offer_id')->ignore($flow?->id)],
            ];
        }
        $data = $request->validate($rules);
        foreach ($data['steps'] as $index => $step) {
            if (empty($step['allow_all_countries']) && empty($step['countries'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "steps.$index.countries" => 'Choose at least one country or enable Allow all countries.',
                ]);
            }
        }
        // Store only the supported fields, even when a client supplies extra step keys.
        $data['steps'] = array_map(fn ($step) => [
            'offer_id' => (int) $step['offer_id'],
            'countries' => !empty($step['allow_all_countries']) ? [] : array_values(array_unique($step['countries'])),
        ] + (!empty($step['allow_all_countries']) ? ['allow_all_countries' => true] : []), array_values($data['steps']));
        return $data;
    }
}
