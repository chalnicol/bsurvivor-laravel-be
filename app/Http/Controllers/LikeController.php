<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\Like;


class LikeController extends Controller
{
    public function toggleVote(Request $request, Comment $likeable)
    {
        // $userId = $request->user()->id;

        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        $isLike = (bool) $request->input('is_like');

        $existingLike = $likeable->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            if ($existingLike->is_like == $isLike) {
                // User is un-liking/disliking
                $existingLike->delete();
            } else {
                // User is changing their vote
                $existingLike->update(['is_like' => $isLike]);
            }
        } else {
            // User is liking/disliking for the first time
            $likeable->likes()->create([
                'user_id' => $user->id,
                'is_like' => $isLike
            ]);
        }

        $votes = [
            'likes' => $likeable->likes()->where('is_like', true)->count(),
            'dislikes' => $likeable->likes()->where('is_like', false)->count(),
        ];

        // Return updated counts
        return response()->json([
            'votes' => $votes
        ]);
    }

}
