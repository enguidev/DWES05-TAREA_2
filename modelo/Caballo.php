<?php

require_once __DIR__ . '/Pieza.php';

/*
  Clase Caballo
  Se mueve en forma de L: 2 casillas en una dirección y 1 en perpendicular
  Es la única pieza que puede saltar sobre otras
 */
class Caballo extends Pieza
{
/*
  Constructor de la clase Caballo:
   -$posicion: Posición inicial
  - $color: Color de la pieza
*/
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 3; // Valor del caballo
  }

  /*
   Para verificar el movimiento del caballo:
   - Convertimos las posiciones actuales y nuevas a coordenadas numéricas (fila, columna).
   - Calculamos la diferencia de filas y de columnas entre las posiciones actuales y nuevas.
   - El caballo se mueve en forma de L: 2 casillas en una dirección y 1 en perpendicular.
   - Si se cumple (2,1) o (1,2), el movimiento es válido.
  */
  public function movimiento($nuevaPosicion)
  {
    // Si la pieza está capturada, no puede moverse por lo que retornamos false
      if ($this->estaCapturada()) return false;

    // Convertimos las posiciones a coordenadas numéricas
    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    // Si alguna de las conversiones falla, retornamos false
    if (!$coordsActuales || !$coordsNuevas) return false;

    // Obtenemos las coordenadas de las posiciones actuales y nuevas
    // Con list() asignamos los valores de los arrays a variables individuales (desestructuración de arrays)
    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // Calculamos las diferencias absolutas de filas y columnas
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Movimiento en L: (2,1) o (1,2)
    $esMovimientoL = ($difFilas == 2 && $difCols == 1) || ($difFilas == 1 && $difCols == 2);

    // Si el movimiento es válido...
    if ($esMovimientoL) {
      $this->posicion = $nuevaPosicion; // Actualizamos la posición
      return true; // Retornamos true
    }

    return false; // Si no es válido, retornamos false
  }

  /*
   Para simular el movimiento del caballo:
   - Verificamos que el movimiento sea válido (forma de L).
   - Como el caballo salta sobre otras piezas, no hay casillas intermedias.
   - Solo devolvemos la posición final si el movimiento es válido.
  */
  public function simulaMovimiento($nuevaPosicion)
  {
    // Si la pieza está capturada, no puede moverse, retornamos array vacío
      if ($this->estaCapturada()) return [];

    // Convertimos las posiciones a coordenadas numéricas
    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return [];

    // Obtenemos las coordenadas de las posiciones actuales y nuevas
    // Con list() asignamos los valores de los arrays a variables individuales (desestructuración de arrays)
    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // Calculamos las diferencias absolutas
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Verificamos movimiento en L
    $esMovimientoL = ($difFilas == 2 && $difCols == 1) || ($difFilas == 1 && $difCols == 2);

    if ($esMovimientoL) {
      // El caballo salta, solo devolvemos la posición final
      return [$nuevaPosicion];
    }

    return []; // Si no es válido, retornamos array vacío
  }
}
