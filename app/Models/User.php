<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SEEKER = 'seeker';
    public const ROLE_EMPLOYER = 'employer';
    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'cv_file_path',
        'profile_completion_percent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'profile_completion_percent' => 'integer',
        ];
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedJobs()
    {
        return $this->belongsToMany(JobListing::class, 'bookmarks', 'user_id', 'job_listing_id')->withTimestamps();
    }

    public function isSeeker(): bool
    {
        return $this->role === self::ROLE_SEEKER;
    }

    public function isEmployer(): bool
    {
        return $this->role === self::ROLE_EMPLOYER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Recalculate profile completion percent based on filled fields.
     */
    public function recomputeProfileCompletion(): int
    {
        $fields = [
            'name' => (bool) $this->name,
            'email' => (bool) $this->email,
            'email_verified' => (bool) $this->email_verified_at,
            'phone' => (bool) $this->phone,
            'cv' => (bool) $this->cv_file_path,
        ];

        $filled = count(array_filter($fields));
        $total = count($fields);

        return (int) round(($filled / $total) * 100);
    }
}
