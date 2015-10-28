<?php

namespace App\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use StringHelper;

class Merchant extends Model
{
    // use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tblmerchant';

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
     * Return the locations who belongs to the merchant
     *
     * * @return \Illuminate\Database\Eloquent\Relations|HasMany
     */
    public function locations()
    {
        return $this->hasMany(Location::class, 'idMerchant');
    }

    /**
     * Return the subscriptions who belongs to the merchant
     *
     * * @return \Illuminate\Database\Eloquent\Relations|HasMany
     */
    public function subscriptions() {
        return $this->hasMany(Subscription::class, 'idMerchant');
    }

    /**
     * Automatically hash the password
     *
     * @param $password
     */
    public function setSzPasswordAttribute($password) {
        $this->attributes['szPassword'] = StringHelper::encryptString($password);
    }

    /**
     * Automatically check the email
     *
     * @param $email
     * @throws \Exception
     */
    public function setSzEmailAttribute($email) {
//        $model = $this->newQuery()->where('szEmail', $email)->where('id', '!=', $this->id)->first();
//
//        \Log::info($email);
//        \Log::info($this->id);
//
//        if ($model) {
//            throw new \ErrorException('Email already taken', 10005);
////            throw new ValidationFailureException('Validation Failed.');
//        } else {
//            $this->attributes['szEmail'] = $email;
//        }
        $this->attributes['szEmail'] = $email;
    }

    /**
     * Automatically generate mobile key
     *
     * @param $mobile_key
     */
    public function setSzMobileKeyAttribute($mobile_key) {
        if (empty($mobile_key) || $mobile_key == '') {
            $this->attributes['szMobileKey'] = StringHelper::uniqueKey();
        } else {
            $this->attributes['szMobileKey'] = $mobile_key;
        }
    }
}