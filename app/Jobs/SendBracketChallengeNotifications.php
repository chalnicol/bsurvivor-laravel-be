<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BracketChallenge;
use App\Notifications\BracketEntryUpdated;

class SendBracketChallengeNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $challengeId;

    public function __construct(int $challengeId)
    {
        $this->challengeId = $challengeId;
    }

    public function handle()
    {
        $challenge = BracketChallenge::find($this->challengeId);

        if (!$challenge) {
            return;
        }

        $challenge->entries()->chunk(200, function($entries) {
            foreach ($entries as $entry) {
                if ($entry->user) {
                    $entry->user->notify(new BracketEntryUpdated($entry));
                }
            }
        });
    }
}
