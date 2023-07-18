<?php

namespace Pterodactyl\Services\Api;

use Pterodactyl\Contracts\Repository\RefreshTokenRepositoryInterface;
use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Exceptions\Model\DataValidationException;

class AccessTokenService
{
    /**
     * @var RefreshTokenRepositoryInterface
     */
    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository
    )
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * Get access token using authorization code
     *
     * @param Request $request
     * @return array|true[]
     * @throws DataValidationException
     */
    public function usingAuthCode(Request $request): array
    {
        /**
         * Request access token using authorization code
         */
        $response = Http::post(env('OAUTH_SERVER') . 'oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code',),
            'redirect_uri' => env('APP_URL'),
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'scope' => implode(" ", config('auth.scopes')),
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                ...json_decode($response->body(), true)
            ];
        }

        /**
         * Set tokens
         */
        $this->setTokens($request, $response);

        return [
            'success' => true,
        ];
    }

    /**
     * Get access token using refresh token
     *
     * @param Request $request
     * @param $refreshToken
     * @return array|true[]
     * @throws DataValidationException
     */
    public function usingRefreshToken(Request $request, $refreshToken): array
    {
        /**
         * Request access token using refresh token
         */
        $response = Http::post(env('VITE_AUTH_SERVER') . 'oauth/token', [
            'refresh_token' => $refreshToken->token,
            'grant_type' => 'authorization_code',
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                ...json_decode($response->body(), true)
            ];
        }

        /**
         * Set tokens
         */
        $this->setTokens($request, $response);

        return [
            'success' => true,
        ];
    }

    /**
     * Set tokens
     *
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws DataValidationException
     */
    protected function setTokens(Request $request, Response $response): void
    {
        $user = (array)JWT::decode($request->cookie('user_session'), new Key(config('auth.public_key'), 'RS256'));
        /**
         * Set access token and expiry in an encrypted session store
         */
        $request->session()->put('access_token', [
            'token_value' => $response['access_token'],
            'token_expiry' => CarbonImmutable::createFromTimestamp(time() + $response['expires_in'])
        ]);

        /**
         * If the auth server returned an refresh token (If this is the first time the application has
         * requested an access token), Store the refresh token in database
         */
        if (array_key_exists('refresh_token', json_decode($response->body(), true))) {
            $this->refreshTokenRepository->create([
                'user_id' => $user['sub'],
                'token' => $response['refresh_token']
            ]);
        }
    }
}
