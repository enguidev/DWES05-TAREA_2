<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Alfil
 * Se mueve en líneas diagonales
 */
class Alfil extends Pieza
{
  /**
   * Constructor de Alfil
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 3; // Valor del alfil
  }

  /**
   * Verifica si el movimiento es válido para un alfil y lo realiza
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

    // El alfil se mueve en diagonal: diferencia de filas == diferencia de columnas
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    if ($difFilas == $difCols && $difFilas > 0) {
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

    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Verificar que es movimiento diagonal
    if ($difFilas != $difCols || $difFilas == 0) return [];

    $casillas = [];
    $direccionFila = ($filaNueva > $filaActual) ? 1 : -1;
    $direccionCol = ($colNueva > $colActual) ? 1 : -1;

    $fila = $filaActual + $direccionFila;
    $col = $colActual + $direccionCol;

    while ($fila != $filaNueva || $col != $colNueva) {
      $casillas[] = $this->coordsANotacion($fila, $col);
      $fila += $direccionFila;
      $col += $direccionCol;
    }

    $casillas[] = $nuevaPosicion; // Incluimos la posición final

    return $casillas;
  }
}
