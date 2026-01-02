<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Dama (Reina)
 * Se mueve en líneas horizontales, verticales y diagonales
 * Combina los movimientos de Torre y Alfil
 */
class Dama extends Pieza
{
  /**
   * Constructor de Dama
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 9; // Valor de la dama
  }

  /**
   * Verifica si el movimiento es válido para una dama y lo realiza
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

    // Movimiento horizontal o vertical (como torre)
    $esHorizontal = ($filaActual == $filaNueva && $colActual != $colNueva);
    $esVertical = ($colActual == $colNueva && $filaActual != $filaNueva);

    // Movimiento diagonal (como alfil)
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);
    $esDiagonal = ($difFilas == $difCols && $difFilas > 0);

    if ($esHorizontal || $esVertical || $esDiagonal) {
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
      $casillas[] = $nuevaPosicion;
    }
    // Movimiento vertical
    elseif ($colActual == $colNueva && $filaActual != $filaNueva) {
      $direccion = ($filaNueva > $filaActual) ? 1 : -1;
      for ($fila = $filaActual + $direccion; $fila != $filaNueva; $fila += $direccion) {
        $casillas[] = $this->coordsANotacion($fila, $colActual);
      }
      $casillas[] = $nuevaPosicion;
    }
    // Movimiento diagonal
    else {
      $difFilas = abs($filaNueva - $filaActual);
      $difCols = abs($colNueva - $colActual);

      if ($difFilas == $difCols && $difFilas > 0) {
        $direccionFila = ($filaNueva > $filaActual) ? 1 : -1;
        $direccionCol = ($colNueva > $colActual) ? 1 : -1;

        $fila = $filaActual + $direccionFila;
        $col = $colActual + $direccionCol;

        while ($fila != $filaNueva || $col != $colNueva) {
          $casillas[] = $this->coordsANotacion($fila, $col);
          $fila += $direccionFila;
          $col += $direccionCol;
        }

        $casillas[] = $nuevaPosicion;
      }
    }

    return $casillas;
  }
}
