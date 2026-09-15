<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /** Si la petición llegó con el campo antiguo, para responder en él. */
    private bool $vinoComoEmail = false;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Las PWA ya instaladas envían `email`. Se normaliza aquí para que de
     * la validación en adelante solo exista un campo: si se retirara la
     * compatibilidad, alguien con la versión anterior en caché y sin sesión
     * no podría ni entrar a recibir la actualización.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('identificador') && $this->filled('email')) {
            $this->vinoComoEmail = true;
            $this->merge(['identificador' => $this->input('email')]);
        }
    }

    public function rules(): array
    {
        return [
            // Nombre de miembro o correo, indistintamente.
            'identificador' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Lo tecleado en el campo de acceso. */
    public function identificador(): string
    {
        return trim($this->validated()['identificador']);
    }

    /**
     * El error se cuelga del campo que se envió, para que un cliente
     * antiguo lo encuentre donde lo espera.
     */
    public function campoIdentificador(): string
    {
        return $this->vinoComoEmail ? 'email' : 'identificador';
    }

    /**
     * La pantalla de acceso es lo primero que ve alguien y los mensajes por
     * defecto de Laravel salen en inglés.
     */
    public function messages(): array
    {
        return [
            'identificador.required' => 'Indica tu usuario o tu correo.',
            'password.required' => 'Indica tu contraseña.',
        ];
    }

    public function attributes(): array
    {
        return ['identificador' => 'usuario o correo'];
    }
}
