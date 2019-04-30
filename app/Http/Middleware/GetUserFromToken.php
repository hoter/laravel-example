<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class GetUserFromToken extends \Tymon\JWTAuth\Middleware\GetUserFromToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, \Closure $next)
    {
        $user = null;
        if ($request->expectsJson()) {
            if (!$token = $this->auth->setRequest($request)->getToken()) {
                throw new AuthenticationException('API token not provided');
            }
            try {
                $user = $this->auth->authenticate($token);
            } catch (TokenExpiredException $e) {
                throw new AuthenticationException('API token expired');
            } catch (JWTException $e) {
                throw new AuthenticationException('API token invalid');
            }

            if (!$user) {
                throw new AuthenticationException('User not found');
            }

            $this->events->fire('tymon.jwt.valid', $user);

            return $next($request);
        }

        // If there is any custom Implementation on non Ajax
        return parent::handle($request, $next);
    }
}
