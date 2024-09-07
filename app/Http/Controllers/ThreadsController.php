<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Thread;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use JWTAuth;

class ThreadsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'api_key']);
    }
    public function userThreads()
    {
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::parseToken()->authenticate();
            $id = $user->id;
            $threads = Thread::where('creator', $id)
                ->withCount('likes') 
                ->with('comments')
                ->orderBy('id' , 'desc')
                ->get();
    
            $likedThreadIds = Like::where('user_id', $id)->pluck('thread_id')->toArray();
    
            return response()->json([
                'status' => 'Success',
                'data' => $threads->map(function ($thread) use ($likedThreadIds , $token) {
                    return [
                        'content' => $thread->content,
                        'image' => $thread->image ? base64_encode($thread->image) : null,
                        'image_type' => $thread->image_type,
                        'slug' => $thread->slug,
                        'id' => $thread->id,
                        'is_liked' => in_array($thread->id, $likedThreadIds),
                        'likes_count' => $thread->likes_count,
                        'comments' => $thread->comments->map(function($e) use ($token) {
                            return [
                                'content' => $e->content,
                                'user_id' => $e->user_id,
                                'thread_id' => $e->thread_id,
                                'creator' =>User::findOrFail($e->user_id),
                            ];
                        }),
                    ];
                })
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve threads', 'message' => $e->getMessage()], 500);
        }
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'image' => 'image',
        ]);

        try {
            $thread = new Thread();
            $content = $request->input('content');
            $thread->content = $content;
            $thread->creator = JWTAuth::parseToken()->authenticate()->id;
            
            $contentLength = strlen($content);
            
            $maxLength = 7;
            $slugLength = $contentLength > $maxLength ? $maxLength : ($contentLength > 1 ? $contentLength : 1);
            
            $slug = '@' . Str::slug(substr($content, 0, $slugLength));
            $thread->slug = $slug;



            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $thread->image = file_get_contents($image->getRealPath());
                $thread->image_type = $image->getMimeType();
            }

            $thread->save();

                $thread->slug .= $thread->id;
                $thread->save();

            return response()->json(['message' => 'Thread Created Successfully'], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create thread', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:threads,id',
        ]);
    
        try {
            $thread = Thread::findOrFail($request->input('id'));
    
            $user = JWTAuth::parseToken()->authenticate();
            if ($thread->creator != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
    
            $thread->delete();
    
            return response()->json(['status' => 'success'], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete thread', 'message' => $e->getMessage()], 500);
        }
    }


    public function update(Request $r){
        $r->validate([
            'id' => 'required|exists:threads,id',
            
        ]);
try{
    
            
    $thread = Thread::find($r->id);
    if ($r->content){

        $content = $r->input('content');
        
    $thread->content = $content;
    
        

    $contentLength = strlen($content);
        
    $maxLength = 7;
    $slugLength = $contentLength > $maxLength ? $maxLength : ($contentLength > 1 ? $contentLength : 1);
    $slug = '@' . Str::slug(substr($content, 0, $slugLength));
    $thread->slug = $slug . $thread->id;
    }
    if($r->hasFile('image')){
        $image = $r->file('image');
        $thread->image = file_get_contents($image->getRealPath());
        $thread->image_type = $image -> getMimeType();
    }

    if ($r->content || $r->image){
        $thread->save();

    }
    return response()->json(['message' => 'succes'] , 200);

}catch(\Execption $e){
    return response()->json(['message' => "Failed $e"] , 200);
}
    } 

    public function like(Request $r){
        try{
            
        $like = Like::where('user_id' , $r->user_id)
        ->where('thread_id' , $r->thread_id)->first();    

        if ($like){
            $like->delete();
            return response()->json(['message' => 'unLiked'] ,200);        
        }

    $nl = Like::create([
            'user_id' => $r->user_id,
            'thread_id' => $r->thread_id,
        ]);


        return response()->json(['data' => $nl] , 201);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 400);
        }
    }

        public function comment(Request $r) {
            
            $comment = new Comment();

            $comment->user_id = $r->id;

            $comment->thread_id = $r->thread_id;
            $comment->content = $r->content;


                $comment->save();

                    return response()->json([

                        'content' => $comment->content,
                        'creator' => User::find($comment->user_id),
                        'id' => $comment->id
                    ],201);

        }


            

}
