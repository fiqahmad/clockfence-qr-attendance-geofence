<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Enums\StaffStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property int|null $organization_id
 * @property string $email
 * @property string|null $staff_no
 * @property int|null $site_id
 * @property StaffStatus $status
 * @property string $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Site|null $site
 */
#[Fillable(['name', 'email', 'password', 'staff_no', 'site_id', 'status', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Only Active Admins and Managers may open the admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive()
            && $this->hasAnyRole(array_map(fn (Role $role): string => $role->value, Role::panelRoles()));
    }

    /**
     * The home Site. A User with one is a Staff member, whatever their role.
     *
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    public function isManager(): bool
    {
        return $this->hasRole(Role::Manager->value);
    }

    public function isActive(): bool
    {
        return $this->status === StaffStatus::Active;
    }

    /**
     * Stop this Staff member logging in or Clocking, and end any session they have
     * open, including "remember me". Staff members are never deleted.
     */
    public function deactivate(): void
    {
        $this->forceFill([
            'status' => StaffStatus::Inactive,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table(config('session.table', 'sessions'))->where('user_id', $this->id)->delete();
    }

    /**
     * Undo a deactivation: back to Active, or to Invited if they never set a password.
     */
    public function reactivate(): void
    {
        $this->forceFill([
            'status' => $this->email_verified_at === null ? StaffStatus::Invited : StaffStatus::Active,
        ])->save();
    }

    /**
     * The User's single role, if any.
     */
    public function primaryRole(): ?Role
    {
        $roleName = $this->getRoleNames()->first();

        return $roleName === null ? null : Role::from($roleName);
    }

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
            'status' => StaffStatus::class,
        ];
    }
}
