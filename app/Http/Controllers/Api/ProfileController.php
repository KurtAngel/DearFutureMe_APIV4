<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class ProfileController extends Controller
{
    public function showProfile()
    {
        $user = Auth::user();

        return response()->json([
            'profile_pic_url' => url($user->profile_pic ? Storage::url($user->profile_pic) : null)
            // 'profile_pic_url' => Storage::url($user->profile_pic)
        ]);
    }

    public function uploadProfilePic(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Get the current user
        $user = User::find(Auth::id());
        
        // Handle the file upload
        if ($request->hasFile('profile_pic')) {
            
            if ($user->profile_pic) {
                Storage::disk('public')->delete($user->profile_pic);
            }
            
            // Store the new profile picture
            $path = $request->file('profile_pic')->store('/profile_pics', 'public');
            
            $user->profile_pic = $path;
            
            $user->save();
        }

        return response()->json([
            'message' => 'Profile picture updated successfully!',
            'profile_pic_url' => Storage::url($user->profile_pic)
        ]);
    }
}
