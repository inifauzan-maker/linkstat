<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageEvent extends Model
{
    public const PAGE_VIEW = 'page_view';
    public const CTA_CLICK = 'cta_click';
    public const LINK_CLICK = 'link_click';

    protected $fillable = [
        'landing_page_id',
        'landing_page_link_id',
        'event_type',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'clicked_url',
    ];

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(LandingPageLink::class, 'landing_page_link_id');
    }
}
