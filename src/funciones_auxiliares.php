<?php
// Funciones auxiliares para la aplicación de ajedrez

/**
 * Esta función devuelve la ruta de la imagen de una pieza
 */
function obtenerImagenPieza($pieza)
{
  // Si no hay pieza, devolvemos vacío
  if ($pieza === null) {
    return '';
  }

  // Obtenemos el color de la pieza
  $color = $pieza->getColor();
  // Según el color, usamos una carpeta u otra
  $carpeta = ($color === 'blancas') ? 'public/imagenes/fichas_blancas' : 'public/imagenes/fichas_negras';
  // Establecemos el nombre corto del color
  $colorNombre = ($color === 'blancas') ? 'blanca' : 'negra';

  // Aquí relacionamos el tipo de pieza con el nombre del archivo de imagen
  $piezaNombres = [
    'Torre' => 'torre',
    'Caballo' => 'caballo',
    'Alfil' => 'alfil',
    'Dama' => 'dama',
    'Rey' => 'rey',
    'Peon' => 'peon'
  ];

  // Obtenemos la clase de la pieza (por ejemplo "Torre")
  $tipoPieza = get_class($pieza);
  // Si no encontramos esa pieza, devolvemos vacío
  if (!isset($piezaNombres[$tipoPieza])) {
    return '';
  }

  // Construimos el nombre del archivo (por ejemplo "torre_blanca.png")
  $nombreArchivo = $piezaNombres[$tipoPieza] . '_' . $colorNombre . '.png';
  // Devolvemos la ruta completa a la imagen
  return $carpeta . '/' . $nombreArchivo;
}

/**
 * Esta función busca si hay una pieza en una posición del tablero
 */
function obtenerPiezaEnCasilla($posicion, $partida)
{
  // Obtenemos los dos jugadores
  $jugadores = $partida->getJugadores();
  // Primero buscamos en las piezas blancas
  $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion);
  // Si encontramos, la devolvemos
  if ($pieza) {
    return $pieza;
  }
  // Si no, buscamos en las piezas negras
  $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion);
  // Devolvemos la pieza (o null si no hay)
  return $pieza ?: null;
}

/**
 * Esta función convierte segundos a formato MM:SS (minutos:segundos)
 */
function formatearTiempo($segundos)
{
  // Dividimos los segundos entre 60 para obtener minutos
  $minutos = floor($segundos / 60);
  // Obtenemos los segundos que quedan después de contar minutos
  $segs = $segundos % 60;
  // Devolvemos el tiempo formateado con ceros a la izquierda
  return sprintf("%02d:%02d", $minutos, $segs);
}
