<?php

/**
 * Třída má funkce na validaci hodnot
 */
class XCvals
{
    /**
     * Validuje, zda je hodnota číslo (nebo číselný řetězec) a volitelně ověřuje konkrétní typy.
     * @param mixed $value Validovaná hodnota (číslo nebo string).
     * @param array<string> $types Pole povolených typů ('int', 'integer', 'float', 'double'). Pokud je prázdné, bere se jakékoliv číslo.
     * @param bool $retVal Pokud je true, vrací při úspěchu zvalidovanou a přetypovanou hodnotu.
     * @return bool|int Vrací bool (výsledek validace), nebo int/float (pokud $retVal = true a hodnota je validní). Při chybě vrací false.
     */
    public static function IsNum(
        mixed $value,
        array $types = [],
        bool $retVal = false
    ): mixed {
        // Převedeme hodnotu na řetězec a ořízneme mezery
        $stringValue = is_scalar($value) ? trim((string)$value) : '';

        // Základní ověření, zda jde vůbec o číselný formát
        if (!is_numeric($stringValue)) {
            return false;
        }

        // Pokud je pole prázdné, chceme validovat jakékoliv číslo
        if (empty($types)) {
            $types = ['any'];
        }

        $isValid = false;
        $matchedType = 'any';

        // Projdeme všechny požadované typy v poli a zkusíme najít shodu
        foreach ($types as $type) {
            $currentType = strtolower($type);

            $check = match ($currentType) {
                'int', 'integer' => filter_var($stringValue, FILTER_VALIDATE_INT) !== false,
                'float', 'double' => filter_var($stringValue, FILTER_VALIDATE_FLOAT) !== false,
                'any' => true,
                default => false,
            };

            if ($check) {
                $isValid = true;
                $matchedType = $currentType;
                break; // Jakmile najdeme první vyhovující typ, máme hotovo
            }
        }

        // Pokud hodnota nevyhověla žádnému z typů
        if (!$isValid) {
            return false;
        }

        // Pokud chceme vrátit hodnotu ($retVal = true), přetypujeme ji podle úspěšného typu
        if ($retVal) {
            return match ($matchedType) {
                'int', 'integer' => (int)$stringValue,
                'float', 'double' => (float)$stringValue,
                'any' => str_contains($stringValue, '.') ? (float)$stringValue : (int)$stringValue,
            };
        }

        return true;
    }
}
