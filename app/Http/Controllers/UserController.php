<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JWTAuth; 
use Illuminate\Support\Str;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'api_key']); 
    }

    public function updateUser(Request $request)
    {
        
        $request->validate([
            'image' => 'nullable|image',
            'image_type' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        try {
            $user = auth()->user();

            
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageData = base64_encode(file_get_contents($image->getRealPath()));

                $user->image = $imageData;
                $user->image_type = $request->image_type;
            }

            
            if ($request->filled('name')) {
                $user->name = $request->name;
                $user->slug = '@'.  Str::slug($request->name) . $user->id;

            }

            
            if ($request->bio || $request->bio == '') {
                $user->bio = $request->bio;
            }

            $user->save();

            
            $token = JWTAuth::getToken();
            if ($token) {
                $token = JWTAuth::refresh($token);
            } else {
                return response()->json(['Error' => 'Token Not Valid'], 400);
            }

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 202);

        } catch (\Exception $e) {
            
            \Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['Error' => $e], 500);
        }
    }
    
}
