<?php
/**
 * Middleware - DuckBrain
 *
 * Librería base para middlewares.
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;

class Middleware {

    /**
     * Llama al siguiente callback.
     *
     * @param Neuron $req
     *
     * @return mixed
     */
    public static function next(Neuron $req): mixed
    {
        $next = array_pop($req->next);
        return call_user_func_array($next, [$req]);
    }
}
