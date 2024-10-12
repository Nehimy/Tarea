<?php
/**
 * Validator - DuckBrain
 *
 * Libería complementaria de la libería Request.
 * Sirve para simplpificar la verificación de valores.
 *
 * Tiene la posibilida de verificar tanto reglas individuales como en lote.
 *
 * |----------+--------------------------------------------------------|
 * | Regla    | Descripción                                            |
 * |----------+--------------------------------------------------------|
 * | not      | Niega la siguiente regla. Ej: not:float                |
 * | exists   | Es requerido; debe estar definido y puede estar vacío  |
 * | required | Es requerido; debe estar definido y no vacío           |
 * | number   | Es numérico                                            |
 * | int      | Es entero                                              |
 * | float    | Es un float                                            |
 * | bool     | Es booleano                                            |
 * | email    | Es un correo                                           |
 * | enum     | Esta en un lista ve valores. Ej: enum:admin,user,guest |
 * | url      | Es una url válida                                      |
 * |----------+--------------------------------------------------------|
 *
 * Las listas de reglas están separadas por |, Ej: required|email
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;

class Validator {
    public static string $lastFailed = '';

    /**
     * Validar lista de reglas sobre las propiedades de un objeto.
     *
     * @param array  $rulesList Lista de reglas.
     * @param Neuron $haystack  Objeto al que se le verificarán las reglas.
     *
     * @return bool  Retorna true solo si todas las reglas se cumplen y false en cuanto una falle.
     */
    public static function validateList(array $rulesList, Neuron $haystack): bool
    {
        foreach ($rulesList as $target => $rules) {
            $rules = preg_split('/\|/', $rules);
            foreach ($rules as $rule) {
                if (static::checkRule($haystack->{$target}, $rule))
                    continue;
                static::$lastFailed = $target.'.'.$rule;
                return false;
            }
        }

        return true;
    }

    /**
     * Revisa si una regla se cumple.
     *
     * @param mixed  $subject Lo que se va a verfificar.
     * @param string $rule    La regla a probar.
     *
     * @return bool
     */
    public static function checkRule(mixed $subject, string $rule): bool
    {
        $arguments    = preg_split('/[:,]/', $rule);
        $rule         = [static::class, $arguments[0]];
        $arguments[0] = $subject;

        if (is_callable($rule))
            return call_user_func_array($rule, $arguments);

        throw new \Exception('Bad rule: "'.preg_split('/::/', $rule)[1].'"' );
    }

    /**
     * Verifica la regla de manera negativa.
     *
     * @param mixed $subject Lo que se va a verfificar.
     * @param mixed $rule    La regla a probar.
     *
     * @return bool
     */
    public static function not(mixed $subject, ...$rule): bool
    {
        return !static::checkRule($subject, join(':', $rule));
    }

    /**
     * Comprueba que que esté definido/exista.
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function exists(mixed $subject): bool
    {
        return isset($subject);
    }

    /**
     * Comprueba que que esté definido y no esté vacío.
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function required(mixed $subject): bool
    {
        return isset($subject) && !empty($subject);
    }

    /**
     * number
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function number(mixed $subject): bool
    {
        return is_numeric($subject);
    }

    /**
     * int
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function int(mixed $subject): bool
    {
        return filter_var($subject, FILTER_VALIDATE_INT);
    }

    /**
     * float
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function float(mixed $subject): bool
    {
        return filter_var($subject, FILTER_VALIDATE_FLOAT);
    }

    /**
     * bool
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function bool(mixed $subject): bool
    {
        return filter_var($subject, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * email
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function email(mixed $subject): bool
    {
        return filter_var($subject, FILTER_VALIDATE_EMAIL);
    }

    /**
     * url
     *
     * @param mixed $subject
     *
     * @return bool
     */
    public static function url(mixed $subject): bool
    {
        return filter_var($subject, FILTER_VALIDATE_URL);
    }

    /**
     * enum
     *
     * @param mixed $subject
     * @param mixed $values
     *
     * @return bool
     */
    public static function enum(mixed $subject, ...$values): bool
    {
        return in_array($subject, $values);
    }
}
