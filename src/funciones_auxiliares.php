<?php
// Funciones auxiliares para la aplicación de ajedrez

// Para devolver la ruta de la imagen de una pieza
function obtenerImagenPieza($pieza)
{
  // Si no hay pieza, devolvemos vacío
  if ($pieza === null) return '';

  $color = $pieza->getColor(); // Obtenemos el color de la pieza

  // Según el color, usamos una carpeta u otra
  $carpeta = ($color === 'blancas') ? 'public/imagenes/fichas_blancas' : 'public/imagenes/fichas_negras';

  $colorNombre = ($color === 'blancas') ? 'blanca' : 'negra'; // Nombre corto del color

  // Relacionamos el tipo de pieza con el nombre del archivo de imagen
  $piezaNombres = [
    'Torre' => 'torre',
    'Caballo' => 'caballo',
    'Alfil' => 'alfil',
    'Dama' => 'dama',
    'Rey' => 'rey',
    'Peon' => 'peon'
  ];

  $tipoPieza = get_class($pieza); // Obtenemos la clase de la pieza (por ejemplo "Torre")

  // Si no encontramos esa pieza, devolvemos vacío
  if (!isset($piezaNombres[$tipoPieza])) return '';

  // Construimos el nombre del archivo (por ejemplo "torre_blanca.png")
  $nombreArchivo = $piezaNombres[$tipoPieza] . '_' . $colorNombre . '.png';

  return $carpeta . '/' . $nombreArchivo; // Devolvemos la ruta completa a la imagen
}

// Para buscar si hay una pieza en una posición del tablero
function obtenerPiezaEnCasilla($posicion, $partida)
{
  $jugadores = $partida->getJugadores(); // Obtenemos los dos jugadores

  $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion); // Buscamos en las piezas blancas

  // Si encontramos la pieza, la devolvemos
  if ($pieza) return $pieza;

  $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion); // Buscamos en las piezas negras

  return $pieza ?: null; // Devolvemos la pieza (o null si no hay)
}

// Para convertir segundos a formato MM:SS (minutos:segundos)
function formatearTiempo($segundos)
{
  $minutos = floor($segundos / 60); // Dividimos los segundos entre 60 para obtener minutos

  $segs = $segundos % 60; // Obtenemos los segundos que quedan después de contar minutos

  return sprintf("%02d:%02d", $minutos, $segs); // Devolvemos el tiempo formateado con ceros a la izquierda
}
