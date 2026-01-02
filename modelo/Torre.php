<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Torre
 * Se mueve en líneas horizontales o verticales
 */
class Torre extends Pieza
{
  /**
   * Constructor de Torre
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 5; // Valor de la torre
  }

  /**
   * Verifica si el movimiento es válido para una torre y lo realiza
   * @param string $nuevaPosicion Posición destino
   * @return bool True si el movimiento es válido
   */
  public function movimiento($nuevaPosicion)
  {
    if ($this->estCapturada()) return false;

    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return false;

    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // La torre se mueve solo horizontal o verticalmente
    $esHorizontal = ($filaActual == $filaNueva && $colActual != $colNueva);
    $esVertical = ($colActual == $colNueva && $filaActual != $filaNueva);

    if ($esHorizontal || $esVertical) {
      $this->posicion = $nuevaPosicion;
      return true;
    }

    return false;
  }

  /**
   * Simula el movimiento y devuelve todas las casillas intermedias
   * @param string $nuevaPosicion Posición destino
   * @return array Array de posiciones por las que pasa
   */
  public function simulaMovimiento($nuevaPosicion)
  {
    if ($this->estCapturada()) return [];

    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return [];

    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    $casillas = [];

    // Movimiento horizontal
    if ($filaActual == $filaNueva && $colActual != $colNueva) {
      $direccion = ($colNueva > $colActual) ? 1 : -1;
      for ($col = $colActual + $direccion; $col != $colNueva; $col += $direccion) {
        $casillas[] = $this->coordsANotacion($filaActual, $col);
      }
      $casillas[] = $nuevaPosicion; // Incluimos la posición final
    }
    // Movimiento vertical
    elseif ($colActual == $colNueva && $filaActual != $filaNueva) {
      $direccion = ($filaNueva > $filaActual) ? 1 : -1;
      for ($fila = $filaActual + $direccion; $fila != $filaNueva; $fila += $direccion) {
        $casillas[] = $this->coordsANotacion($fila, $colActual);
      }
      $casillas[] = $nuevaPosicion; // Incluimos la posición final
    }

    return $casillas;
  }
}
