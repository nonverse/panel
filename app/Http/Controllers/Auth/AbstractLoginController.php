<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Failed;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\User;

abstract class AbstractLoginController extends Controller
{
    use AuthenticatesUsers;

    protected AuthManager $auth;

    /**
     * Lockout time for failed login requests.
     */
    protected int $lockoutTime;

    /**
     * After how many attempts should logins be throttled and locked.
     */
    protected int $maxLoginAttempts;

    /**
     * Where to redirect users after login / registration.
     */
    protected string $redirectTo = '/';

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->lockoutTime = config('auth.lockout.time');
        $this->maxLoginAttempts = config('auth.lockout.attempts');
        $this->auth = Container::getInstance()->make(AuthManager::class);
    }

    /**
     * Get the failed login response instance.
     *
     * @return never
     *
     * @throws DisplayException
     */
    protected function sendFailedLoginResponse(Request $request, Authenticatable $user = null, string $message = null)
    {
        $this->incrementLoginAttempts($request);
        $this->fireFailedLoginEvent($user, [
            $this->getField($request->input('user')) => $request->input('user'),
        ]);

        if ($request->route()->named('auth.login-checkpoint')) {
            throw new DisplayException($message ?? trans('auth.two_factor.checkpoint_failed'));
        }

        throw new DisplayException(trans('auth.failed'));
    }

    /**
     * Send the response after the user was authenticated.
     */
    protected function sendLoginResponse(Request $request, User $user): JsonResponse
    {
        $request->session()->regenerate();


        Auth::loginUsingId($user->id);
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Determine if the user is logging in using an email or username.
     */
    protected function getField(string $input = null): string
    {
        return ($input && str_contains($input, '@')) ? 'email' : 'username';
    }

    /**
     * Fire a failed login event.
     */
    protected function fireFailedLoginEvent(Authenticatable $user = null, array $credentials = [])
    {
        Event::dispatch(new Failed('auth', $user, $credentials));
    }
}
