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

  /*
   Para verificar el movimiento de la torre:
   - Convertimos las posiciones actuales y nuevas a coordenadas numéricas (fila, columna).
   - La torre se mueve en líneas rectas: horizontales o verticales.
   - Horizontal: misma fila, diferente columna.
   - Vertical: misma columna, diferente fila.
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

    // La torre se mueve solo horizontal o verticalmente
    $esHorizontal = ($filaActual == $filaNueva && $colActual != $colNueva);
    $esVertical = ($colActual == $colNueva && $filaActual != $filaNueva);

    // Si el movimiento es válido...
    if ($esHorizontal || $esVertical) {
      $this->posicion = $nuevaPosicion; // Actualizamos la posición
      $this->haMovido = true; // Marcamos que la torre se ha movido (importante para el enroque)
      return true; // Retornamos true
    }

    return false; // Si no es válido, retornamos false
  }

  /*
   Para simular el movimiento de la torre:
   - Verificamos que sea movimiento horizontal o vertical.
   - Iteramos desde la posición actual hasta la nueva, agregando cada casilla intermedia.
   - Usamos operador ternario para determinar dirección: si nueva > actual entonces +1, sino -1.
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
      // Determinamos la dirección: +1 si avanza a la derecha, -1 si retrocede
      $direccion = ($colNueva > $colActual) ? 1 : -1;

      // Iteramos desde la columna siguiente hasta la final (sin incluir la inicial)
      for ($col = $colActual + $direccion; $col != $colNueva; $col += $direccion) {
        $casillas[] = $this->coordsANotacion($filaActual, $col);
      }
      $casillas[] = $nuevaPosicion; // Incluimos la posición final
    }
    // Movimiento vertical (misma columna, diferente fila)
    elseif ($colActual == $colNueva && $filaActual != $filaNueva) {
      // Determinamos la dirección: +1 si baja, -1 si sube
      $direccion = ($filaNueva > $filaActual) ? 1 : -1;

      // Iteramos desde la fila siguiente hasta la final (sin incluir la inicial)
      for ($fila = $filaActual + $direccion; $fila != $filaNueva; $fila += $direccion) {
        $casillas[] = $this->coordsANotacion($fila, $colActual);
      }
      $casillas[] = $nuevaPosicion; // Incluimos la posición final
    }

    return $casillas; // Retornamos el array de casillas
  }
}
