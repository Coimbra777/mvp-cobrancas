<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_TENTATIVAS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
            'manter_conectado' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'senha.required' => 'Informe a senha.',
        ];
    }

    /**
     * Tenta autenticar. Falhas usam a chave "credenciais" com mensagem genérica,
     * sem indicar se o erro foi no e-mail ou na senha.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $this->garantirQueNaoEstaBloqueado();

        $credenciais = ['email' => $this->string('email')->toString(), 'password' => $this->string('senha')->toString()];

        if (! Auth::attempt($credenciais, $this->boolean('manter_conectado'))) {
            RateLimiter::hit($this->chaveDeLimite(), 60);

            throw ValidationException::withMessages([
                'credenciais' => 'E-mail ou senha incorretos.',
            ]);
        }

        RateLimiter::clear($this->chaveDeLimite());
    }

    /**
     * @throws ValidationException
     */
    private function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDeLimite(), self::MAX_TENTATIVAS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'credenciais' => 'Muitas tentativas. Tente de novo em '.RateLimiter::availableIn($this->chaveDeLimite()).' segundos.',
        ]);
    }

    private function chaveDeLimite(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->toString()).'|'.$this->ip());
    }
}
