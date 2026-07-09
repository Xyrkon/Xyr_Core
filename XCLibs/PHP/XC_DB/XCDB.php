<?php

class XCDB
{
    protected array $params = [];
    public string $cte = '';

    /**
     * Konstruktor zůstává chráněný.
     */
    protected function __construct(protected \PDO $pdo) {}

    // =========================================================================
    // 1. PRIVÁTNÍ TOVÁRNÍ METODA (Společné nastavení pro všechny DB)
    // =========================================================================

    /**
     * Sjednotí konfiguraci PDO a vytvoří finální instanci XCDB.
     */
    private static function createInstance(\PDO $pdo): self
    {
        // Společné nastavení atributů pro jakoukoliv databázi
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Vrací novou nakonfigurovanou instanci
        return new self($pdo);
    }

    // =========================================================================
    // 2. VEŘEJNÉ TOVÁRNÍ METODY (User definuje jen čisté PDO)
    // =========================================================================

    /**
     * Vytvoří a vrátí novou instanci XCDB připojenou k hlavní databázi.
     */
    public static function mojeDB(): self
    {
        // Jen čistý pokus o připojení...
        $pdo = new \PDO("mysql:host=localhost;dbname=test_db;charset=utf8", "root", "");

        // ...a zbytek práce delegujeme na společnou private funkci
        return self::createInstance($pdo);
    }

    /**
     * Vytvoří a vrátí novou instanci XCDB připojenou k jiné databázi.
     */
    public static function sklady(): self
    {
        $pdo = new \PDO("pgsql:host=192.168.1.50;dbname=warehouse", "skladnik", "tajne_heslo");

        return self::createInstance($pdo);
    }

    // =========================================================================
    // 3. INSTANČNÍ METODY PRO DOTAZY A PARAMETRY (Zůstávají stejné)
    // =========================================================================

    public function q(string $value): string
    {
        return $this->pdo->quote($value);
    }

    public function qArray(array $values): string
    {
        $quotedValues = array_map(fn($val) => $this->pdo->quote((string)$val), $values);
        return implode(', ', $quotedValues);
    }

    public function addParams(array $params): self
    {
        foreach ($params as $name => $value) {
            $cleanName = ltrim($name, ':');
            $this->params[$cleanName] = $value;
        }
        return $this;
    }

    public function query(string $sql): array|object
    {
        $baseSql = trim($sql);
        $finalSql = !empty($this->cte) ? trim($this->cte) . "\n" . $baseSql : $baseSql;

        try {
            $stmt = $this->pdo->prepare($finalSql);
            $stmt->execute($this->params);

            if (preg_match('/^\s*(SELECT|WITH)/i', $finalSql)) {
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $results = ['affected_rows' => $stmt->rowCount()];
            }

            $this->reset();
            return $results;

        } catch (\Exception $e) {
            $error = new class($e->getMessage(), $finalSql, $this->params, $e->getCode()) {
                public function __construct(
                    public string $message,
                    public string $sql,
                    public array $params,
                    public int|string $code
                ) {}
            };

            $this->reset();
            return $error;
        }
    }

    protected function reset(): void
    {
        $this->params = [];
        $this->cte = '';
    }
}