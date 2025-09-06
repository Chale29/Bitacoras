<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cedula',
        'condicion',
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
        ];
    }

    /**
     * Relación con el modelo Profesor
     */
    public function profesor()
    {
        return $this->hasOne(Profesor::class, 'usuario_id');
    }

    /**
     * Relación many-to-many con Instituciones
     */
    public function instituciones()
    {
        return $this->belongsToMany(Institucione::class, 'user_institucione', 'user_id', 'institucion_id');
    }

    /**
     * Relación many-to-many con Especialidades
     */
    public function especialidades()
    {
        return $this->belongsToMany(Especialidade::class, 'user_especialidade', 'user_id', 'especialidad_id');
    }
}