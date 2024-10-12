<?php
/**
 * Neuron - DuckBrain
 *
 * Neuron, sirve para crear un objeto que alojará valores, pero
 * además tiene la característica especial de que al intentar
 * acceder a un atributo que no está definido devolerá nulo en
 * lugar de generar un error php notice que indica que se está
 * intentando acceder a un valor no definido.
 *
 * El constructor recibe un objeto o arreglo con los valores que
 * sí estarán definidos.
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class Neuron {

    /**
     * __construct
     *
     * @param array $data
     */
    public function __construct(...$data)
    {
        if (count($data) === 1 &&
            isset($data[0]) &&
            (is_array($data[0]) ||
             is_object($data[0])))
            $data = $data[0];

        foreach($data as $key => $value)
            $this->{$key} = $value;
    }

    /**
     * __get
     *
     * @param string $index
     * @return null
     */
    public function __get(string $index): null
    {
        return null;
    }
}

?>
