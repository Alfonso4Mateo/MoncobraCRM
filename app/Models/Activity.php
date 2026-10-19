<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    // Esto obliga a Laravel a convertir la fecha UTC a tu Timezone (Europe/Madrid)
    protected $casts = [
        'properties' => 'collection',
        'created_at' => 'datetime',
    ];
}
