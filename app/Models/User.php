<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUlids, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * CIMA-042: deactivated accounts are blocked at auth/middleware and
     * sessions are invalidated on deactivation; audit history is preserved.
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /** Wards this account is assigned to (drives the ward-scope restriction). */
    public function wards(): BelongsToMany
    {
        return $this->belongsToMany(Ward::class, 'ward_user');
    }

    /**
     * Roles that keep unrestricted ward access (`super admin`, `hospital
     * administrator`). Every other account only sees/admits into the wards
     * assigned to it.
     */
    public function hasFullWardAccess(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN)
            || $this->hasRole(Role::HOSPITAL_ADMINISTRATOR);
    }

    /** @return list<string> ward ids assigned to this account */
    public function wardIds(): array
    {
        return array_values(array_map('strval', (array) $this->wards()->pluck('wards.id')->all()));
    }

    /** Ward-view/admission gate for the scoped roles. */
    public function canAccessWard(string $wardId): bool
    {
        return $this->hasFullWardAccess()
            || in_array($wardId, $this->wardIds(), true);
    }
}
