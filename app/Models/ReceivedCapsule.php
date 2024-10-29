<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedCapsule extends Model
{
    use HasFactory;

    protected $table = 'receivedcapsules'; // Specify the correct table name
    
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'receiver_email',
        'scheduled_open_at'
    ];
    
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function images() { // Change to images to reflect the relationship correctly
        return $this->morphMany(Image::class, 'capsule');
    }

    public function image()
    {
        return $this->hasMany(Image::class, 'capsule_id'); // Adjust if using a different foreign key
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id'); // Adjust 'user_id' to 'sender_id' if needed
    }

    protected static function booted()
    {
        static::deleting(function ($capsule) {
            // Delete all associated images when a capsule is deleted
            $capsule->images()->delete();
        });
    }
}
