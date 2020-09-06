<?php

require_once(ABSPATH . 'wp-config.php');

class CurrencyExtractorError extends Exception {};

class DataExtractor
{
    function __construct() {
        try
        {
            $this->pdo = DataExtractor::get_pdo();
            if (count($this->get_pairs_list()) == 0){
                throw new Exception("No tables exist in courses schema");
            }
        } catch (Exception $e)
        {
            throw new CurrencyExtractorError(
                "Database was not initialised properly. <br>
                Maybe you did not start the parser? <br><br>
                ".$e->getMessage()
            );
        }
	}

    private static function get_pdo()
    {
        return new PDO(
            "mysql:host=".DB_HOST.";dbname=courses;charset=".DB_CHARSET,
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    private function get_pairs_list()
    {
        $query = "SELECT table_name FROM information_schema.tables
            WHERE table_schema = 'courses';";

        $result = $this->pdo->query($query);
        $arr = [];
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $line)
        {
            foreach ($line as $item)
            {
                $arr[] = $item;
            }
        }
        return $arr;
    }

    private function get_rate_by_currency($currency, $timeperiod)
    {
        if ($timeperiod == "day")
        {
            $query = "SELECT date, value 
                    FROM courses.%s
                    WHERE date BETWEEN NOW() - INTERVAL 1 DAY AND NOW();";
        }
        else if ($timeperiod == "month")
        {
            $query = "SELECT date, value 
                    FROM courses.%s
                    WHERE id %% 30 = 1 
                    AND date BETWEEN NOW() - INTERVAL 30 DAY AND NOW();";
        }
        else if ($timeperiod == "year")
        {
            $query = "SELECT date, value 
                    FROM courses.%s
                    WHERE id %% 365 = 1 
                    AND date BETWEEN NOW() - INTERVAL 365 DAY AND NOW();";
        }
        else
        {
            $query = "SELECT date, value 
                    FROM courses.%s";
        }
        $query = sprintf($query, $currency);
        $result = $this->pdo->query($query);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    private function get_pair_data($pair_name, $timeperiod)
    {
        foreach ($this->get_rate_by_currency($pair_name, $timeperiod) as $currency)
        {
            yield [$currency['date'], $currency['value']];
        }
    }

    public function echo_js_pair_data()
    {
        echo "{";
        $pairs_list = $this->get_pairs_list();
        foreach ($pairs_list as $j => $pair)
        {
            echo '"'.strtoupper($pair)."\": {";
            foreach (["day", "month", "year"] as $m => $timeperiod)
            {
                echo '"'.$timeperiod."\": {";
                $data = iterator_to_array($this->get_pair_data($pair, $timeperiod));

                echo "\"labels\": [";
                foreach ($data as $i => $dat)
                {
                    echo '"'.$dat[0].($i != count($data) - 1 ? '", ' : '"');
                }
                echo "], ";

                echo "\"y\": [";
                foreach ($data as $i => $dat)
                {
                    echo $dat[1].($i != count($data) - 1 ? ", " : "");
                }
                echo "] ";

                echo ($m != 2 ? "}, " : "} ");
            }
            echo ($j != count($pairs_list) - 1 ? "}, " : "} ");
        }
        echo "}";
    }
}

?>