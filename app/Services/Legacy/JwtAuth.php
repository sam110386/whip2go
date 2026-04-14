<?php

namespace App\Services\Legacy;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Component/Auth/JwtAuthenticate.php
 * JWT authentication adapter using firebase/php-jwt.
 */
class JwtAuth
{
    protected array $settings = [
        'fields' => [
            'id'       => 'id',
            'password' => 'password',
            'token'    => 'token',
        ],
        'parameter' => '_token',
        'header'    => 'X_JSON_WEB_TOKEN',
        'userTable' => 'users',
        'scope'     => [],
        'pepper'    => 'driveitaway',
        'getfields' => [],
    ];

    public function __construct(array $settings = [])
    {
        $this->settings = array_merge($this->settings, $settings);

        if (empty($this->settings['parameter']) && empty($this->settings['header'])) {
            throw new \InvalidArgumentException('You need to specify token parameter and/or header');
        }
    }

    /**
     * Stateless — always returns false (mirrors CakePHP behaviour).
     */
    public function authenticate(Request $request): bool
    {
        return false;
    }

    /**
     * Extract JWT from request and look up the user.
     *
     * @return array|false
     */
    public function getUser(Request $request)
    {
        $token = $this->getToken($request);
        if ($token) {
            return $this->findUser($token);
        }
        return false;
    }

    protected function getToken(Request $request): ?string
    {
        if (!empty($this->settings['header'])) {
            $token = $request->header(str_replace('_', '-', $this->settings['header']));
            if ($token) {
                return $token;
            }
        }
        if (!empty($this->settings['parameter'])) {
            $token = $request->query($this->settings['parameter']);
            if ($token) {
                return $token;
            }
        }
        return null;
    }

    /**
     * Decode JWT and look up user in DB.
     *
     * @return array|false
     */
    public function findUser(string $token, ?string $password = null)
    {
        $decoded = JWT::decode($token, new Key($this->settings['pepper'], 'HS256'));

        if (isset($decoded->record)) {
            return json_decode(json_encode($decoded->record), true);
        }

        $fields = $this->settings['fields'];
        $query = DB::table($this->settings['userTable'])
            ->where($fields['id'], $decoded->user->userId ?? null)
            ->where($fields['token'], $decoded->user->token ?? null);

        if (!empty($this->settings['scope'])) {
            foreach ($this->settings['scope'] as $col => $val) {
                $query->where($col, $val);
            }
        }

        if (!empty($this->settings['getfields'])) {
            $query->select($this->settings['getfields']);
        }

        $result = $query->first();
        if (empty($result)) {
            return false;
        }

        return (array)$result;
    }

    /**
     * Refresh an expired JWT token.
     */
    public function refresh(string $token, int $tokenIncreaseTo): array
    {
        try {
            JWT::decode($token, new Key($this->settings['pepper'], 'HS256'));
            return ['status' => 1, 'message' => 'Token is valid', 'auth_token' => $token];
        } catch (ExpiredException $e) {
            JWT::$leeway = 720000;
            $decoded = (array)JWT::decode($token, new Key($this->settings['pepper'], 'HS256'));
            JWT::$leeway = 0;

            $decoded['iat'] = time();
            $decoded['exp'] = time() + $tokenIncreaseTo;

            $newToken = JWT::encode($decoded, $this->settings['pepper'], 'HS256');
            return ['status' => 1, 'message' => 'Token refreshed', 'auth_token' => $newToken];
        } catch (\Exception $e) {
            return ['status' => 0, 'message' => $e->getMessage(), 'auth_token' => ''];
        }
    }
}
