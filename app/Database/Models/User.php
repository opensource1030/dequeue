<?php

namespace App\Database\Models;

use Illuminate\Database\Eloquent\Model;

use Log;
use StringHelper;

class User extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbluser';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that are no mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Automatically hash the password
     *
     * @param $password
     */
    public function setSzPasswordAttribute($password) {
        Log::info('password hashed');

        $this->attributes['szPassword'] = StringHelper::encryptString($password);
//        $this->attributes['szPassword'] = bcrypt($password);
    }
    /**
     * Return orders who belongs to the user
     *
     * * @return \Illuminate\Database\Eloquent\Relations|HasMany
     */
    public function orders() {
        return $this->hasMany(Order::class, 'idUser');
    }
}
