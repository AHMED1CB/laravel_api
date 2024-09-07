<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\models\User;
use App\models\Like;
use App\models\Thread;
use App\models\Comment;
use App\models\Follower;
use App\Http\Resources\ThreadResource;

class ProfilesController extends Controller
{
    public function getUserBySlug(Request $r)
    {
        

        $user = User::where('slug' , $r->slug)->first();
if ($user){
        return response()->json([
        'user' => [
            'name' => $user->name,
            'image' => $user->image,
            'image_type' => $user->image_type,
            'id' => $user->id,
        ] , 
        'is_followed_by_you' => Follower::where('follow_from' , $r->id)->where('follow_to' , $user->id)->exists()
        ] , 200);
}
    }

    public function getUserThreads(Request $r)
    {
        $threads = Thread::where('creator' , $r->id)->get();

        $likedThreadIds = Like::where('user_id', $r->bid)->pluck('thread_id')->toArray();
        
        return response()->json($threads->map(function ($t) use($likedThreadIds) {
            return [
                'content' => $t->content,
                'image' => base64_encode($t->image),
                'image_type' => $t->image_type,
                'slug' => $t->slug,
                'id' => $t->id,
                'is_liked' => in_array($t->id , $likedThreadIds),
                'likes_count' => count($t->likes),
            ];
        }) , 200);
    }

    public function singleThread(Request $r)
    {
            $thread = Thread::where('slug' , $r->slug)->with('comments')->withCount('likes')->with('likes')->first();
if($thread) {
    $likedThreadIds = Like::where('user_id', $r->id)->pluck('thread_id')->toArray();

    return response()->json([
        'data' => [
        'id' => $thread->id ,
        'content' => $thread->content,
        'slug' => $thread->slug,
        'image' => base64_encode($thread->image),
        'image_type' => $thread->image_type,
        'likes_count' => $thread->likes_count,
        'is_liked' => in_array($thread->id , $likedThreadIds),
        'comments' => Comment::where('thread_id' , $thread->id)->orderBy('id' , 'desc')->get()->map(function ($c) {
                return [
                        'content' => $c->content,
                        'creator' => User::find($c->user_id),
                        'id' => $c->id
                
                    ];
        }),
        ]
     ,
          'creator' => User::where('id' , $thread->creator)->first(),
          'is_followed' => Follower::where('follow_from'  , $r->id)->exists()
     ] , 200);

}else{
 return response()->json(['message' => 'not Found Tread'] , 404);
}
 
    }


}
