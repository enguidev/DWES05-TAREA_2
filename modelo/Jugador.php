<?php

require_once __DIR__ . '/Torre.php';
require_once __DIR__ . '/Caballo.php';
require_once __DIR__ . '/Alfil.php';
require_once __DIR__ . '/Dama.php';
require_once __DIR__ . '/Rey.php';
require_once __DIR__ . '/Peon.php';

//  Clase Jugador. Representa un jugador con sus 16 piezas
class Jugador
{
  private $nombre; // Nombre del jugador

  private $color;  // "blancas" o "negras"

  private $piezas; // Array de 16 piezas


  /*
   Constructor del Jugador:
   - Inicializa el nombre ($nombre) y color del jugador ($color).
   - Crea las 16 piezas en sus posiciones iniciales según el color.
   - Blancas: piezas mayores en fila 1, peones en fila 2.
   - Negras: piezas mayores en fila 8, peones en fila 7.
  */
  public function __construct($nombre, $color)
  {
    $this->nombre = $nombre;
    $this->color = $color;
    $this->piezas = []; // Inicializamos el array de piezas vacío

    // Determinamos las filas iniciales según el color
    // Si el color es "blancas"...
    if ($color === "blancas") {

      $filaPiezasMayores = 1; // piezas mayores en fila 1

      $filaPeones = 2; // peones en fila 2

      // Si el color es "negras"... 
    } else {

      $filaPiezasMayores = 8; // piezas mayores en fila 8

      $filaPeones = 7; // peones en fila 7
    }

    // Creamos las piezas mayores en el orden estándar de ajedrez
    $this->piezas[] = new Torre("A" . $filaPiezasMayores, $color);    // Torre izquierda
    $this->piezas[] = new Caballo("B" . $filaPiezasMayores, $color);  // Caballo izquierdo
    $this->piezas[] = new Alfil("C" . $filaPiezasMayores, $color);    // Alfil izquierdo
    $this->piezas[] = new Dama("D" . $filaPiezasMayores, $color);     // Dama
    $this->piezas[] = new Rey("E" . $filaPiezasMayores, $color);      // Rey
    $this->piezas[] = new Alfil("F" . $filaPiezasMayores, $color);    // Alfil derecho
    $this->piezas[] = new Caballo("G" . $filaPiezasMayores, $color);  // Caballo derecho
    $this->piezas[] = new Torre("H" . $filaPiezasMayores, $color);    // Torre derecha

    // Creamos los 8 peones (uno en cada columna)
    $columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    // Recorremos las columnas para crear los peones
    foreach ($columnas as $columna) {

      // Creamos el peon en la fila correspondiente
      $this->piezas[] = new Peon($columna . $filaPeones, $color);
    }
  }


  // Para obtener el nombre del jugador
  public function getNombre()
  {
    return $this->nombre; // Retornamos el nombre
  }

  // Para obtener el color del jugador
  public function getColor()
  {
    return $this->color; // Retornamos el color
  }

  // Para obtener todas las piezas del jugador
  public function getPiezas()
  {
    return $this->piezas; // Retornamos el array de piezas
  }


  /*
    Para obtener la pieza en una posición específica:
    - Recorremos el array de piezas del jugador.
    - Si encontramos una pieza cuya posición coincide con la solicitada y no está capturada, la retornamos.
    - Si no encontramos ninguna, retornamos null.
  */
  public function getPiezaEnPosicion($posicion)
  {
    // Recorremos las piezas del jugador
    foreach ($this->piezas as $pieza) {

      // Si la posición coincide y la pieza no está capturada...
      if ($pieza->getPosicion() === $posicion && !$pieza->estCapturada()) return $pieza; // Retornamos la pieza encontrada
    }

    // Si no encontramos ninguna pieza en esa posición, retornamos null
    return null;
  }


  // Para obtener el rey del jugador
  public function getRey()
  {
    // Recorremos las piezas del jugador
    foreach ($this->piezas as $pieza) {

      // Si la pieza es un rey y no está capturada...
      if ($pieza instanceof Rey && !$pieza->estCapturada()) return $pieza;
    }

    // Si no encontramos el rey (debería existir siempre), retornamos null
    return null;
  }


  // Para calcular los puntos del jugador
  public function calcularPuntos()
  {

    $puntos = 0; // Inicializamos los puntos en 0

    // Recorremos las piezas del jugador
    foreach ($this->piezas as $pieza) {

      // Si la pieza no está capturada, sumamos su valor a los puntos
      if (!$pieza->estCapturada()) $puntos += $pieza->getValor();
    }

    return $puntos; // Retornamos el total de puntos
  }


  // Para verificar si el jugador ha perdido
  public function haPerdido()
  {
    $rey = $this->getRey(); // Obtenemos el rey del jugador

    return $rey === null || $rey->estCapturada(); // Retornamos true si el rey no existe o está capturado 
  }

  /*
   Para promover un peón a otra pieza:
   - Verificamos que sea un peón y que pueda promoverse (esté en última fila).
   - Creamos la nueva pieza (Dama, Torre, Alfil o Caballo) en la misma posición.
   - Reemplazamos el peón en el array de piezas por la nueva pieza.
  */
  public function promoverPeon($peon, $tipoPieza = 'Dama')
  {
    // Si no es un peón o no puede promoverse...  
    if (!$peon instanceof Peon || !$peon->puedePromoverse()) return false; // No es válido para promoción

    $posicion = $peon->getPosicion(); // Obtenemos la posición del peón

    $color = $peon->getColor(); // Obtenemos el color del peón

    // Crear la nueva pieza según el tipo solicitado
    switch ($tipoPieza) {

      // En el caso de ser una dama
      case 'Dama':

        $nuevaPieza = new Dama($posicion, $color); // Creamos una dama

        break;

      // En el caso de ser una torre
      case 'Torre':

        $nuevaPieza = new Torre($posicion, $color); // Creamos una torre

        break;

      // En el caso de ser un alfil
      case 'Alfil':

        $nuevaPieza = new Alfil($posicion, $color); // Creamos un alfil

        break;

      // En el caso de ser un caballo
      case 'Caballo':

        $nuevaPieza = new Caballo($posicion, $color); // Creamos un caballo
        break;

      // En caso de no ser un tipo válido
      default:
        return false; // Retornamos false
    }

    // Reemplazamos el peón en la lista de piezas
    // Buscamos el peón en el array de piezas
    foreach ($this->piezas as $key => $pieza) {

      // Si encontramos el peón...
      if ($pieza === $peon) { // Comparación por referencia

        $this->piezas[$key] = $nuevaPieza; // Reemplazamos el peón por la nueva pieza

        return true; // Retornamos true
      }
    }

    // Si no encontramos el peón, retornamos false
    return false;
  }
}
