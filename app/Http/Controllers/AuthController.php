<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Follower;
use Validator;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
        
    public function login(Request $request){
        
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'user not registerd'], 301);
        }

        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

    $data = array_merge(
    $validator->validated(),
    ['password' => bcrypt($request->password)],
    ['slug' => '@'. Str::slug($request->input('name'))]
);

$user = User::create($data);

$user->slug = $user->slug . $user->id;

    $user->save();


        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

  
   public function userProfile()
{
    $user = auth()->user();

    $followersIds = $user->followers()->pluck('follow_from');
    
    $followers = User::whereIn('id', $followersIds)->get();

    $following = Follower::where('follow_from', $user->id)->get();

    return response()->json([
        'user' => $user,
        'followers' => $followers->map(function ($f) use ($user) {
            return [
                'name' => $f->name,
                'image' => $f->image,
                'id' => $f->id,
                'image_type' => $f->image_type,
                'is_already_followed' => Follower::where('follow_from', $user->id)
                                                ->where('follow_to', $f->id)
                                                ->exists(),
            ];
        }),
        'following' => $following->map(function ( $fl) use ($user) {
            $f = User::find($fl->follow_to); 
            return [
                'name' => $f->name,
                'image' => $f->image,
                'image_type' => $f->image_type,
                'id' => $f->id
            ];
        }),
    ], 200);
}

    

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

}