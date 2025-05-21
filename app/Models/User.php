<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name', 'email', 'email_verified_at', 'password', 'access_id', 'last_access', 'remember_token'
    ];

    protected $dates = ['create_at', 'update_at'];

}
