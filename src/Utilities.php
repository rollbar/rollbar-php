<?php namespace Rollbar;

final class Utilities {
    public static function pascaleToCamel(input) {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $input)), '_');
    }
}
