<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use App\Http\Resources\UserResource; 
use App\Models\User;
use App\Models\BracketChallengeEntry;
use App\Http\Resources\BracketChallengeEntryResource;

class ProfileController extends Controller
{

    public function get_bracket_challenge_entries () {

        $user = Auth::user();
        if ( !$user ) {
            return response()->json([
                'message' => 'Authentication required.',
            ]);
        }

        $bracketChallengeEntries = $user->bracketChallengeEntries()
            ->with('bracket_challenge')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return BracketChallengeEntryResource::collection($bracketChallengeEntries);

        // $groupedEntries = $bracketChallengeEntries->groupBy(function ($entry) {
        //     // Check if bracket_challenge and league exist to prevent errors
        //     return optional(optional($entry->bracket_challenge)->league)->abbr;
        // });


        // // 3. Format the grouped data for the JSON response
        // $formattedGroupedEntries = [];
        // foreach ($groupedEntries as $leagueName => $entriesCollection) {
        //     // Skip entries that might not have a league (e.g., if relationship is null)
        //     if (is_null($leagueName)) {
        //         continue;
        //     }

        //     // Get the first entry from the collection to access the league details
        //     $firstEntryInGroup = $entriesCollection->first();
        //     $league = optional(optional($firstEntryInGroup->bracket_challenge)->league);

        //     $formattedGroupedEntries[] = [
        //         'league_id'   => $league->id,
        //         'league_name' => $league->name,
        //         // Apply BracketChallengeEntryResource to each entry in this specific league group
        //         'entries'     => BracketChallengeEntryResource::collection($entriesCollection),
        //     ];
        // }

        // // Optional: Sort the leagues by name or ID if desired
        // // usort($formattedGroupedEntries, function($a, $b) {
        // //     return $a['league_name'] <=> $b['league_name'];
        // // });


        // return response()->json([
        //     'message' => 'Bracket Challenge Entries fetched successfully.',
        //     'leagues' => $formattedGroupedEntries, // Return the grouped data
        // ]);
    
    }

    public function post_bracket_challenge_entry(Request $request) 
    {
        $userId = Auth::id();

        $bracketChallengeId = $request->input('bracket_challenge_id');

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
            'entry_data' => 'required|array',
        ], [
            'bracket_challenge_id.unique' => 'You have already submitted an entry for this bracket challenge.',
        ]);

        
        $padded_user_id = str_pad(Auth::id(), 5, '0', STR_PAD_LEFT);

        $padded_challenge_id = str_pad($request->bracket_challenge_id, 5, '0', STR_PAD_LEFT);

        $randomString = Str::random(10);

        $name = 'BCE-'. $padded_challenge_id . '-' . $padded_user_id . '-' . Str::upper($randomString);

        $bracketChallengeEntry = BracketChallengeEntry::create([
            'bracket_challenge_id' => $request->bracket_challenge_id,
            'entry_data' => $request->entry_data,
            'user_id' => Auth::id(),
            'name' => $name,
            'slug' =>  Str::slug($name),
        ]);

        // $bracketChallengeEntry->load('bracket_challenge', 'user');
        $bracketChallengeEntry->load('user');

        return response()->json([
            'message' => 'Entry created successfully!',
            'entry' => new BracketChallengeEntryResource($bracketChallengeEntry)
        ]); 
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