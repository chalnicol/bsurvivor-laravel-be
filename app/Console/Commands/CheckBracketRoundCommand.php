<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckBracketRoundCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-bracket-round-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roundId = $this->argument('roundId');

        $this->info("Checking bracket entries for round ID: {$roundId}...");

        // 1. Get the actual winners for the specified round
        $roundMatchups = Matchup::where('round_id', $roundId)
            ->whereNotNull('winner_team_id')
            ->get();

        if ($roundMatchups->isEmpty()) {
            $this->error("No completed matchups found for round ID: {$roundId}.");
            return Command::FAILURE;
        }

        // 2. Get all active bracket entries
        $activeEntries = BracketEntry::with('predictions')
            ->where('status', 'active')
            ->get();

        $eliminatedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($activeEntries as $entry) {
                foreach ($roundMatchups as $matchup) {
                    $prediction = $entry->predictions->firstWhere('matchup_id', $matchup->id);

                    // If the prediction is missing or incorrect, eliminate the user.
                    if (!$prediction || $prediction->predicted_winner_team_id !== $matchup->winner_team_id) {
                        $entry->status = 'eliminated';
                        $entry->last_round_survived = $roundId - 1;
                        $entry->save();
                        $eliminatedCount++;
                        break; // Stop checking this entry and move to the next.
                    }
                }
            }

            // 3. Optional: Check for a single winner if it's the final round
            // This logic can be more complex, but a simple check would be:
            // if ($roundMatchups->count() === 1 && $activeEntries->count() === 1) {
            //     $activeEntries->first()->update(['status' => 'won']);
            // }

            DB::commit();

            $this->info("Round check complete. Eliminated {$eliminatedCount} entries.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
