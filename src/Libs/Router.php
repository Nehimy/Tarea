<?php
/**
 * Router - DuckBrain
 *
 * Librería de Enrrutador.
 * Depende de manera forzada de que la constante ROOT_DIR esté definida
 * y de manera optativa de que la constante SITE_URL lo esté también.
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;

class Router {
    private static $get    = [];
    private static $post   = [];
    private static $put    = [];
    private static $patch  = [];
    private static $delete = [];
    private static $last;
    public  static $notFoundCallback = 'Libs\Router::defaultNotFound';

    /**
     * Función callback por defectio para cuando
     * no se encuentra configurada la ruta.
     *
     * @return void
     */
    public static function defaultNotFound (): void
    {
        header("HTTP/1.0 404 Not Found");
        echo '<h2 style="text-align: center;margin: 25px 0px;">Error 404 - Página no encontrada</h2>';
    }

    /**
     * __construct
     */
    private function __construct() {}

    /**
     * Parsea para deectar las pseudovariables (ej: {variable})
     *
     * @param string $path
     *   Ruta con pseudovariables.
     *
     * @param callable $callback
     *   Callback que será llamado cuando la ruta configurada en $path coincida.
     *
     * @return array
     *   Arreglo con 2 índices:
     *     path        - Contiene la ruta con las pseudovariables reeplazadas por expresiones regulares.
     *     callback    - Contiene el callback en formato Namespace\Clase::Método.
     */
    private static function parse(string $path, callable $callback): array
    {
        preg_match_all('/{(\w+)}/s', $path, $matches, PREG_PATTERN_ORDER);
        $paramNames = $matches[1];

        $path = preg_quote($path, '/');
        $path = preg_replace(
            ['/\\\{\w+\\\}/s'],
            ['([^\/]+)'],
            $path);

        return [
            'path'       => $path,
            'callback'   => [$callback],
            'paramNames' => $paramNames
        ];
    }


    /**
     * Devuelve el ruta base o raiz del proyecto sobre la que trabajará el router.
     *
     * Ej: Si la url del sistema está en "https://ejemplo.com/duckbrain"
     *     entonces la ruta base sería "/duckbrain"
     *
     * @return string
     */
    public static function basePath(): string
    {
        if (defined('SITE_URL') && !empty(SITE_URL))
            return rtrim(parse_url(SITE_URL, PHP_URL_PATH), '/').'/';
        return str_replace($_SERVER['DOCUMENT_ROOT'], '/', ROOT_DIR);
    }

    /**
     * Redirije a una ruta relativa interna.
     *
     * @param string $path
     *   La ruta relativa a la ruta base.
     *
     * Ej: Si nuesto sistema está en "https://ejemplo.com/duckbrain"
     *     llamamos a Router::redirect('/docs'), entonces seremos
     *     redirigidos a "https://ejemplo.com/duckbrain/docs".
     * @return void
     */
    public static function redirect(string $path): void
    {
        header('Location: '.static::basePath().ltrim($path, '/'));
        exit;
    }

    /**
     * Añade un middleware a la última ruta usada.
     * Solo se puede usar un middleware a la vez.
     *
     * @param callable $callback
     * @param int $prioriry
     *
     * @return static
     *   Devuelve la instancia actual.
     */
    public static function middleware(callable $callback, int $priority = null): static
    {
        if (!isset(static::$last))
            return new static();

        $method = static::$last[0];
        $index = static::$last[1];

        if (isset($priority) && $priority <= 0)
            $priority = 1;

        if (is_null($priority) || $priority >= count(static::$$method[$index]['callback']))
            static::$$method[$index]['callback'][] = $callback;
        else {
            static::$$method[$index]['callback'] = array_merge(
                array_slice(static::$$method[$index]['callback'], 0, $priority),
                [$callback],
                array_slice(static::$$method[$index]['callback'], $priority)
            );
        }

        return new static();
    }

    /**
     * Reconfigura el callback final de la última ruta.
     *
     * @param callable $callback
     *
     * @return static
     */
    public static function reconfigure(callable $callback): static
    {
        if (empty(static::$last))
            return new static();

        $method = static::$last[0];
        $index  = static::$last[1];

        static::$$method[$index]['callback'][0] = $callback;

        return new static();
    }

    /**
     * Configura calquier método para todas las rutas.
     *
     * En caso de no recibir un callback, busca la ruta actual
     * solo configura la ruta como la última configurada
     * siempre y cuando la misma haya sido configurada previamente.
     *
     * @param string $method
     *    Método http.
     * @param string $path
     *    Ruta con pseudovariables.
     * @param callable|null $callback
     *
     * @return
     *    Devuelve la instancia actual.
     */
    public static function configure(string $method, string $path, ?callable $callback = null): static
    {
        if (is_null($callback)) {
            $path = preg_quote($path, '/');
            $path = preg_replace(
                ['/\\\{\w+\\\}/s'],
                ['([^\/]+)'],
                $path);

            foreach(static::$$method as $index => $router)
                if ($router['path'] == $path) {
                    static::$last = [$method, $index];
                    break;
                }

            return new static();
        }

        static::$$method[] = static::parse($path, $callback);
        static::$last = [$method, count(static::$$method)-1];
        return new static();
    }

    /**
     * Define los routers para el método GET.
     *
     * @param string $path
     *   Ruta con pseudovariables.
     * @param callable $callback
     *   Callback que será llamado cuando la ruta configurada en $path coincida.
     *
     * @return static
     *   Devuelve la instancia actual.
     */
    public static function get(string $path, callable $callback = null): static
    {
        return static::configure('get', $path, $callback);
    }

    /**
     * Define los routers para el método POST.
     *
     * @param string $path
     *   Ruta con pseudovariables.
     * @param callable $callback
     *   Callback que será llamado cuando la ruta configurada en $path coincida.
     *
     * @return static
     *   Devuelve la instancia actual.
     */
    public static function post(string $path, callable $callback = null): static
    {
        return static::configure('post', $path, $callback);
    }

    /**
     * Define los routers para el método PUT.
     *
     * @param string $path
     *   Ruta con pseudovariables.
     * @param callable $callback
     *   Callback que será llamado cuando la ruta configurada en $path coincida.
     *
     * @return static
     *   Devuelve la instancia actual
     */

    public static function put(string $path, callable $callback = null): static
    {
        return static::configure('put', $path, $callback);
    }

    /**
     * Define los routers para el método PATCH.
     *
     * @param string $path
     *   Ruta con pseudovariables.
     * @param callable $callback
     *   Callback que será llamado cuando la ruta configurada en $path coincida.
     *
     * @return static
     *   Devuelve la instancia actual
     */
    public static function patch(string $path, callable $callback = null): static
    {
        return static::configure('patch', $path, $callback);
    }

    /**
     * Define los routers para el método DELETE.
     *
     * @param string $path
     *   Ruta con pseudovariables
     * @param callable $callback
     *   Callback que será llamado cuando la ruta configurada en $path coincida.
     *
     * @return static
     *   Devuelve la instancia actual
     */
    public static function delete(string $path, callable $callback = null): static
    {
        return static::configure('delete', $path, $callback);
    }

    /**
     * Devuelve la ruta actual tomando como raíz la ruta de instalación de DuckBrain.
     *
     * @return string
     */
    public static function currentPath() : string
    {
        return preg_replace('/'.preg_quote(static::basePath(), '/').'/',
                            '/', strtok($_SERVER['REQUEST_URI'], '?'), 1);
    }

    /**
     * Aplica la configuración de rutas.
     *
     * @param string $path (opcional) Ruta a usar. Si no se define, detecta la ruta actual.
     *
     * @return void
     */
    public static function apply(string $path = null): void
    {
        $path    = $path ?? static::currentPath();
        $routers = match($_SERVER['REQUEST_METHOD']) { // Según el método selecciona un arreglo de routers
            'POST'   => static::$post,
            'PUT'    => static::$put,
            'PATCH'  => static::$patch,
            'DELETE' => static::$delete,
            default  => static::$get
        };

        foreach ($routers as $router) { // revisa todos los routers para ver si coinciden con la ruta actual
            if (preg_match_all('/^'.$router['path'].'\/?$/si',$path, $matches, PREG_PATTERN_ORDER)) {
                unset($matches[0]);

                // Objtener un reflection del callback
                $lastCallback = $router['callback'][0];
                if ($lastCallback instanceof \Closure) { // si es función anónima
                    $reflectionCallback = new \ReflectionFunction($lastCallback);
                } else {
                    if (is_string($lastCallback))
                        $lastCallback = preg_split('/::/', $lastCallback);

                    // Revisamos su es un método o solo una función
                    if (count($lastCallback) == 2)
                        $reflectionCallback = new \ReflectionMethod($lastCallback[0], $lastCallback[1]);
                    else
                        $reflectionCallback = new \ReflectionFunction($lastCallback[0]);
                }

                // Obtener los parámetros
                $arguments  = $reflectionCallback->getParameters();
                if (isset($arguments[0])) {
                    // Obtenemos la clase del primer parámetro
                    $argumentClass = strval($arguments[0]->getType());

                    // Verificamos si la clase está o no tipada
                    if (empty($argumentClass)) {
                        $request = new Request;
                    } else {
                        $request = new $argumentClass;

                        // Verificamos que sea instancia de Request (requerimiento)
                        if (!($request instanceof Request))
                            throw new \Exception('Bad argument type on router callback.');
                    }
                } else {
                    $request = new Request;
                }

                // Comprobando y guardando los parámetros variables de la ruta
                if (isset($matches[1])) {
                    foreach ($matches as $index => $match) {
                        $paramName                   = $router['paramNames'][$index-1];
                        $request->params->$paramName = urldecode($match[0]);
                    }
                }

                // Llama a la validación y luego procesa la cola de callbacks
                $request->next = $router['callback'];
                $data          = $request->handle();

                // Por defecto imprime como JSON si se retorna algo
                if (isset($data)) {
                    header('Content-Type: application/json');
                    print(json_encode($data));
                }

                return;
            }
        }

        // Si no hay router que coincida llamamos a $notFoundCallBack
        call_user_func_array(static::$notFoundCallback, [new Request]);
    }
}
