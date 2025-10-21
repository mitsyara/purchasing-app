<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail, HasAppAuthentication, HasAppAuthenticationRecovery
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;
    use \App\Traits\HasLoggedActivity;

    /**
     * Filament authorization
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        $allowedDomains = [
            'vietuy.vn',
            'vhl.com.vn',
            'globalhub.com.vn',
            'cangroup.vn',
        ];

        $emailDomain = substr(strrchr($this->email, "@"), 1);
        return (in_array($emailDomain, $allowedDomains) && $this->hasVerifiedEmail()) || $this->id === 1;
    }

    // Filament's 2FA 

    /**
     * This method return the user's app authentication secret.
     */
    public function getAppAuthenticationSecret(): ?string
    {

        return $this->app_mfa_secret;
    }

    /**
     * This method save the user's app authentication secret.
     */
    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->app_mfa_secret = $secret;
        $this->save();
    }

    /**
     * Should return the user's holder name, uniquely identifiable.
     */
    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    // This method should return the user's saved app authentication recovery codes.
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_mfa_recovery_codes;
    }

    /**
     * This method should save the user's app authentication recovery codes.
     * @param  array<string> | null  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->app_mfa_recovery_codes = $codes;
        $this->save();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'dob',
        'status',
        'lock_pin',
        // 'app_mfa_secret',
        // 'app_mfa_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'lock_pin',
        'remember_token',
        'app_mfa_secret',
        'app_mfa_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'status' => \App\Enums\UserStatusEnum::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'lock_pin' => 'hashed',
            'app_mfa_secret' => 'encrypted',
            'app_mfa_recovery_codes' => 'encrypted:array',
        ];
    }

    // Model relationships

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_user', 'user_id', 'contact_id');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public static function getCompanyPivotTable(): string
    {
        return 'company_user';
    }
}
