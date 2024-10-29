<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Image;
use Illuminate\Http\Request;
use App\Models\ReceivedCapsule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Resources\ReceivedCapsuleResource;
use Illuminate\Routing\Controllers\HasMiddleware;

class ReceivedCapsuleController implements HasMiddleware
{   
    /**
     * Display a listing of the resource.
     */

     public static function middleware()
     {
         return [
             new Middleware('auth:sanctum')
         ];
     }
     public function index()
     {
         $user = Auth::user();
         
         // Eager load images through the ReceivedCapsule model
         $capsules = ReceivedCapsule::with('images')
             ->where('receiver_email', $user->email)
             ->get();
             
         if ($capsules->isEmpty()) {
             return response()->json(['message' => 'No capsules found!'], 404);
         } else {
             return response()->json([
                 'data' => $capsules
             ], 200);
         }
     }
     
    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        //
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        // Validate incoming request data

        $user = Auth::user();
        
        $validatedData = $request->validate([
            'title' => 'required|max:50|string',
            'message' => 'required|max:5000|string',
            'receiver_email' => 'required|email',
            'scheduled_open_at' => 'required',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        // Check if the user is authenticated
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        // Check if receiver exists in users table
        $receiver = User::where('email', $validatedData['receiver_email'])->first();
        if (!$receiver) {
            return response()->json(['message' => 'Receiver not found.'], 404);
        }
    
        $createdCapsule = ReceivedCapsule::create(array_merge($validatedData, ['user_id' => $user->id]));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                if ($imageFile->isValid()) {
                    // Store the image and get the path
                    $imagePath = $imageFile->store('images', 'public');
    
                    // Create a new image record and associate it with the capsule
                    $image = new Image([
                        'image' => $imagePath,
                        'capsule_id' => $createdCapsule->id, // Use the ID of the created received capsule
                        'capsule_type' => 'App\\Models\\ReceivedCapsule'
                    ]);
    
                    // Save the image using the morphMany relationship
                    $createdCapsule->images()->save($image);
                }
            }
        }
    
        return response()->json([
            'data' => new ReceivedCapsuleResource($createdCapsule),
            'message' => 'Capsule sent successfully'
        ]);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(ReceivedCapsule $receivedCapsule)
    {
        $user = Auth::user();

        // Check if the capsule belongs to the authenticated user
        if ($receivedCapsule->receiver_email !== $user->email) {
            return response()->json(['message' => 'Capsule not found!'], 404);
        }

        // Eager-load the sender and images relationships
        $receivedCapsule->load(['images', 'sender']);

        // Map the images to include full URLs
        $imagesWithUrls = $receivedCapsule->images->map(function ($image) {
            $user = Auth::user();
            return [
                'id' => $image->id,
                'image_url' => url($user->profile_pic ? Storage::url($image->image) : null) // Convert relative path to full URL
            ];
        });

        $sender = $receivedCapsule->sender;

        return response()->json([
            'id' => $receivedCapsule->id,
            'title' => $receivedCapsule->title,
            'message' => $receivedCapsule->message,
            'receiver_email' => $receivedCapsule->receiver_email,
            'scheduled_open_at' => $receivedCapsule->scheduled_open_at,
            'images' => $imagesWithUrls,

            'sender' => [
                // 'name' => $receivedCapsule->sender->name,
                // 'email' => $receivedCapsule->sender->email,
                // 'profile_pic_url' => $receivedCapsule->sender->profile_pic ? Storage::url($receivedCapsule->sender->profile_pic) : null,
                'name' => $sender ? $sender->name : null,
                'email' => $sender ? $sender->email : null,
                'profile_pic_url' => $sender && $sender->profile_pic ? Storage::url($sender->profile_pic) : null,
            ]
        ], 200);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceivedCapsule $receivedCapsule) {
        
        foreach ($receivedCapsule->images as $image) {
            // Delete the image from storage
            if (Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }
    
            // Delete the image record from the database
            $image->delete();
        }
        if (!$receivedCapsule) {
            return response()->json(['message' => 'Capsule not found'], 404);
        }
    
            // Delete the specific capsule
            $receivedCapsule->delete();
    
            return response()->json(['message' => 'Capsule deleted!'], 200);
    }
}
