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

  /**
   * Verifica si el movimiento es válido para un peón
   * @param string $nuevaPosicion Posición destino
   * @param bool $esCaptura Indica si el movimiento es una captura
   * @return bool True si el movimiento es válido
   */
  public function movimiento($nuevaPosicion, $esCaptura = false)
  {
    if ($this->estCapturada()) return false;

    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return false;

    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    // Dirección de avance según el color
    $direccion = ($this->color === "blancas") ? -1 : 1;

    $difFilas = $filaNueva - $filaActual;
    $difCols = abs($colNueva - $colActual);

    // Captura diagonal (1 casilla diagonal hacia adelante)
    if ($esCaptura) {
      if ($difFilas == $direccion && $difCols == 1) {
        $this->posicion = $nuevaPosicion;
        $this->esPrimerMovimiento = false;
        return true;
      }
    }
    // Movimiento normal (sin captura)
    else {
      // Avance de 1 casilla
      if ($difFilas == $direccion && $difCols == 0) {
        $this->posicion = $nuevaPosicion;
        $this->esPrimerMovimiento = false;
        return true;
      }

      // Avance de 2 casillas (solo en primer movimiento)
      if ($this->esPrimerMovimiento && $difFilas == (2 * $direccion) && $difCols == 0) {
        $this->posicion = $nuevaPosicion;
        $this->esPrimerMovimiento = false;
        return true;
      }
    }

    return false;
  }

  /**
   * Simula el movimiento del peón
   * @param string $nuevaPosicion Posición destino
   * @param bool $esCaptura Indica si es una captura
   * @return array Array de posiciones por las que pasa
   */
  public function simulaMovimiento($nuevaPosicion, $esCaptura = false)
  {
    if ($this->estCapturada()) return [];

    $coordsActuales = $this->notacionACoords($this->posicion);
    $coordsNuevas = $this->notacionACoords($nuevaPosicion);

    if (!$coordsActuales || !$coordsNuevas) return [];

    list($filaActual, $colActual) = $coordsActuales;
    list($filaNueva, $colNueva) = $coordsNuevas;

    $direccion = ($this->color === "blancas") ? -1 : 1;
    $difFilas = $filaNueva - $filaActual;
    $difCols = abs($colNueva - $colActual);

    $casillas = [];

    // Captura diagonal
    if ($esCaptura && $difFilas == $direccion && $difCols == 1) {
      $casillas[] = $nuevaPosicion;
    }
    // Avance de 1 casilla
    elseif (!$esCaptura && $difFilas == $direccion && $difCols == 0) {
      $casillas[] = $nuevaPosicion;
    }
    // Avance de 2 casillas
    elseif (!$esCaptura && $this->esPrimerMovimiento && $difFilas == (2 * $direccion) && $difCols == 0) {
      // Casilla intermedia
      $casillas[] = $this->coordsANotacion($filaActual + $direccion, $colActual);
      // Casilla final
      $casillas[] = $nuevaPosicion;
    }

    return $casillas;
  }

  /**
   * Obtiene si es el primer movimiento del peón
   * @return bool True si es el primer movimiento
   */
  public function getEsPrimerMovimiento()
  {
    return $this->esPrimerMovimiento;
  }

  /**
   * Verifica si el peón puede promoverse en su posición actual
   * @return bool True si puede promoverse
   */
  public function puedePromoverse()
  {
    if ($this->estCapturada()) return false;

    $coords = $this->notacionACoords($this->posicion);
    if (!$coords) return false;

    list($fila, $columna) = $coords;

    // Blancas promueven en fila 7 (índice 7, que es fila 8 en notación)
    // Negras promueven en fila 0 (índice 0, que es fila 1 en notación)
    if ($this->color === 'blancas' && $fila === 7) return true;
    if ($this->color === 'negras' && $fila === 0) return true;

    return false;
  }
}
