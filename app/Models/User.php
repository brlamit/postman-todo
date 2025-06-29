<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
    'name', 'email', 'password', 'otp', 'otp_expires_at', 'otp_verified_at', 'email_verified_at',
];
protected $hidden = [
    'password', 'remember_token', 'otp', 'otp_expires_at',
];
protected $casts = [
    'email_verified_at' => 'datetime',
    'otp_expires_at' => 'datetime',
    'otp_verified_at' => 'datetime',
];
}