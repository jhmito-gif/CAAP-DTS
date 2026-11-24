<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomResetPassword;

class User extends Authenticatable implements FilamentUser
{
    use Concerns\Audited;
    use HasApiTokens;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role == 'ADMIN' && ! $this->isDeactivated();
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    const ROLE_ADMIN = 'ADMIN';
    const ROLE_USER = 'USER';
    const ROLE_DEFAULT = self::ROLE_USER;

    const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_USER => 'User',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'manages_documents',
        'office',
        'service',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

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
            'manages_documents' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }


    public function isAdmin(){
        return $this->role === self::ROLE_ADMIN;
    }

    /*
    |--------------------------------------------------------------------------
    | Deactivation
    |--------------------------------------------------------------------------
    | Accounts are never deleted: a person's name is on the records they sent,
    | received and signed. Someone who has left is deactivated instead -- they
    | cannot sign in (password, two-factor, API token) and drop out of the
    | people lists, and an admin can bring them back.
    */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /** People who can still sign in -- the ones worth offering in a list. */
    public function scopeActive($query)
    {
        return $query->whereNull($this->qualifyColumn('deactivated_at'));
    }

    public function deactivate(): void
    {
        $this->forceFill(['deactivated_at' => now()])->save();
    }

    public function reactivate(): void
    {
        $this->forceFill(['deactivated_at' => null])->save();
    }

    protected function auditAction(string $event): string
    {
        $changed = array_values(array_diff(array_keys($this->getChanges()), ['updated_at']));

        if ($event === 'updated' && $changed === ['deactivated_at']) {
            return $this->deactivated_at ? 'user.deactivated' : 'user.reactivated';
        }

        return "user.{$event}";
    }

    /** Saved e-signature image. */
    public function signature(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSignature::class);
    }

    /** Hashed PIN that confirms each e-signature. */
    public function signingPin(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SigningPin::class);
    }

    // Custom Reset Password 
    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }
}
