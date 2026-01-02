<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Caballo
 * Se mueve en forma de L: 2 casillas en una dirección y 1 en perpendicular
 * Es la única pieza que puede saltar sobre otras
 */
class Caballo extends Pieza
{
  /**
   * Constructor de Caballo
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 3; // Valor del caballo
  }

  /**
   * Verifica si el movimiento es válido para un caballo y lo realiza
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

    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Movimiento en L: (2,1) o (1,2)
    $esMovimientoL = ($difFilas == 2 && $difCols == 1) || ($difFilas == 1 && $difCols == 2);

    if ($esMovimientoL) {
      $this->posicion = $nuevaPosicion;
      return true;
    }

    return false;
  }

  /**
   * Simula el movimiento del caballo
   * Como el caballo salta, solo devuelve la posición final si es válida
   * @param string $nuevaPosicion Posición destino
   * @return array Array con solo la posición final (el caballo salta)
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

    $esMovimientoL = ($difFilas == 2 && $difCols == 1) || ($difFilas == 1 && $difCols == 2);

    if ($esMovimientoL) {
      // El caballo salta, solo devolvemos la posición final
      return [$nuevaPosicion];
    }

    return [];
  }
}
