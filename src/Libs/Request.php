<?php
/**
 * Request - DuckBrain
 *
 * Libería complementaria de la libería Router.
 * Contiene el cuerpo básico de la petición http (POST, GET, JSON, etc).
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;

class Request extends Neuron  {
    public Neuron $get;
    public Neuron $post;
    public Neuron $put;
    public Neuron $patch;
    public Neuron $json;
    public Neuron $params;
    public string $path;
    public string $error;
    public array  $next;

    /**
     * __construct
     *
     * @param string $path  Ruta actual tomando como raíz la instalación de DuckBrain.
     */
    public function __construct()
    {
        $this->path   = Router::currentPath();
        $this->get    = new Neuron($_GET);
        $this->post   = new Neuron($_POST);
        $this->put    = new Neuron();
        $this->patch  = new Neuron();

        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType === "application/json")
            $this->json = new Neuron(
                (object) json_decode(trim(file_get_contents("php://input")), false)
            );
        else {
            $this->json   = new Neuron();
            if (in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'])) {
                parse_str(file_get_contents("php://input"), $input_vars);
                $this->{strtolower($_SERVER['REQUEST_METHOD'])} = new Neuron($input_vars);
            }
        }

        $this->params = new Neuron();
    }

    /**
     * Corre las validaciones e intenta continuar con la pila de callbacks.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        if ($this->validate())
            return Middleware::next($this);

        return null;
    }

    /**
     * Inicia la validación que se haya configurado.
     *
     * @return bool
     */
    public function validate(): bool
    {
        $actual = match($_SERVER['REQUEST_METHOD']) {
            'GET', 'DELETE' => $this->get,
            default => $this->{strtolower($_SERVER['REQUEST_METHOD'])}
        };

        if (Validator::validateList(static::paramRules(), $this->params) &&
            Validator::validateList(static::getRules(),   $this->get   ) &&
            Validator::validateList(static::rules(),      $actual))
            return true;

        if (isset(static::messages()[Validator::$lastFailed]))
            $error =  static::messages()[Validator::$lastFailed];
        else {

            $error = 'Error: validation failed of '.preg_replace('/\./', ' as ', Validator::$lastFailed, 1);
        }

        return static::onInvalid($error);
    }

    /**
     * Reglas para el método actual.
     *
     * @return array
     */
    public static function rules(): array {
        return [];
    }

    /**
     * Reglas para los parámetros por URL.
     *
     * @return array
     */
    public static function paramRules(): array {
        return [];
    }

    /**
     * Reglas para los parámetros GET.
     *
     * @return array
     */
    public static function getRules(): array {
        return [];
    }

    /**
     * Mensajes de error en caso de fallar una validación.
     *
     * @return array
     */
    public static function messages(): array {
        return [];
    }

    /**
     * Función a ejecutar cuando se ha detectado un valor no válido.
     *
     * @param string $error
     *
     * @return false
     */
    protected function onInvalid(string $error): false
    {
        http_response_code(422);
        print($error);
        return false;
    }
}
