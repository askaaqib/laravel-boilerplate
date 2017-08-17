<?php

namespace App\Models;

use App\Notifications\ResetPassword as ResetPasswordNotification;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * App\Models\User.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property bool $active
 * @property string $confirmation_token
 * @property bool $confirmed
 * @property string $remember_token
 * @property string $locale
 * @property string $timezone
 * @property string $slug
 * @property \Carbon\Carbon $last_access_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property mixed $is_super_admin
 * @property \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 *
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User actives()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User findSimilarSlugs(\Illuminate\Database\Eloquent\Model $model, $attribute, $config, $slug)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereConfirmationToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereConfirmed($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereLastAccessAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereLocale($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereTimezone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUpdatedAt($value)
 * @mixin \Eloquent
 *
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\SocialLogin[] $providers
 * @property mixed $avatar
 * @property mixed $formatted_roles
 * @property \Plank\Mediable\MediableCollection|\App\Models\Post[] $posts
 */
class User extends Authenticatable
{
    use Notifiable;
    use Sluggable;

    protected $dates = [
        'last_access_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [
            'name',
            'email',
            'active',
            'locale',
            'timezone',
        ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden
        = [
            'password',
            'confirmation_token',
            'remember_token',
        ];

    protected $appends = [
      'avatar',
      'can',
    ];

    public function scopeActives(Builder $query)
    {
        return $query->where('active', '=', true);
    }

    public function getIsSuperAdminAttribute()
    {
        return $this->id === 1;
    }

    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function getFormattedRolesAttribute()
    {
        return $this->is_super_admin
            ? trans('labels.user.super_admin')
            : $this->roles->implode('display_name', ', ');
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasRole($name)
    {
        return $this->roles->contains('name', $name);
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (!in_array($permission, $permissions, true)) {
                    $permissions[] = $permission;
                }
            }
        }

        return collect($permissions);
    }

    /**
     * Get all user permissions in a flat array.
     *
     * @return array
     */
    public function getCanAttribute()
    {
        $permissions = [];

        /** @var Authenticatable $user */
        if ($user = auth()->user()) {
            $all_permissions = config('permissions');

            foreach ($all_permissions as $name => $permission) {
                if ($user->can($name)) {
                    $permissions[$name] = true;
                    continue;
                }
                $permissions[$name] = false;
            }
        }

        return $permissions;
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * @param $provider
     *
     * @return bool
     */
    public function getProvider($provider)
    {
        return $this->providers->first(function (SocialLogin $item) use ($provider) {
            return $item->provider === $provider;
        });
    }

    /**
     * @return mixed
     */
    public function providers()
    {
        return $this->hasMany(SocialLogin::class);
    }

    /**
     * Get user avatar from gravatar.
     */
    public function getAvatarAttribute()
    {
        $hash = md5($this->email);

        return "https://secure.gravatar.com/avatar/{$hash}?size=100&d=mm&r=g";
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return array
     */
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    public function __toString()
    {
        return $this->name;
    }
}
