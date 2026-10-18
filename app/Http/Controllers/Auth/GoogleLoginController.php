<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GoogleLoginController extends Controller
{
    public function redirect(Request $request, string $locale): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return $this->fail($locale, 'Login Google belum dikonfigurasi.', 'Google sign-in is not configured yet.');
        }

        $state = Str::random(64);
        $codeVerifier = Str::random(96);
        $request->session()->put('google_oauth', [
            'state' => $state,
            'code_verifier' => $codeVerifier,
            'locale' => $locale,
            'started_at' => now()->timestamp,
        ]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $flow = $request->session()->pull('google_oauth');
        $locale = is_array($flow) && in_array($flow['locale'] ?? null, ['id', 'en'], true) ? $flow['locale'] : 'id';
        $state = $request->query('state');

        if (! is_array($flow)
            || ! is_string($flow['state'] ?? null)
            || ! is_string($state)
            || ! hash_equals($flow['state'], $state)
            || ! is_int($flow['started_at'] ?? null)
            || now()->timestamp - $flow['started_at'] > 600
            || ! is_string($flow['code_verifier'] ?? null)) {
            return $this->fail($locale, 'Sesi login Google tidak valid atau telah kedaluwarsa. Coba lagi.', 'Your Google sign-in session expired or is invalid. Please try again.');
        }

        if ($request->has('error')) {
            return $this->fail($locale, 'Login Google dibatalkan atau ditolak.', 'Google sign-in was cancelled or denied.');
        }

        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return $this->fail($locale, 'Google tidak mengirimkan kode login. Coba lagi.', 'Google did not return a sign-in code. Please try again.');
        }

        try {
            $tokenResponse = Http::asForm()->acceptJson()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'code_verifier' => $flow['code_verifier'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri(),
            ]);

            $accessToken = $tokenResponse->json('access_token');

            if (! $tokenResponse->successful() || ! is_string($accessToken) || $accessToken === '') {
                return $this->fail($locale, 'Login Google gagal. Coba lagi.', 'Google sign-in failed. Please try again.');
            }

            $profileResponse = Http::withToken($accessToken)->acceptJson()->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (ConnectionException) {
            return $this->fail($locale, 'Google tidak dapat dihubungi. Coba lagi nanti.', 'Google could not be reached. Please try again later.');
        }

        $subject = $profileResponse->json('sub');
        $email = $profileResponse->json('email');
        $hostedDomain = $profileResponse->json('hd');

        if (! $profileResponse->successful()
            || ! is_string($subject) || $subject === '' || strlen($subject) > 255
            || ! is_string($email) || strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || $profileResponse->json('email_verified') !== true) {
            return $this->fail($locale, 'Email akun Google belum terverifikasi.', 'Your Google account email is not verified.');
        }

        $user = User::withTrashed()->where('google_id', $subject)->first();

        if ($user === null) {
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user !== null) {
                $emailDomain = Str::lower(Str::afterLast($email, '@'));
                $isAuthoritativeEmail = $emailDomain === 'gmail.com'
                    || (is_string($hostedDomain) && Str::lower($hostedDomain) === $emailDomain);

                if ($user->google_id !== null || ! $isAuthoritativeEmail) {
                    return $this->fail($locale, 'Email ini sudah digunakan. Masuk dengan metode sebelumnya.', 'This email is already in use. Sign in using your previous method.');
                }

                if (! $user->trashed()) {
                    $user->forceFill(['google_id' => $subject, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
                }
            } else {
                $name = $profileResponse->json('name');
                $name = is_string($name) && trim($name) !== '' ? Str::limit(trim($name), 255, '') : Str::before($email, '@');
                $username = Str::slug(Str::limit($name, 180, '')) ?: 'user';

                $user = new User;
                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'username' => $username.'-'.Str::lower((string) Str::ulid()),
                    'password' => Str::random(64),
                    'locale' => $locale,
                    'region_code' => $locale === 'id' ? 'ID' : 'GLOBAL',
                    'google_id' => $subject,
                    'email_verified_at' => now(),
                ])->save();
                $user->assignRole(Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']));
                event(new Registered($user));
            }
        }

        if ($user->trashed()) {
            return $this->fail($locale, 'Akun ini tidak tersedia.', 'This account is unavailable.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('community.home', ['locale' => $locale]));
    }

    private function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('google.callback');
    }

    private function fail(string $locale, string $indonesian, string $english): RedirectResponse
    {
        return redirect()->route('community.login', ['locale' => $locale])
            ->with('google_auth_error', $locale === 'id' ? $indonesian : $english);
    }
}
