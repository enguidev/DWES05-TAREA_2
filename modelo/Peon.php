<?php

require_once __DIR__ . '/Pieza.php';

/**
 * Clase Peon
 * Movimiento especial:
 * - Avanza 1 casilla hacia adelante (sin captura)
 * - Avanza 2 casillas en su primer movimiento (sin captura)
 * - Captura en diagonal (1 casilla en diagonal hacia adelante)
 */
class Peon extends Pieza
{
  private $esPrimerMovimiento; // Indica si es el primer movimiento del peón

  /**
   * Constructor de Peon
   * @param string $posicion Posición inicial
   * @param string $color Color de la pieza
   */
  public function __construct($posicion, $color)
  {
    parent::__construct($posicion, $color);
    $this->valor = 1; // Valor del peón
    $this->esPrimerMovimiento = true;
  }

  /**
   * Permite forzar el estado de primer movimiento (útil para simulaciones)
   * @param bool $v
   */
  public function setEsPrimerMovimiento($v)
  {
    $this->esPrimerMovimiento = (bool)$v;
  }

  /*
   Para verificar el movimiento del peón:
   - El peón tiene movimientos especiales según si es captura o no.
   - Avance normal: 1 casilla hacia adelante (blancas -1, negras +1).
   - Primer movimiento: puede avanzar 2 casillas.
   - Captura: 1 casilla en diagonal hacia adelante.
  */
  public function movimiento($nuevaPosicion, $esCaptura = false)
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

    // Dirección de avance según el color
    // Blancas avanzan hacia arriba (fila disminuye): dirección = -1
    // Negras avanzan hacia abajo (fila aumenta): dirección = +1
    $direccion = ($this->color === "blancas") ? -1 : 1;

    // Calculamos las diferencias de posición
    $difFilas = $filaNueva - $filaActual;
    $difCols = abs($colNueva - $colActual);

    // Captura diagonal (1 casilla diagonal hacia adelante)
    if ($esCaptura) {
      if ($difFilas == $direccion && $difCols == 1) {
        $this->posicion = $nuevaPosicion; // Actualizamos posición
        $this->esPrimerMovimiento = false; // Ya no es primer movimiento
        return true; // Movimiento válido
      }
    }
    // Movimiento normal (sin captura)
    else {
      // Avance de 1 casilla
      if ($difFilas == $direccion && $difCols == 0) {
        $this->posicion = $nuevaPosicion; // Actualizamos posición
        $this->esPrimerMovimiento = false; // Ya no es primer movimiento
        return true; // Movimiento válido
      }

      // Avance de 2 casillas (solo en primer movimiento)
      if ($this->esPrimerMovimiento && $difFilas == (2 * $direccion) && $difCols == 0) {
        $this->posicion = $nuevaPosicion; // Actualizamos posición
        $this->esPrimerMovimiento = false; // Ya no es primer movimiento
        return true; // Movimiento válido
      }
    }

    return false; // Si no es válido, retornamos false
  }

  /*
   Para simular el movimiento del peón:
   - Si es captura diagonal: solo devolvemos la casilla final.
   - Si es avance de 1: solo la casilla final.
   - Si es avance de 2 (primer movimiento): casilla intermedia + casilla final.
  */
  public function simulaMovimiento($nuevaPosicion, $esCaptura = false)
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

    // Dirección de avance según el color (blancas: -1, negras: +1)
    $direccion = ($this->color === "blancas") ? -1 : 1;
    $difFilas = $filaNueva - $filaActual;
    $difCols = abs($colNueva - $colActual);

    $casillas = []; // Array para almacenar las casillas

    // Captura diagonal
    if ($esCaptura && $difFilas == $direccion && $difCols == 1) {
      $casillas[] = $nuevaPosicion; // Solo la casilla final
    }
    // Avance de 1 casilla
    elseif (!$esCaptura && $difFilas == $direccion && $difCols == 0) {
      $casillas[] = $nuevaPosicion; // Solo la casilla final
    }
    // Avance de 2 casillas (primer movimiento)
    elseif (!$esCaptura && $this->esPrimerMovimiento && $difFilas == (2 * $direccion) && $difCols == 0) {
      // Casilla intermedia
      $casillas[] = $this->coordsANotacion($filaActual + $direccion, $colActual);
      // Casilla final
      $casillas[] = $nuevaPosicion;
    }

    return $casillas; // Retornamos el array de casillas
  }

  /**
   * Obtiene si es el primer movimiento del peón
   * @return bool True si es el primer movimiento
   */
  public function getEsPrimerMovimiento()
  {
    return $this->esPrimerMovimiento;
  }

  /*
   Para verificar si el peón puede promoverse:
   - El peón se promociona al llegar a la última fila del oponente.
   - Blancas: fila 8 (índice 0 en nuestro sistema de coordenadas).
   - Negras: fila 1 (índice 7 en nuestro sistema de coordenadas).
  */
  public function puedePromoverse()
  {
    // Si la pieza está capturada, no puede promoverse
    if ($this->estCapturada()) return false;

    // Convertimos la posición a coordenadas
    $coords = $this->notacionACoords($this->posicion);
    if (!$coords) return false;

    // Obtenemos la fila actual
    // Con list() asignamos los valores del array a variables individuales (desestructuración de arrays)
    list($fila, $columna) = $coords;

    // Sistema de coordenadas: A8 = fila 0, A7 = fila 1, ..., A1 = fila 7
    // Blancas promueven al llegar a la fila 8 (índice 0)
    // Negras promueven al llegar a la fila 1 (índice 7)
    if ($this->color === 'blancas' && $fila === 0) return true;
    if ($this->color === 'negras' && $fila === 7) return true;

    return false; // No puede promoverse
  }
}
