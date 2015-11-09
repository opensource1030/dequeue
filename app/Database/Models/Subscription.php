<?php

namespace App\Database\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tblsubscriptions';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable / not.
     *
     * @var array
     */
    protected $fillable = [];
    protected $guarded = [];

    /**
     * Return a merchant who has the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'idMerchant');
    }

    /**
     * Return a category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'idCategory');
    }

    /**
     * Return orders
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders() {
        return $this->hasMany(Order::class, 'idSubscription');
    }
}
