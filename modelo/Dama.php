<?php

require_once __DIR__ . '/Pieza.php';

/*
  Clase Dama (Reina)
  Se mueve en líneas horizontales, verticales y diagonales
  Combina los movimientos de Torre y Alfil
 */
class Dama extends Pieza
{
  /*
  $posicion: Posición inicial
  $color: Color de la pieza
*/
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 9; // Valor de la dama
  }

  /*
   Para verificar el movimiento de la dama:
   - Convertimos las posiciones actuales y nuevas a coordenadas numéricas (fila, columna).
   - La dama combina movimientos de torre y alfil.
   - Puede moverse horizontal, vertical o diagonalmente.
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

    // Movimiento horizontal o vertical (como torre)
    $esHorizontal = ($filaActual == $filaNueva && $colActual != $colNueva);
    $esVertical = ($colActual == $colNueva && $filaActual != $filaNueva);

    // Movimiento diagonal (como alfil)
    $difFilas = abs($filaNueva - $filaActual);
    $difCols = abs($colNueva - $colActual);
    $esDiagonal = ($difFilas == $difCols && $difFilas > 0);

    // Si el movimiento es válido (horizontal, vertical o diagonal)...
    if ($esHorizontal || $esVertical || $esDiagonal) {
      $this->posicion = $nuevaPosicion; // Actualizamos la posición
      return true; // Retornamos true
    }

    return false; // Si no es válido, retornamos false
  }

  /*
   Para simular el movimiento de la dama:
   - La dama combina movimientos de torre (horizontal/vertical) y alfil (diagonal).
   - Dependiendo del tipo de movimiento, iteramos las casillas intermedias.
   - Usamos operadores ternarios para determinar las direcciones de movimiento.
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

    $casillas = []; // Array para almacenar las casillas intermedias

    // Movimiento horizontal (misma fila, diferente columna)
    if ($filaActual == $filaNueva && $colActual != $colNueva) {
      // Determinamos la dirección: +1 si avanza, -1 si retrocede
      $direccion = ($colNueva > $colActual) ? 1 : -1;
      for ($col = $colActual + $direccion; $col != $colNueva; $col += $direccion) {
        $casillas[] = $this->coordsANotacion($filaActual, $col);
      }
      $casillas[] = $nuevaPosicion; // Incluimos la posición final
    }
    // Movimiento vertical (misma columna, diferente fila)
    elseif ($colActual == $colNueva && $filaActual != $filaNueva) {
      // Determinamos la dirección: +1 si baja, -1 si sube
      $direccion = ($filaNueva > $filaActual) ? 1 : -1;
      for ($fila = $filaActual + $direccion; $fila != $filaNueva; $fila += $direccion) {
        $casillas[] = $this->coordsANotacion($fila, $colActual);
      }
      $casillas[] = $nuevaPosicion; // Incluimos la posición final
    }
    // Movimiento diagonal
    else {
      $difFilas = abs($filaNueva - $filaActual);
      $difCols = abs($colNueva - $colActual);

      // Verificar que es movimiento diagonal válido
      if ($difFilas == $difCols && $difFilas > 0) {
        // Determinamos las direcciones de movimiento en fila y columna
        $direccionFila = ($filaNueva > $filaActual) ? 1 : -1;
        $direccionCol = ($colNueva > $colActual) ? 1 : -1;

        // Inicializamos en la primera casilla intermedia
        $fila = $filaActual + $direccionFila;
        $col = $colActual + $direccionCol;

        // Iteramos hasta llegar a la posición final
        while ($fila != $filaNueva || $col != $colNueva) {
          $casillas[] = $this->coordsANotacion($fila, $col);
          $fila += $direccionFila; // Avanzamos en la dirección de la fila
          $col += $direccionCol;   // Avanzamos en la dirección de la columna
        }

        $casillas[] = $nuevaPosicion; // Incluimos la posición final
      }
    }

    return $casillas; // Retornamos el array de casillas
  }
}
