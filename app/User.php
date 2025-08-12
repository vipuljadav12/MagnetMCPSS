<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\PasswordResetNotification;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
        'username','name', 'email', 'password','role_id','first_name','last_name','district_id','status','profile','role_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    /*public function setNameAttribute($value)
    {
        $this->attributes['Name'] = $this->FirstName.$this->LastName;
    }*/
     public function getFullNameAttribute($value)
    {
       return ucfirst($this->first_name) . ' ' . ucfirst($this->last_name);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }
}
