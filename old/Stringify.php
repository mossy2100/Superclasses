<?php

declare(strict_types = 1);


/**
 * This class provides JSON-like encoding with a few differences:
 * 1. Integer keys for PHP arrays (non-lists) are not quoted as they would be in JSON objects.
 * 2. PHP arrays are encoded with square brackets.
 * 3. Objects are encoded using an HTML tag-like syntax (angle brackets), with UML symbols to indicate visibility.
 * 4. Floats are encoded with a decimal point, 'E', or both, to differentiate them from ints.
 *
 * The purpose of the class is to offer a more readable alternative to var_dump(), print_r(), or serialize().
 *
 * None of these methods throw exceptions, so they can be used by __toString() implementations.
 */
class Stringify
{
    /**
     * Convert a value to a readable string representation.
     *
     * @param mixed $value The value to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the value.
     */
    public static function stringify(mixed $value, int $indent_level = 0): string
    {
        // Get the type.
        $type = get_debug_type($value);

        // Call the relevant encode method.
        switch ($type) {
            case 'null':
            case 'bool':
            case 'int':
            case 'string':
                return json_encode($value);

            case 'float':
                return self::stringifyFloat($value);

            case 'array':
                return self::stringifyArray($value, $indent_level);
        }

        // If it's a resource then $type will be "resource (type)" or "resource (closed)".
        if (str_starts_with($type, 'resource')) {
            return self::stringifyResource($value);
        }

        // Must be a class name.
        return self::stringifyObject($value, $type, $indent_level);
    }

    /**
     * Encode a float in such a way that it doesn't look like an integer.
     *
     * @param float $value The float value to encode.
     * @return string The string representation of the float.
     */
    public static function stringifyFloat(float $value): string
    {
        // Handle infinity and NaN specially.
        if (is_nan($value)) {
            return 'NaN';
        }
        if ($value === INF) {
            return '∞';
        }
        if ($value === -INF) {
            return '-∞';
        }

        // Convert the float to a string using the default method.
        $s = (string)$value;

        // If the string representation of the float value has no decimal point or exponent (i.e. nothing to distinguish
        // it from an integer), append a decimal point.
        if (!preg_match('/[.eE]/', $s)) {
            $s .= '.0';
        }

        return $s;
    }

    /**
     * Encode a PHP array as a list with square brackets or an associative array with braces and
     * key-value pairs.
     *
     * @param array $ary The array to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the array.
     */
    public static function stringifyArray(array $ary, int $indent_level = 0): string
    {
        // Encode a list like a JSON array. All elements will be on one line with no indentation.
        if (array_is_list($ary)) {
            return '[' . implode(', ', array_map('Stringify\Stringify', $ary)) . ']';
        }

        // Encode an associative array like a JSON object. Each key-value pair will be on its own line.
        $pairs = [];
        $indent = str_repeat(' ', 4 * ($indent_level + 1));

        foreach ($ary as $key => $value) {
            $pairs[] = $indent . self::stringify($key) . ': ' .
                self::stringify($value, $indent_level + 1);
        }

        return "{\n" . implode(",\n", $pairs) . "\n" . str_repeat(' ', 4 * $indent_level) . '}';
    }

    /**
     * Stringify a resource.
     *
     * @param mixed $value The resource to stringify.
     * @return string The string representation of the resource.
     */
    public static function stringifyResource(mixed $value): string {
        return 'resource:' . get_resource_type($value) . ':' . get_resource_id($value);
    }

    /**
     * Encode an object like an HTML tag with bonus UML symbols to indicate visibility.
     *
     * @param object $obj The object to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the object.
     */
    public static function stringifyObject(object $obj, string $class, int $indent_level = 0): string
    {
        // Call the __toString() method if implemented.
        if ($obj instanceof Stringable) {
            return $obj->__toString();
        }

        // Convert the object to an array to get its properties.
        // This works better than reflection, as new properties can be created when converting the object to an array
        // (example: DateTime).
        $a = (array)$obj;

        // Early return if no properties.
        if (count($a) === 0) {
            return "<$class>";
        }

        // Generate the strings for key-value pairs. Each will be on its own line.
        $pairs = [];
        $indent = str_repeat(' ', 4 * ($indent_level + 1));

        foreach ($a as $key => $value) {
            // Split on null bytes to determine the property name and visibility.
            $name_parts = explode("\0", $key);

            switch (count($name_parts)) {
                case 1:
                    $vis_symbol = '+';
                    break;

                case 3:
                    $vis_symbol = $name_parts[1] === '*' ? '#' : '-';
                    $key = $name_parts[2];
                    break;

                default:
                    // If there are 4 parts, the object is an anonymous class with a property
                    // indicating where the class is defined. We don't care about that, so ignore it.
                    continue 2;
            }

            $pairs[] = $indent . $vis_symbol . $key . ': ' . self::stringify($value, $indent_level + 1);
        }

        return "<$class\n" . implode(",\n", $pairs) . '>';
    }
}
