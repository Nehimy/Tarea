<?php
/**
 * View - DuckBrain
 *
 * Manejador de vistas simplificado.
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
*/

namespace Libs;

class View extends Neuron {

    /**
     * Incluye el archivo.
     *
     * @param string $viewName  Ruta relativa y el nommbre sin extensión del archivo.
     * @param string $viewPath  (opcional) Ruta donde se encuentra la vista.
     * @param string $extension (opcional) Extensión del archivo.
     *
     * @return void
     */
    protected function include(
        string $viewName,
        string $viewPath  = null,
        string $extension = 'php'
    ): void
    {
        $view = $this;

        if (isset($viewPath) &&
            file_exists("$viewPath$viewName.$extension")) {
            include("$viewPath$viewName.$extension");
            return;
        }

        include(ROOT_CORE."/Views/$viewName.$extension");
    }

    /**
     * Función que "renderiza" las vistas
     *
     * @param string        $viewName  Ruta relativa y el nommbre sin extensión del archivo.
     * @param array|Neuron  $params    (opcional) Arreglo que podrá ser usado en la vista mediante $view ($param['index'] se usaría así: $view->index)
     * @param string        $viewPath  (opcional) Ruta donde se encuentra la vista. En caso de que la vista no se encuentre en esa ruta, se usará la ruta por defecto "src/Views/".
     * @param string        $extension (opcional) Extensión del archivo.
     *
     * @return void
     */
    public static function render(
        string $viewName,
        array|Neuron $params = [],
        string $viewPath     = null,
        string $extension    = 'php'
    ): void
    {
        $instance = new View($params);
        $instance->html($viewName, $viewPath);
    }

    /**
     * Renderiza las vistas HTML
     *
     * @param string $viewName  Ruta relativa y el nommbre sin extensión del archivo ubicado en src/Views
     * @param string $viewPath  (opcional) Ruta donde se encuentra la vista. En caso de que la vista no se encuentre en esa ruta, se usará la ruta por defecto "src/Views/".
     * @param string $extension (opcional) Extensión del archivo.
     *
     * @return void
     */
    public function html(
        string $viewName,
        string $viewPath  = null,
        string $extension = 'php'
    ): void
    {
        $this->include(
            $viewName,
            $viewPath,
            $extension
        );
    }

    /**
     * Renderiza código CSS.
     *
     * @param string $viewName  Ruta relativa y el nommbre sin extensión del archivo ubicado en src/Views
     * @param string $viewPath  (opcional) Ruta donde se encuentra la vista. En caso de que la vista no se encuentre en esa ruta, se usará la ruta por defecto "src/Views/".
     * @param string $extension (opcional) Extensión del archivo.
     *
     * @return void
     */
    public function css(
        string $viewName,
        string $viewPath  = null,
        string $extension = 'css'
    ): void
    {
        header("Content-type: text/css");
        $this->include($viewName, $viewPath, $extension);
    }

    /**
     * Renderiza código Javascript.
     *
     * @param string $viewName  Ruta relativa y el nommbre sin extensión del archivo ubicado en src/Views
     * @param string $viewPath  (opcional) Ruta donde se encuentra la vista. En caso de que la vista no se encuentre en esa ruta, se usará la ruta por defecto "src/Views/".
     * @param string $extension (opcional) Extensión del archivo.
     *
     * @return void
     */
    public function js(
        string $viewName,
        string $viewPath  = null,
        string $extension = 'js'
    ): void
    {
        header("Content-type: application/javascript");
        $this->include($viewName, $viewPath, $extension);
    }

    /**
     * Imprime los datos en Json.
     *
     * @param object|array $data Objeto o array que se imprimirá a JSON.
     *
     * @return void
     */
    public function json(object|array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        print(json_encode($data));
    }

    /**
     * Imprime los datos en texto plano
     *
     * @param string $txt Contenido de texto.
     *
     * @return void
     */
    public function text(string $txt): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        print($txt);
    }

    /**
     * Intenta devolver la url absoluta a partir de una ruta relativa.
     *
     * @param string $path
     *
     * @return string
     */
    public static function route(string $path = '/'): string
    {
        if (defined('SITE_URL') && !empty(SITE_URL))
            return rtrim(SITE_URL, '/').'/'.ltrim($path, '/');

        return $path;
    }
}
?>
