<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfferRoutingFlow extends Model
{
    protected $fillable = ['name', 'entry_offer_id', 'fallback_offer_id', 'steps', 'is_active'];
    protected $casts = ['steps' => 'array', 'is_active' => 'boolean'];

    public function entryOffer()
    {
        return $this->belongsTo(Offer::class, 'entry_offer_id', 'idoffer');
    }

    public function fallbackOffer()
    {
        return $this->belongsTo(Offer::class, 'fallback_offer_id', 'idoffer');
    }
}
