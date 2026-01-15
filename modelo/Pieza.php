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
  protected $haMovido;  // Indica si la pieza ya se ha movido

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
    $this->haMovido = false;
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
   * Verifica si la pieza ya se ha movido
   * @return bool True si se ha movido
   */
  public function haMovido()
  {
    return $this->haMovido;
  }

  /**
   * Marca la pieza como movida
   */
  public function setHaMovido()
  {
    $this->haMovido = true;
  }

  /*
   Convierte notación de ajedrez (A1-H8) a coordenadas de array [fila, columna]:
   - ord() devuelve el código ASCII del carácter.
   - Restamos ord('A') para obtener la columna: A=0, B=1, ..., H=7.
   - Para la fila: 8 - número da el índice: 8=0, 7=1, ..., 1=7.
   - Validamos que estén dentro del rango 0-7.
  */
  protected function notacionACoords($posicion)
  {
    // Verificamos que la posición tenga exactamente 2 caracteres
    if (strlen($posicion) != 2) return null;

    // Convertimos la letra de columna a número (A=0, B=1, ..., H=7)
    $columna = ord(strtoupper($posicion[0])) - ord('A'); // A=0, B=1, ..., H=7

    // Convertimos el número de fila a índice de array (8=0, 7=1, ..., 1=7)
    $fila = 8 - (int)$posicion[1]; // 8=0, 7=1, ..., 1=7

    // Verificamos que las coordenadas estén dentro del tablero
    if ($columna < 0 || $columna > 7 || $fila < 0 || $fila > 7) {
      return null; // Coordenadas inválidas
    }

    return [$fila, $columna]; // Retornamos el array con las coordenadas
  }

  /*
   Convierte coordenadas de array a notación de ajedrez:
   - chr() convierte un código ASCII a carácter.
   - Sumamos columna a ord('A') para obtener la letra: 0=A, 1=B, ..., 7=H.
   - Para el número: 8 - fila da el número de fila: 0=8, 1=7, ..., 7=1.
  */
  protected function coordsANotacion($fila, $columna)
  {
    // Convertimos la columna (0-7) a letra (A-H)
    $letra = chr(ord('A') + $columna);

    // Convertimos la fila (0-7) a número (8-1)
    $numero = 8 - $fila;

    return $letra . $numero; // Concatenamos letra y número (ej: "A1", "H8")
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
