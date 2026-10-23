<?php

namespace App\Models;

use Database\Factories\JobPushSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPushSubscription extends Model
{
    /** @use HasFactory<JobPushSubscriptionFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
