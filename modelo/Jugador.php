<?php

require_once __DIR__ . '/Torre.php';
require_once __DIR__ . '/Caballo.php';
require_once __DIR__ . '/Alfil.php';
require_once __DIR__ . '/Dama.php';
require_once __DIR__ . '/Rey.php';
require_once __DIR__ . '/Peon.php';

/**
 * Clase Jugador
 * Representa un jugador con sus 16 piezas
 */
class Jugador
{
  private $nombre;
  private $color;  // "blancas" o "negras"
  private $piezas; // Array de 16 piezas

  /*
   Constructor del Jugador:
   - Inicializa el nombre y color del jugador.
   - Crea las 16 piezas en sus posiciones iniciales según el color.
   - Blancas: piezas mayores en fila 1, peones en fila 2.
   - Negras: piezas mayores en fila 8, peones en fila 7.
  */
  public function __construct($nombre, $color)
  {
    $this->nombre = $nombre;
    $this->color = $color;
    $this->piezas = []; // Inicializamos el array de piezas vacío

    // Determinar la fila inicial según el color
    // Blancas: piezas mayores en fila 1, peones en fila 2
    // Negras: piezas mayores en fila 8, peones en fila 7
    if ($color === "blancas") {
      $filaPiezasMayores = 1;
      $filaPeones = 2;
    } else {
      $filaPiezasMayores = 8;
      $filaPeones = 7;
    }

    // Crear las piezas mayores en el orden estándar de ajedrez
    $this->piezas[] = new Torre("A" . $filaPiezasMayores, $color);    // Torre izquierda
    $this->piezas[] = new Caballo("B" . $filaPiezasMayores, $color);  // Caballo izquierdo
    $this->piezas[] = new Alfil("C" . $filaPiezasMayores, $color);    // Alfil izquierdo
    $this->piezas[] = new Dama("D" . $filaPiezasMayores, $color);     // Dama
    $this->piezas[] = new Rey("E" . $filaPiezasMayores, $color);      // Rey
    $this->piezas[] = new Alfil("F" . $filaPiezasMayores, $color);    // Alfil derecho
    $this->piezas[] = new Caballo("G" . $filaPiezasMayores, $color);  // Caballo derecho
    $this->piezas[] = new Torre("H" . $filaPiezasMayores, $color);    // Torre derecha

    // Crear los 8 peones (uno en cada columna)
    $columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    foreach ($columnas as $columna) {
      $this->piezas[] = new Peon($columna . $filaPeones, $color);
    }
  }

  /**
   * Obtiene el nombre del jugador
   * @return string Nombre del jugador
   */
  public function getNombre()
  {
    return $this->nombre;
  }

  /**
   * Obtiene el color de las piezas del jugador
   * @return string Color ("blancas" o "negras")
   */
  public function getColor()
  {
    return $this->color;
  }

  /**
   * Obtiene todas las piezas del jugador
   * @return array Array de piezas
   */
  public function getPiezas()
  {
    return $this->piezas;
  }

  /**
   * Busca una pieza en una posición específica
   * @param string $posicion Posición a buscar
   * @return Pieza|null La pieza encontrada o null
   */
  public function getPiezaEnPosicion($posicion)
  {
    foreach ($this->piezas as $pieza) {
      if ($pieza->getPosicion() === $posicion && !$pieza->estCapturada()) {
        return $pieza;
      }
    }
    return null;
  }

  /**
   * Obtiene el rey del jugador
   * @return Rey|null El rey o null si fue capturado
   */
  public function getRey()
  {
    foreach ($this->piezas as $pieza) {
      if ($pieza instanceof Rey && !$pieza->estCapturada()) {
        return $pieza;
      }
    }
    return null;
  }

  /**
   * Calcula los puntos totales del jugador (suma de valores de piezas activas)
   * @return int Puntos totales
   */
  public function calcularPuntos()
  {
    $puntos = 0;
    foreach ($this->piezas as $pieza) {
      if (!$pieza->estCapturada()) {
        $puntos += $pieza->getValor();
      }
    }
    return $puntos;
  }

  /**
   * Verifica si el rey del jugador ha sido capturado
   * @return bool True si el rey fue capturado (el jugador perdió)
   */
  public function haPerdido()
  {
    $rey = $this->getRey();
    return $rey === null || $rey->estCapturada();
  }

  /*
   Para promover un peón a otra pieza:
   - Verificamos que sea un peón y que pueda promoverse (esté en última fila).
   - Creamos la nueva pieza (Dama, Torre, Alfil o Caballo) en la misma posición.
   - Reemplazamos el peón en el array de piezas por la nueva pieza.
  */
  public function promoverPeon($peon, $tipoPieza = 'Dama')
  {
    // Verificamos que sea un peón y que pueda promoverse
    if (!$peon instanceof Peon || !$peon->puedePromoverse()) {
      return false; // No es válido para promoción
    }

    // Obtenemos la posición y color del peón
    $posicion = $peon->getPosicion();
    $color = $peon->getColor();

    // Crear la nueva pieza según el tipo solicitado
    switch ($tipoPieza) {
      case 'Dama':
        $nuevaPieza = new Dama($posicion, $color);
        break;
      case 'Torre':
        $nuevaPieza = new Torre($posicion, $color);
        break;
      case 'Alfil':
        $nuevaPieza = new Alfil($posicion, $color);
        break;
      case 'Caballo':
        $nuevaPieza = new Caballo($posicion, $color);
        break;
      default:
        return false; // Tipo de pieza no válido
    }

    // Reemplazar el peón en la lista de piezas
    foreach ($this->piezas as $key => $pieza) {
      if ($pieza === $peon) { // Comparación por referencia
        $this->piezas[$key] = $nuevaPieza; // Reemplazamos
        return true; // Promoción exitosa
      }
    }

    return false; // No se encontró el peón
  }
}
