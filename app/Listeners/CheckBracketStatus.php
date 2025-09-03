<?php

namespace App\Listeners;

use App\Events\BracketChallengeUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\CheckEntryStatus;
use App\Jobs\OrchestrateBracketProcessing;

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
        // Dispatch the single orchestrator job.
        OrchestrateBracketProcessing::dispatch($event->bracketChallenge->id);
        
    }
}
