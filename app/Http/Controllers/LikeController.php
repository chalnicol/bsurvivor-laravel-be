<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\BracketChallengeEntry;
use App\Models\BracketChallenge;

use App\Models\Like;


class LikeController extends Controller
{
    public function toggleVote(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $isLike = (bool) $request->input('is_like');
        $likeableId = $request->input('likeable_id');
        $modelName = $request->input('model_name');

        $likeableType = 'App\\Models\\' . $modelName;

        // Validate the input and retrieve the model instance
        if (!$likeableId || !$likeableType || !class_exists($likeableType)) {
            return response()->json(['message' => 'Invalid likeable item.'], 400);
        }
       
        $likeable = $likeableType::find($likeableId);

        if (!$likeable) {
            return response()->json(['message' => 'Likeable item not found.'], 404);
        }

        // Existing vote logic remains the same
        $existingLike = $likeable->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            if ($existingLike->is_like == $isLike) {
                $existingLike->delete();
            } else {
                $existingLike->update(['is_like' => $isLike]);
            }
        } else {
            $likeable->likes()->create([
                'user_id' => $user->id,
                'is_like' => $isLike
            ]);
        }

        $votes = [
            'likes' => $likeable->likes()->where('is_like', true)->count(),
            'dislikes' => $likeable->likes()->where('is_like', false)->count(),
        ];

        return response()->json(['votes' => $votes]);
    }

    // public function toggleVote(Request $request, Comment $likeable)
    // {
    //     // $userId = $request->user()->id;

    //     $user = Auth::guard('sanctum')->user();

    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'User not found.'
    //         ], 404);
    //     }

    //     $isLike = (bool) $request->input('is_like');

    //     $existingLike = $likeable->likes()->where('user_id', $user->id)->first();

    //     if ($existingLike) {
    //         if ($existingLike->is_like == $isLike) {
    //             // User is un-liking/disliking
    //             $existingLike->delete();
    //         } else {
    //             // User is changing their vote
    //             $existingLike->update(['is_like' => $isLike]);
    //         }
    //     } else {
    //         // User is liking/disliking for the first time
    //         $likeable->likes()->create([
    //             'user_id' => $user->id,
    //             'is_like' => $isLike
    //         ]);
    //     }

    //     $votes = [
    //         'likes' => $likeable->likes()->where('is_like', true)->count(),
    //         'dislikes' => $likeable->likes()->where('is_like', false)->count(),
    //     ];

    //     // Return updated counts
    //     return response()->json([
    //         'votes' => $votes
    //     ]);
    // }

}
