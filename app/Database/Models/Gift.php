<?php

namespace App\Database\Models;

use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tblusergiftmapping';

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
