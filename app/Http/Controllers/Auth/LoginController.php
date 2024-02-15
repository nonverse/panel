<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Repository\RefreshTokenRepositoryInterface;
use Pterodactyl\Repositories\Eloquent\UserRepository;
use Pterodactyl\Services\Api\AccessTokenService;
use Pterodactyl\Services\Users\UserCreationService;

class LoginController extends AbstractLoginController
{
    /**
     * @var RefreshTokenRepositoryInterface
     */
    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * @var AccessTokenService
     */
    private AccessTokenService $accessTokenService;

    /**
     * @var UserCreationService
     */
    private UserCreationService $userCreationService;

    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        UserRepository                  $userRepository,
        AccessTokenService              $accessTokenService,
        UserCreationService             $userCreationService
    )
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->userRepository = $userRepository;
        $this->accessTokenService = $accessTokenService;
        $this->userCreationService = $userCreationService;
        parent::__construct();
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. React will take over at this point and
     * turn the login area into an SPA.
     */
    public function index(): View
    {
        return view('templates/auth.core');
    }

    /**
     * Handle a login request to the application via OAuth2.
     *
     *
     */
    public function login(Request $request): JsonResponse
    {
        if (!$jwt = $request->cookie('user_session')) {
            return $this->requestAuthorization($request);
        }

        try {
            $userJwt = (array)JWT::decode($jwt, new Key(config('auth.public_key'), 'RS256'));

            /**
             * If an unexpired access token exists in session already, no need to get new access token
             */
            if ($accessToken = $request->session()->get('access_token')) {
                if ($accessToken['token_expiry'] instanceof CarbonInterface && $accessToken['token_expiry']->isAfter(CarbonImmutable::now()->addMinute())) {
                    $decodedToken = (array)JWT::decode($accessToken['token_value'], new Key(config('auth.public_key'), 'RS256'));
                    if ($decodedToken['sub'] === $userJwt['sub']) {
                        return new JsonResponse([
                            'success' => true
                        ]);
                    }
                }
            }

            /**
             * Get user's refresh token from database
             */
            $refreshToken = $this->refreshTokenRepository->getUsingUserId($userJwt['sub']);
            $this->accessTokenService->usingRefreshToken($request, $refreshToken);

        } catch (ExpiredException $e) {
            /**
             * If authentication is invalid (expired), request authorization
             * The auth server will automatically request the user to login if not already before authorizing the application
             */
            return $this->requestAuthorization($request);
        } catch (ModelNotFoundException $e) {
            /**
             * If a refresh token is not found...
             */
            if ($request->input('code')) {
                /**
                 * If an authorization code is present in the request, get access token using authorization code
                 */
                $this->accessTokenService->usingAuthCode($request);
            } else {
                /**
                 * If an authorization code is not found, request authorization
                 * It is assumed that the user is logged in at this point. The auth server will still check for an
                 * authenticated user but it is expected that the application will be authorized without requiring login
                 */
                return $this->requestAuthorization($request);
            }
        }

        /**
         * Check if user exists on panel
         */
        try {
            $user = $this->userRepository->getUserByUuid($userJwt['sub']);
        } catch (Exception $e) {
            $userRes = json_decode(Http::withToken($request->session()->get('access_token')['token_value'])->get(env('API_SERVER') . 'user/store'), true)['data'];

            $user = $this->userCreationService->handle([
                'uuid' => $userJwt['sub'],
                'email' => $userRes['email'],
                'username' => $userRes['username']
            ]);
        }

        return $this->sendLoginResponse($request, $user);
    }

    /**
     * Return response to request authorization
     *
     * @param Request $request
     * @return JsonResponse
     */
    protected function requestAuthorization(Request $request): JsonResponse
    {

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => env('OAUTH_CLIENT_ID'),
            'redirect_uri' => env('APP_URL') . 'auth/login/',
            'scope' => implode(" ", config('auth.scopes')),
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse([
            'success' => false,
            'data' => [
                'auth_url' => env('OAUTH_SERVER') . 'oauth/authorize?' . $query
            ],
            'error' => 'unauthenticated'
        ], 401);
    }
}
