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

    /**
     * Chytře ověří, zda je hodnota string (nebo objekt převoditelný na string) a volitelně ji ořeže.
     *
     * @param mixed $value Testovaná hodnota
     * @param bool $returnString Pokud je true, vrátí (ořezaný) string. Pokud false, vrátí bool.
     * @param bool $doTrim Pokud je true, ořeže z textu úvodní a koncové bílé znaky.
     * @return bool|string
     */
    public static function IsStr(mixed $value, bool $returnString = false, bool $doTrim = true): bool|string
    {
        // 1. Chytré ověření: Je to klasický string, nebo objekt s metodou __toString?
        $isStrictString = is_string($value) || (is_object($value) && method_exists($value, '__toString'));

        if (!$isStrictString) {
            return false;
        }

        // Převedeme na nativní string (zajistí bezpečný přetypování objektů)
        $strValue = (string)$value;

        // 2. Provedení trimu, pokud je vyžadován
        if ($doTrim) {
            $strValue = trim($strValue);
        }

        // 3. Vrácení výsledku podle požadovaného režimu
        return $returnString ? $strValue : true;
    }

    /**
     * Chytře ověří, zda hodnota reprezentuje boolean.
     * Automaticky akceptuje true, false, 1, 0, "1", "0", "true", "false"
     * a umožňuje přidat další vlastní povolené hodnoty.
     *
     * @param mixed $value Testovaná hodnota
     * @param array $additionalValues Volitelné pole dalších stringů, které mají být považovány za platný boolean
     * @return bool Vrací true, pokud je hodnota platný boolean, jinak false.
     */
    public static function IsBool(mixed $value, array $additionalValues = []): bool
    {
        // 1. Základní nativní boolean kontrola
        if (is_bool($value)) {
            return true;
        }

        // 2. Výchozí povolené hodnoty
        $allowedValues = ['1', '0', 'true', 'false'];

        // 3. Pokud uživatel poslal vlastní hodnoty, převedeme je na lowercase a přidáme k defaultům
        if (!empty($additionalValues)) {
            $cleanAdditional = array_map(function ($val) {
                return strtolower(trim((string)$val));
            }, $additionalValues);

            $allowedValues = array_merge($allowedValues, $cleanAdditional);
        }

        // 4. Převedeme testovanou hodnotu na čistý string a zkontrolujeme, zda existuje v poli
        if (is_scalar($value)) {
            $cleanValue = strtolower(trim((string)$value));

            return in_array($cleanValue, $allowedValues, true);
        }

        // Cokoliv jiného (pole, objekty, null) není boolean
        return false;
    }
}
