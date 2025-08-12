<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Validation\Rule;

use App\Http\Resources\UserResource; 

use App\Models\User;
use App\Models\BracketChallenge;
use App\Models\BracketChallengeEntry;
use App\Models\BracketChallengeEntryPrediction;

use App\Http\Resources\BracketChallengeEntryResource;
use App\Http\Resources\RoundResourceCustom;

use Carbon\Carbon;

class ProfileController extends Controller
{

    public function get_bracket_challenge_entries(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required.'
            ], 401);
        }

        $query = $user->entries()
            ->with('bracketChallenge.league');

        if ($request->filled('search')) {
            $searchTerm = '%' . strtolower(trim($request->input('search'))) . '%';

            $query->where(function ($q) use ($searchTerm) {
                // 1. Search by Bracket Entry Name
                $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(status) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(MONTHNAME(created_at)) LIKE ?', [$searchTerm])
                    ->orWhereRaw('YEAR(created_at) LIKE ?', [$searchTerm]);

                $q->orWhereHas('bracketChallenge', function ($challengeQuery) use ($searchTerm) {
                    $challengeQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                });

                // 2. Search by League Name OR Abbreviation
                $q->orWhereHas('bracketChallenge.league', function ($leagueQuery) use ($searchTerm) {
                    $leagueQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                                ->orWhereRaw('LOWER(abbr) LIKE ?', [$searchTerm]);
                });
            });
        }

        $bracketChallengeEntries = $query->orderBy('created_at', 'desc')->paginate(10);

        return BracketChallengeEntryResource::collection($bracketChallengeEntries);
    }

    public function post_bracket_challenge_entry(Request $request) 
    {
        $userId = Auth::id();

        $bracketChallengeId = $request->input('bracket_challenge_id');

        //get bracket challenge
        $bracketChallenge = BracketChallenge::where('id', $bracketChallengeId)
            ->where('is_public', true)
            ->where('start_date', '<=', Carbon::now()->toDateString())
            ->where('end_date', '>=', Carbon::now()->toDateString())
            ->first();

        if ( !$bracketChallenge ) {
            return response()->json([
                'message' => 'Bracket challenge not found.',
            ], 404);
        }

        $request->validate([
            'bracket_challenge_id' => [
                'required',
                'integer',
                'exists:bracket_challenges,id',
                 Rule::unique('bracket_challenge_entries')->where(function ($query) use ($userId, $bracketChallengeId) {
                    $query->where('user_id', $userId)
                          ->where('bracket_challenge_id', $bracketChallengeId);
                })
            ],
            // 'entry_data' => 'required|array',
            'predictions' => 'required|array',
            'predictions.*.matchup_id' => 'required|exists:matchups,id',
            'predictions.*.predicted_winner_team_id' => 'required|exists:teams,id',
            'predictions.*.teams' => 'required|array|size:2',
            'predictions.*.teams.*.id' => 'required|integer|exists:teams,id',
        ], [
            'bracket_challenge_id.unique' => 'You have already submitted an entry for this bracket challenge.',
            'predictions.matchup_id.exists' => 'Invalid matchup ID',
            'predictions.teams.exists' => 'Invalid team ID',
            'predictions.predicted_winner_team_id.exists' => 'Invalid predicted winner team ID',
            'predictions.teams.size' => 'Only two teams can be in a matchup',
            'predictions.teams.*.id.exists' => 'One or all team ids are invalid',
        ]);

        
        $padded_user_id = str_pad(Auth::id(), 3, '0', STR_PAD_LEFT);

        $padded_challenge_id = str_pad($request->bracket_challenge_id, 4, '0', STR_PAD_LEFT);


        $name = 'BCE-'. $padded_challenge_id . '-'  . Str::upper(Str::random(5)) . $padded_user_id;


        DB::beginTransaction();

        try {
            // 3. Create the main bracket entry record for the user.
            $entry = BracketChallengeEntry::create([
                'name' => $name,
                'user_id' => auth()->id(),
                'bracket_challenge_id' => $bracketChallengeId,
                'status' => 'active',
                'last_round_survived' => 0,
                'slug' => Str::slug($name),
            ]);

            // 4. Prepare the predictions for saving.
            $predictions = collect(request()->input('predictions'))->map(function ($prediction) use ($entry) {
                return new BracketChallengeEntryPrediction([
                    'bracket_challenge_entry_id' => $entry->id,
                    'matchup_id' => $prediction['matchup_id'],
                    'predicted_winner_team_id' => $prediction['predicted_winner_team_id'],
                    'teams' => $prediction['teams'],
                ]);
            });

            // 5. Save all predictions at once using Eloquent's saveMany method.
            $entry->predictions()->saveMany($predictions);

            // 6. If everything succeeded, commit the transaction to make the changes permanent.
            DB::commit();

            return response()->json([
                'message' => 'Your bracket entry has been saved successfully!'
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // 7. If any part of the process failed, roll back the transaction.
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while saving your bracket. Please try again.'
            ], 500); // 500 Internal Server Error
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        $request->validate([
            'username' => ['nullable', 'string', 'min:5', 'max:15', 'unique:users,username,' . $user->id], // Example for username
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id], // Example for email
            // Add other validation rules for fields you allow to be updated
        ]);

        // Only update fields that are present in the request
        if ($request->has('username')) {
            $user->username = $request->username;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        // ... update other fields as needed

        $user->save();

        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => new UserResource($user) // Return the updated user data
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('The provided password does not match your current password.');
                }
            }],
            'password' => ['required', 'confirmed', 'min:8', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully!']);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        // Delete the user record
        $user->delete();

        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Exception $e) {
            // Log this, but don't fail the deletion if session cannot be invalidated for some reason.
            // This can happen if the session truly wasn't active or was already cleared.
            \Log::error("Failed to invalidate session during account deletion: " . $e->getMessage());
        }

        return response()->json(['message' => 'Account deleted successfully!']);
    }


}