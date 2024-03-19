<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'document_cpf',
        'document_rg',
        'document_rg_consignor',
        'affiliation_date',
        'registration_number',
        'nationality',
        'marital_status',
        'ocuppation',
        'address',
        'address_city_state',
        'address_zipcode',
        'phone_ddd',
        'phone_number',
        'other_associations',
        'payment_type',
        'code_bank',
        'agency_bank',
        'account_bank',
        'financial_situation',
        'date_of_birth',
        'isActive',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function dependents() : HasMany
    {
        return $this->hasMany(UserDependents::class, 'responsible_user_id');
    }
}
