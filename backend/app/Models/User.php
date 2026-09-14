<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'puede_atribuir_a_otros'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'puede_atribuir_a_otros' => 'boolean',
        ];
    }

    /**
     * Indica si este usuario puede registrar movimientos de stock atribuidos
     * a otros miembros del hogar (no solo a sí mismo).
     */
    public function puedeAtribuirAOtros(): bool
    {
        return (bool) $this->puede_atribuir_a_otros;
    }

    public function movimientosRegistrados(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'usuario_registrador_id');
    }

    public function movimientosAtribuidos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'usuario_atribuido_id');
    }
}
