<?php

declare(strict_types=1);

use App\Livewire\Campaign\Show as CampaignShow;
use App\Livewire\CampaignTracker\Index as CampaignTrackerIndex;
use App\Livewire\CampaignTracker\Report as CampaignTrackerReport;
use App\Livewire\CampaignTracker\Search as CampaignTrackerSearch;
use App\Livewire\Home;
use App\Livewire\Refer\Index as ReferIndex;
use App\Livewire\Refer\Show as ReferShow;
use App\Livewire\ReferTracker\Index as ReferTrackerIndex;
use App\Livewire\ReferTracker\Report as ReferTrackerReport;
use App\Livewire\ReferTracker\Search as ReferTrackerSearch;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Main Application Routes
|--------------------------------------------------------------------------
*/

// Home page - Displays the list of all available campaigns.
Route::get('/', Home::class)->name('home');

// Auth
Route::redirect('/login', '/admin/login')->name('login');

// Campaign
Route::get('/camp/{campaign_or_refer_id}', CampaignShow::class)->name('campaign.show');

// Refer
Route::get('/refer', ReferIndex::class)->name('refer.index');
Route::get('/refer/{campaign}', ReferShow::class)->name('refer.show');

// Campaign Tracker
Route::get('/camp-tracker', CampaignTrackerIndex::class)->name('campaign-tracker.index');
Route::get('/camp-tracker/{campaign}', CampaignTrackerSearch::class)->name('campaign-tracker.search');
Route::get('/camp-tracker-report/{campaign}/{upi}', CampaignTrackerReport::class)->name('campaign-tracker.report');

// Refer Tracker
Route::get('/refer-tracker', ReferTrackerIndex::class)->name('refer-tracker.index');
Route::get('/refer-tracker/{campaign}', ReferTrackerSearch::class)->name('refer-tracker.search');
Route::get('/refer-tracker-report/{refer}', ReferTrackerReport::class)->name('refer-tracker.report');

/*
|--------------------------------------------------------------------------
| Short Domain Routes
|--------------------------------------------------------------------------
|
| All routes within this group will automatically use the short domain
| defined in the `app.url_short` config value.
|
*/

/**
 * The domain for short URLs, cast to a string to satisfy static analysis.
 *
 * @var string $shortDomain
 */
$shortDomain = config('app.url_short') ?? '';

Route::domain($shortDomain)->group(function (): void {
    // Campaign
    Route::get('/c/{campaign_or_refer_id}', CampaignShow::class)->name('short.campaign.show');

    // Refer
    Route::get('/r/{campaign}', ReferShow::class)->name('short.refer.show');

    // Campaign Tracker
    Route::get('/ct/{campaign}', CampaignTrackerSearch::class)->name('short.campaign-tracker.search');

    // Refer Tracker
    Route::get('/rt/{campaign}', ReferTrackerSearch::class)->name('short.refer-tracker.search');
    Route::get('/rtr/{refer}', ReferTrackerReport::class)->name('short.refer-tracker.report');
});
