<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Thread;
use App\Models\Like;
use App\Models\Follower;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

class ThreadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['api_key']);
    }
    
    public function allThreads(Request $request)
    {
        $threads = Thread::withCount('likes') 
        ->with('comments')
        ->orderBy('id' , 'desc')
        ->get();
        
        $likedThreadIds = [];

        if ($request->id){
        $likedThreadIds = Like::where('user_id', $request->id)->pluck('thread_id')->toArray();
        }
            $data = $threads->map(function ($thread) use ($likedThreadIds , $request) {
                    return [
                    'id' => $thread ->id,
                    'content' => $thread->content,
                    'image' => base64_encode($thread->image),
                    'image_type' => $thread->image_type,
                    'creator' => User::findOrFail($thread->creator),
                    'creator_slug' => User::findOrFail($thread->creator)->slug,
                    'likes_count' => count($thread->likes),
                    'is_liked' => in_array($thread->id ,$likedThreadIds),
                    'slug' => $thread->slug,
                    'is_following' => Follower::where('follow_from' , $request->id) ->where('follow_to' , $thread->creator)->first() ? true : false,
                    'comments' => $thread->comments->map(function ($c) {
                        return [
                            'content' => $c->content,
                            'thread_id' => $c->thread->id,
                            'creator' => User::find($c->user_id),
                        ];
                    })
                    ];
            });
        return response()->json(['data' => $data]);

    }

    public function follow(Request $r)
    {


            $follow = Follower::where( 'follow_from' , $r->from)
            ->where('follow_to' , $r->to)->first();

                if ($follow){
                    $follow->delete();
                    return response(['message' => 'unFollow'] ,202);
                }

            $createFollow = new Follower(); 

            $createFollow->follow_from = $r->from;
            $createFollow->follow_to = $r->to;

            $createFollow->save();

                    return response()->json(['message' => 'Followed'] , 201);


    }


    public function isFollowing(Request $r )
{

    $currentUserId = $r->my_id;
    $isFollowing = Follower::where('follow_from', $currentUserId)
                            ->where('follow_to', $r->id)
                            ->exists();

    return response()->json(['is_following' => $isFollowing], 200);
}

    public function deleteComment(Request $r){

        $comment = Comment::findOrFail($r->comment);

        if ($comment){
            $comment->delete();
            $thread = Thread::find($r->id);
            return response()->json($thread->comments->map(function($c) {
                return [
                    'content' => $c->content,
                    'creator' => User::find($c->user_id),
                    'id' => $c->id
            
                ];
            }) ,200);
        }
        
        return response()->json(['message' => 'Not Fount Comment'], 404);
    }
}
