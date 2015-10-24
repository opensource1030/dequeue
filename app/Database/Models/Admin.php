<?php

namespace App\Database\Models;

use Illuminate\Database\Eloquent\Model;

use Log;
use StringHelper;

class Admin extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbladmin';

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
        $this->attributes['szPassword'] = StringHelper::encryptString($password);
    }
}
