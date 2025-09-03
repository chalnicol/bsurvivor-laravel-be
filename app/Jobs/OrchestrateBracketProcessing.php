<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BracketChallenge;
use App\Models\BracketChallengeEntry;
use Illuminate\Support\Facades\Bus;

class OrchestrateBracketProcessing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $challengeId;

    public function __construct(int $challengeId)
    {
        $this->challengeId = $challengeId;
    }

    public function handle(): void
    {
        $challenge = BracketChallenge::find($this->challengeId);
        
        if (!$challenge) {
            return; // Challenge was deleted, no need to proceed.
        }

        $jobs = [];

        $challenge->entries()->select('id')->chunkById(100, function ($entries) use (&$jobs) {
            $jobs[] = new ProcessEntryChunk($entries->pluck('id')->toArray());
        });

        Bus::batch($jobs)
            ->then(function () use ($challenge) {
                // This closure runs after ALL jobs in the batch have completed.
                SendBracketChallengeNotifications::dispatch($challenge->id);
            })
            ->dispatch();
    }
}
