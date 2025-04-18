<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model // Ensure the class name is singular and capitalized
{
    use HasFactory;

    protected $fillable = [
        'image',         // Path to the image
        'capsule_id',    // For polymorphic relation
        'capsule_type'   // For polymorphic relation
    ];
    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function capsule()
    {
        return $this->morphTo(); // Correctly define the morphTo relationship
    }

    public function receivedcapsules()
    {
        return $this->morphTo();
    }
}
