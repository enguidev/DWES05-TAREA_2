<?php

/*
  Clase base Pieza
  Representa una pieza de ajedrez con su posición y valor
*/
class Pieza
{
  protected $posicion; // Posición en notación de ajedrez (ej: "A1", "C4") o "CAPTURADA"
  protected $valor; // Valor numérico de la pieza
  protected $color; // Color de la pieza: "blancas" o "negras"
  protected $haMovido; // Indica si la pieza ya se ha movido

  /*
  Constructor de la clase Pieza:
   -$posicion: Posición inicial en notación de ajedrez
   -$color: Color de la pieza ("blancas" o "negras")
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

  // Para obtener la posición actual de la pieza
  public function getPosicion()
  {
    return $this->posicion; // Retornamos la posición actual
  }

  // Para establecer una nueva posición para la pieza
  public function setPosicion($posicion)
  {
    $this->posicion = $posicion; // Actualizamos la posición
  }

  // Para obtener el valor de la pieza
  public function getValor()
  {
    return $this->valor; // Retornamos el valor de la pieza
  }

  // Para obtener el color de la pieza
  public function getColor()
  {
    return $this->color; // Retornamos el color de la pieza
  }

  // Para verificar si la pieza está capturada
  public function estaCapturada()
  {
    return $this->posicion === "CAPTURADA"; // Retornamos true si está capturada
  }

  // Para marcar la pieza como capturada
  public function capturar()
  {
    $this->posicion = "CAPTURADA"; // Actualizamos la posición a "CAPTURADA"
  }


  // Para verificar si la pieza ya se ha movido
  public function haMovido()
  {
    return $this->haMovido; // Retornamos el estado de movimiento
  }


  // Para marcar la pieza como movida
  public function setHaMovido()
  {
    $this->haMovido = true; // Actualizamos el estado a movida
  }

  /*
   Para convertir una posición en notación de ajedrez (A1-H8) a coordenadas de array [fila, columna]:
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
   Para convertir coordenadas de array a notación de ajedrez:
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
  /*
   Para verificar si unas coordenadas están dentro del tablero:
   - El tablero es de 8x8, por lo que las filas y columnas válidas son de 0 a 7.
   - Comparamos las coordenadas con los límites del tablero.
   - Retornamos true si las coordenadas están dentro del tablero.
   -$fila Fila a verificar
   -$columna: Columna a verificar
   -return bool True si está dentro del tablero
  */
  protected function dentroDelTablero($fila, $columna)
  {
    // Retornamos true si las coordenadas están dentro del tablero
    return $fila >= 0 && $fila < 8 && $columna >= 0 && $columna < 8;
  }

  /*
    Para verificar si un movimiento es válido y lo realiza:
      -Las subclases deben implementar este método
      -$nuevaPosicion: Posición destino en notación de ajedrez
      -return bool True si el movimiento es válido y se realizó
  */
  public function movimiento($nuevaPosicion)
  {
    // Las subclases deben implementar este método
    return false; // Retornamos false
  }

  /*
    Para simular un movimiento:
      -Las subclases deben implementar este método
      -$nuevaPosicion: Posición destino en notación de ajedrez
      -return array Array de posiciones por las que pasa (vacío si es inválido)
  */
  public function simulaMovimiento($nuevaPosicion)
  {
    // Las subclases deben implementar este método
    return []; // Retornamos array vacío
  }
}
