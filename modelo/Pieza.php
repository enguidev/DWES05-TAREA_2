<?php

/**
 * Clase base Pieza
 * Representa una pieza de ajedrez con su posición y valor
 */
class Pieza
{
  protected $posicion;  // Posición en notación de ajedrez (ej: "A1", "C4") o "CAPTURADA"
  protected $valor;     // Valor numérico de la pieza
  protected $color;     // Color de la pieza: "blancas" o "negras"

  /**
   * Constructor de la clase Pieza
   * @param string $posicion Posición inicial en notación de ajedrez
   * @param string $color Color de la pieza ("blancas" o "negras")
   */
  public function __construct($posicion, $color)
  {
    $this->posicion = $posicion;
    $this->color = $color;
    $this->valor = 0; // Se establecerá en las subclases
  }

  /**
   * Obtiene la posición actual de la pieza
   * @return string Posición actual
   */
  public function getPosicion()
  {
    return $this->posicion;
  }

  /**
   * Establece una nueva posición para la pieza
   * @param string $posicion Nueva posición
   */
  public function setPosicion($posicion)
  {
    $this->posicion = $posicion;
  }

  /**
   * Obtiene el valor de la pieza
   * @return int Valor numérico
   */
  public function getValor()
  {
    return $this->valor;
  }

  /**
   * Obtiene el color de la pieza
   * @return string Color ("blancas" o "negras")
   */
  public function getColor()
  {
    return $this->color;
  }

  /**
   * Verifica si la pieza ha sido capturada
   * @return bool True si está capturada
   */
  public function estCapturada()
  {
    return $this->posicion === "CAPTURADA";
  }

  /**
   * Marca la pieza como capturada
   */
  public function capturar()
  {
    $this->posicion = "CAPTURADA";
  }

  /**
   * Convierte notación de ajedrez (A1-H8) a coordenadas de array [fila, columna]
   * @param string $posicion Posición en notación de ajedrez (ej: "A1")
   * @return array [fila, columna] o null si es inválida
   */
  protected function notacionACoords($posicion)
  {
    if (strlen($posicion) != 2) return null;

    $columna = ord(strtoupper($posicion[0])) - ord('A'); // A=0, B=1, ..., H=7
    $fila = 8 - (int)$posicion[1]; // 8=0, 7=1, ..., 1=7

    if ($columna < 0 || $columna > 7 || $fila < 0 || $fila > 7) {
      return null;
    }

    return [$fila, $columna];
  }

  /**
   * Convierte coordenadas de array a notación de ajedrez
   * @param int $fila Fila (0-7)
   * @param int $columna Columna (0-7)
   * @return string Posición en notación de ajedrez (ej: "A1")
   */
  protected function coordsANotacion($fila, $columna)
  {
    $letra = chr(ord('A') + $columna);
    $numero = 8 - $fila;
    return $letra . $numero;
  }

  /**
   * Verifica si unas coordenadas están dentro del tablero
   * @param int $fila Fila a verificar
   * @param int $columna Columna a verificar
   * @return bool True si está dentro del tablero
   */
  protected function dentroDelTablero($fila, $columna)
  {
    return $fila >= 0 && $fila < 8 && $columna >= 0 && $columna < 8;
  }

  /**
   * Método abstracto que deben implementar las subclases
   * Verifica si un movimiento es válido y lo realiza
   * @param string $nuevaPosicion Posición destino en notación de ajedrez
   * @return bool True si el movimiento es válido y se realizó
   */
  public function movimiento($nuevaPosicion)
  {
    // Las subclases deben implementar este método
    return false;
  }

  /**
   * Método abstracto que deben implementar las subclases
   * Simula un movimiento y devuelve todas las casillas por las que pasa
   * @param string $nuevaPosicion Posición destino
   * @return array Array de posiciones por las que pasa (vacío si es inválido)
   */
  public function simulaMovimiento($nuevaPosicion)
  {
    // Las subclases deben implementar este método
    return [];
  }
}
