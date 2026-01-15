<?php

require_once __DIR__ . '/Pieza.php';

// Clase Alfil, que hereda la clase Pieza y se mueve en lineas diagonales
class Alfil extends Pieza
{

/*
  $posicion: Posición inicial
  $color: Color de la pieza
*/
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 3; // Valor del alfil
  }

  /*
   Para verificar el movimiento del alfil:
   - Convertimos las posiciones actuales y nuevas a coordenadas numéricas (fila, columna).
   - Calculamos la diferencia de filas y de columnas entre las posiciones actuales y nuevas.
   - Si la diferencia de filas es igual a la diferencia de columnas y ambas son mayores que 0, el movimiento es válido.
  */
  public function movimiento($nuevaPosicion)
  {
    // Si la pieza está capturada, no puede moverse por lo que retornamos false
    if ($this->estCapturada()) return false;

    // Convertimos las posiciones a coordenadas numéricas
    $coordsActuales = $this->notacionACoords($this->posicion);

    // Convertimos las posiciones a coordenadas numéricas
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    // Si alguna de las conversiones falla, retornamos false
    if (!$coordsActuales || !$coordsNuevas) return false;

    // Obtenemos las coordenadas de las posiciones actuales y nuevas
    // Con list() asignamos los valores de los arrays a variables individuales (desestructuración de arrays)
    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // El alfil se mueve en diagonal: diferencia de filas == diferencia de columnas
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Si el movimiento es válido...
    if ($difFilas == $difCols && $difFilas > 0) {

      $this->posicion = $nuevaPosicion;  // Actualizamos la posición

      return true; // Retornamos true
    }

    return false; // Si no es válido, retornamos false
  }

  /**
   * Simula el movimiento y devuelve todas las casillas intermedias
   * @param string $nuevaPosicion Posición destino
   * @return array Array de posiciones por las que pasa
   */
  /*
    Para simular el movimiento del alfil:
    - Convertimos las posiciones actuales y nuevas a coordenadas numéricas (fila, columna).
    - Calculamos la diferencia de filas y de columnas entre las posiciones actuales y nuevas.
    - Verificamos que el movimiento es diagonal (diferencia de filas igual a diferencia de columnas).
    - Iteramos desde la posición actual hasta la nueva, agregando cada casilla intermedia al array de resultados.
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

    // El alfil se mueve en diagonal: diferencia de filas == diferencia de columnas
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);

    // Verificar que es movimiento diagonal
    if ($difFilas != $difCols || $difFilas == 0) return [];

    $casillas = []; // Array para almacenar las casillas intermedias

    // Determinamos la dirección del movimiento
    // Si la fila nueva es mayor que la actual, el alfil se mueve hacia abajo (+1), si no hacia arriba (-1)
    $direccionFila = ($filaNueva > $filaActual) ? 1 : -1;

    // Si la columna nueva es mayor que la actual, el alfil se mueve hacia la derecha (+1), si no hacia la izquierda (-1)
    $direccionCol = ($colNueva > $colActual) ? 1 : -1;

    // Iteramos desde la posición actual hasta la nueva, agregando las casillas intermedias
    $fila = $filaActual + $direccionFila;

    // Iniciamos en la siguiente fila
    $col = $colActual + $direccionCol;

    // Mientras no lleguemos a la posición nueva
    while ($fila != $filaNueva || $col != $colNueva) {

      // Agregamos la casilla actual al array
      $casillas[] = $this->coordsANotacion($fila, $col);

      $fila += $direccionFila; // Avanzamos a la siguiente fila

      $col += $direccionCol; // Avanzamos a la siguiente columna
    }

    $casillas[] = $nuevaPosicion; // Incluimos la posición final

    return $casillas; // Retornamos el array de casillas
  }
}
