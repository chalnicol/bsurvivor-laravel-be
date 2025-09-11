<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\BracketChallengeEntry;
use App\Models\BracketChallenge;

use App\Models\Like;

use App\Notifications\ReactedToResource;

 use Carbon\Carbon;

class LikeController extends Controller
{

    // public function toggleVote(Request $request)
    // {
    //     $user = Auth::guard('sanctum')->user();

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found.'], 404);
    //     }

    //     $isLike = (bool) $request->input('is_like');
    //     $likeableId = $request->input('likeable_id');
    //     $modelName = $request->input('model_name');

    //     $likeableType = 'App\\Models\\' . $modelName;

    //     // Validate the input and retrieve the model instance
    //     if (!$likeableId || !$likeableType || !class_exists($likeableType)) {
    //         return response()->json(['message' => 'Invalid likeable item.'], 400);
    //     }
       
    //     $likeable = $likeableType::find($likeableId);

    //     if (!$likeable) {
    //         return response()->json(['message' => 'Likeable item not found.'], 404);
    //     }

    //     // Existing vote logic remains the same
    //     $existingLike = $likeable->likes()->where('user_id', $user->id)->first();

    //     if ($existingLike) {
    //         if ($existingLike->is_like == $isLike) {
    //             $existingLike->delete();
    //         } else {
    //             $existingLike->update(['is_like' => $isLike]);
    //         }
    //     } else {
    //         $likeable->likes()->create([
    //             'user_id' => $user->id,
    //             'is_like' => $isLike
    //         ]);
    //     }

    //     $votes = [
    //         'likes' => $likeable->likes()->where('is_like', true)->count(),
    //         'dislikes' => $likeable->likes()->where('is_like', false)->count(),
    //     ];

    //     return response()->json(['votes' => $votes]);
    // }

   

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

        // Existing vote logic to update the database
        $existingLike = $likeable->likes()->where('user_id', $user->id)->first();
        $voteChanged = false;

        if ($existingLike) {
            
            // if (Carbon::now('UTC')->diffInSeconds($existingLike->updated_at) < 30 ) {
            //     return response()->json([
            //         'message' => 'You can only vote once every 30 seconds.',
            //         'remaining_seconds' => 30 - Carbon::now('UTC')->diffInSeconds($existingLike->updated_at),
                
            //     ], 429);
            // }

            if ($existingLike->is_like == $isLike) {
                $existingLike->delete();
                // $voteChanged = true;
            } else {
                $existingLike->update(['is_like' => $isLike]);
                $voteChanged = true;
            }
        } else {
            $likeable->likes()->create([
                'user_id' => $user->id,
                'is_like' => $isLike
            ]);
            $voteChanged = true;
        }

        // Now, apply the session-based rate limit **only for the notification**
        if ($voteChanged && $modelName !== 'BracketChallenge') {

            //$url = method_exists($likeable, 'getUrl') ? $likeable->getUrl() : '/';
            //$resourceType = method_exists($likeable, 'getDisplayName') ? $likeable->getDisplayName() : $modelName;
            $resourceOwner = $likeable->user;

            $username = $resourceOwner->id === $user->id ? 'You' : $user->username;

            $url = "";
            $modelNameNew =  "";

            switch ($modelName) {
                case 'BracketChallengeEntry':
                    $url = '/bracket-challenge-entries/' . $likeable->slug;
                    $modelNameNew = 'bracket challenge entry';

                    break;
                case 'Comment':
                    if ( $likeable->commentable_type === 'App\\Models\\BracketChallengeEntry' ) {
                        $url = '/bracket-challenge-entries/' . $likeable->commentable->slug;
                    } else {
                        $url = '/bracket-challenges/' . $likeable->commentable->slug;
                    }
                    $modelNameNew = 'comment';
                    break;
                default:
                    $url = "";
                    $modelNameNew = $modelName;
                    break;
            }

            $resourceOwner->notify(new ReactedToResource($resourceOwner->id, $username, $modelNameNew, $isLike ? 'like' : 'dislike', $url));
                
            
        }

        $votes = [
            'likes' => $likeable->likes()->where('is_like', true)->count(),
            'dislikes' => $likeable->likes()->where('is_like', false)->count(),
        ];

        return response()->json(['votes' => $votes]);
    }


    


}
