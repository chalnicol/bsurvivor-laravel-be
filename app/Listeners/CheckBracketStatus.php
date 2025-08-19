<?php

namespace App\Listeners;

use App\Events\BracketChallengeUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\CheckEntryStatus;

class CheckBracketStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
     public function handle(BracketChallengeUpdated $event): void
    {
        $bracketChallenge = $event->bracketChallenge;

        $bracketChallenge->entries()->chunk(100, function($entries) use ($bracketChallenge) {
            CheckEntryStatus::dispatch($entries, $bracketChallenge);
        });
    }
}
