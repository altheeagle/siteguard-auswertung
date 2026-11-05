<?php
namespace netzgrenze;

class utils {






    /**
     * Gibt die Debug-Ausgabe von `var_dump()` in einem <pre>-Tag aus.
     *
     * @param mixed $value Der zu dumpende Wert.
     * @return void
     */
   public static function vd($value){
        echo "<pre>";
        var_dump($value);
        echo "</pre>";
    }

    /**
     * Gibt die Debug-Ausgabe von `print_r()` in einem <pre>-Tag aus.
     *
     * @param mixed $value Der auszugebende Wert.
     * @return void
     */
    public static function pr($value){
        echo "<pre>";
        print_r($value);
        echo "</pre>";
    }

    /**
     * Gibt die Debug-Ausgabe auf der Console aus
     *
     * @param mixed $value Der auszugebende Wert.
     * @return void
     */
    public static function clog($value){
        file_put_contents( "php://stdout",$value);
    }

}
?>