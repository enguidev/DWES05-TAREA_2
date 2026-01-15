<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Rey
 * Se mueve una casilla en cualquier dirección (horizontal, vertical o diagonal)
 * No tiene valor asignado (su pérdida significa perder la partida)
 */
class Rey extends Pieza
{
  /**
   * Constructor de Rey
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 0; // El rey no tiene valor (su pérdida = derrota)
  }

  /*
   Para verificar el movimiento del rey:
   - Convertimos las posiciones actuales y nuevas a coordenadas numéricas (fila, columna).
   - El rey se mueve solo una casilla en cualquier dirección (horizontal, vertical o diagonal).
   - Verificamos que la diferencia de filas y columnas sea como máximo 1.
  */
  public function movimiento($nuevaPosicion)
  {
    // Si la pieza está capturada, no puede moverse por lo que retornamos false
    if ($this->estCapturada()) return false;

    // Convertimos las posiciones a coordenadas numéricas
    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    // Si alguna de las conversiones falla, retornamos false
    if (!$coordsActuales || !$coordsNuevas) return false;

    // Obtenemos las coordenadas de las posiciones actuales y nuevas
    // Con list() asignamos los valores de los arrays a variables individuales (desestructuración de arrays)
    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // Calculamos las diferencias absolutas
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // El rey se mueve solo una casilla en cualquier dirección
    // Verificamos que ambas diferencias sean <= 1 y que al menos una sea > 0 (para que se mueva)
    $esMovimientoValido = ($difFilas <= 1 && $difCols <= 1) && ($difFilas + $difCols > 0);

    // Si el movimiento es válido...
    if ($esMovimientoValido) {
      $this->posicion = $nuevaPosicion; // Actualizamos la posición
      $this->haMovido = true; // Marcamos que el rey se ha movido (importante para el enroque)
      return true; // Retornamos true
    }

    return false; // Si no es válido, retornamos false
  }

  /*
   Para simular el movimiento del rey:
   - El rey se mueve solo una casilla (o dos en enroque).
   - Como no hay casillas intermedias en movimientos de 1 casilla, solo devolvemos la posición final.
   - Para enroque: diferencia de 2 columnas en la misma fila.
  */
  public function simulaMovimiento($nuevaPosicion)
  {
    // Si la pieza está capturada, no puede moverse, retornamos array vacío
    if ($this->estCapturada()) return [];

    // Convertimos las posiciones a coordenadas numéricas
    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return [];

    // Obtenemos las coordenadas de las posiciones actuales y nuevas
    // Con list() asignamos los valores de los arrays a variables individuales (desestructuración de arrays)
    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // Calculamos las diferencias
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Movimiento normal: 1 casilla en cualquier dirección
    // O movimiento de 2 columnas en la misma fila (enroque)
    $esMovimientoValido = (
      ($difFilas <= 1 && $difCols <= 1 && ($difFilas + $difCols > 0)) ||
      ($difFilas === 0 && $difCols === 2) // Enroque: 2 columnas en horizontal
    );

    if ($esMovimientoValido) {
      // El rey solo se mueve una casilla o dos (enroque), devolvemos solo la posición final
      return [$nuevaPosicion];
    }

    return []; // Si no es válido, retornamos array vacío
  }
}
