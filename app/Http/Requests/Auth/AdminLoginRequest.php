<?php

namespace App\Http\Requests\Auth;

use App\Models\Admin;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('login'));
        $password = (string) $this->input('password');
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            $authenticated = Auth::guard('admin')->attempt([
                'email' => Str::lower($login),
                'password' => $password,
            ], $this->boolean('remember'));
        } else {
            $digits = strtr($login, [
                '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
                '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
            ]);
            $phone = preg_replace('/\D+/', '', $digits);

            if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
                $phone = '0'.substr($phone, 3);
            }

            $admin = Admin::query()
                ->whereIn('phone_number', array_values(array_unique([$phone, $login])))
                ->first();

            $authenticated = $admin && Hash::check($password, $admin->password);

            if ($authenticated) {
                Auth::guard('admin')->login($admin, $this->boolean('remember'));
            }
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin || strtolower((string) $admin->status) !== 'active') {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'login' => trans('auth.inactive'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey()
    {
        return Str::transliterate(Str::lower($this->input('login')).'|'.$this->ip());
    }
}
