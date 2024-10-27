<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Image;
use App\Models\Capsule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\CapsuleResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class CapsuleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum')
        ];
    }
    
    public function index() {
        //check authenticated user
        $user = Auth::user();
        
        $capsules = Capsule::where('user_id', $user->id)->get();
        
        if($capsules->isEmpty()) {
            return response()->json(['message' => 'No capsules found!'], 404);
        } else {
            return CapsuleResource::collection($capsules);
        } 
    }

    public function show(Capsule $capsule) {

        $user = Auth::user();
    
        // Check if the capsule belongs to the authenticated user
        if ($capsule->user_id !== $user->id) {
            return response()->json(['message' => 'Capsule not found!'], 404);
        }

        $capsule->load('images');

            // Map the images to include full URLs
        $imagesWithUrls = $capsule->images->map(function ($image) {
            $user = Auth::user();
            return [
                'id' => $image->id,
                'image_url' => url($user->profile_pic ? Storage::url($image->image) : null) // Convert relative path to full URL
            ];
        });

        return response()->json([
            'id'=> $capsule['id'],
            'title'=> $capsule['title'],
            'message'=> $capsule['message'],
            'receiver_email'=> $capsule['receiver_email'],
            'scheduled_open_at'=> $capsule['scheduled_open_at'],
            'images' => $imagesWithUrls
        ], 200);
    }

    public function view(Capsule $capsule) {

        // Gate::authorize('modify_receiver', $capsule);

        $user = Auth::user();
    
        if ($capsule->receiver_email !== $user->email) {

            return response()->json([
                'message' => 'You do not own this capsule',
            ], 404);
        }

        return response()->json([
            'Info' => $capsule,
            'images' => $capsule->images 
        ], 200);
    }

    public function destroy(Capsule $capsule) {

        Gate::authorize('modify', $capsule);
        
        foreach ($capsule->images as $image) {
            // Delete the image from storage
            if (Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }
    
            // Delete the image record from the database
            $image->delete();
        }
        if (!$capsule) {
            return response()->json(['message' => 'Capsule not found'], 404);
        }
    
            // Delete the specific capsule
            $capsule->delete();
    
            return response()->json(['message' => 'Capsule deleted!'], 200);
    }

    public function store(Request $request) {

        // Validate incoming request data
        $validatedData = $request->validate([
            'title' => 'required|max:50|string',
            'message' => 'required|max:5000|string',
            'receiver_email' => 'nullable|email',
            'scheduled_open_at' => 'nullable',
            'images' => 'nullable|array', // Expect an array of images
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validate each image
        ]);
    
        // Optionally check if receiver_email exists in users table
        if (isset($validatedData['receiver_email'])) {
            $receiver = User::where('email', $validatedData['receiver_email'])->first();
            
            if (!$receiver) {
                return response()->json(['message' => 'Receiver not found.'], 404);
            }
        }

        $capsule = $request->user()->capsules()->create($validatedData);
    
        // Handle image uploads if images are provided
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                // Check if the file is valid
                if ($imageFile->isValid()) {
                    // Store the image and get the path
                    $imagePath = $imageFile->store('images', 'public'); // Store in storage/app/public/images
    
                    // Create a new image record and associate it with the capsule
                    $image = new Image([ 
                        'image' => $imagePath, 
                        'capsule_id' => $capsule->id, 
                        'capsule_type' => get_class($capsule) 
                    ]);
    
                    // Save the image using the morphMany relationship
                    $capsule->images()->save($image);
                } else {
                    Log::info(message: 'Uploaded image is not valid.');
                }
            }
        } else {
            Log::info('No images uploaded.');
        }

        return response()->json([
            'info' => [
                'title' => $capsule->title,
                'message' => $capsule->message,
                'receiver_email' => $capsule->receiver_email,
                'scheduled_open_at' => $capsule->scheduled_open_at,
                'user_id' => $capsule->user_id,
                'id' => $capsule->id,
                'images' => $capsule->images // Include images directly in the capsule info
            ],
            'draft' => 'Capsule has been moved to draft',
        ], 200);
        
    }
    
    
    public function update(Request $request, Capsule $capsule) {
        // Log the ID of the capsule being updated
        Log::info('Looking for Capsule ID:', ['id' => $capsule->id]);
    
        Gate::authorize('modify', $capsule);
        

        
        // Validate the request data (if validation is enabled)
        try {
            $validatedData = $request->validate([
                'title' => 'nullable|max:50|string',
                'message' => 'nullable|max:5000|string',
                'receiver_email' => 'nullable|email',
                'scheduled_open_at' => 'nullable|date',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation errors:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }
    
        // Log the capsule before updating
        Log::info('Capsule before update:', $capsule->toArray());
        
        // Update the capsule with validated data
        $capsule->update(array_filter($validatedData));
        
        // Optionally, reload the capsule to get updated values
        $capsule->refresh();
        
        return response()->json([
            'id' => $capsule->id,
            'title' => $capsule->title,
            'message' => $capsule->message,
            'receiver_email' => $capsule->receiver_email,
            'scheduled_open_at' => $capsule->scheduled_open_at,
            'images' => $capsule->images,
            'messageResponse' => 'Updated Successfully'
        ], 200);
    }
}        
