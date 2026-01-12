<?php

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

  $tipoPieza = get_class($pieza); // Obtenemos la clase (get_class) de la pieza (por ejemplo "Torre")

  // Si no encontramos esa pieza, devolvemos vacío
  if (!isset($piezaNombres[$tipoPieza])) return '';

  // Construimos el nombre del archivo (por ejemplo "torre_blanca.png")
  $nombreArchivo = $piezaNombres[$tipoPieza] . '_' . $colorNombre . '.png';

  return $carpeta . '/' . $nombreArchivo; // Retornamos la ruta completa a la imagen
}

// Para buscar si hay una pieza en una posición del tablero
function obtenerPiezaEnCasilla($posicion, $partida)
{
  $jugadores = $partida->getJugadores(); // Obtenemos los dos jugadores

  $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion); // Buscamos en las piezas blancas

  if ($pieza) return $pieza; // Si encontramos la pieza, la retornamos

  $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion); // Buscamos en las piezas negras

  return $pieza ?: null; // Retornamos la pieza (o null si no hay)
}

// Para convertir segundos a formato MM:SS (minutos:segundos)
function formatearTiempo($segundos)
{
  /* Dividimos los segundos entre 60 para obtener minutos
    floor() redondea hacia abajo al entero
  */
  $minutos = floor($segundos / 60);

  /* Obtenemos los segundos que quedan después de contar minutos
    % 60 obtiene el resto de segundos que no llegan a un minuto
  */
  $segs = $segundos % 60;

  /* Retornamos el tiempo formateado con ceros a la izquierda
  sprintf() formatea la cadena según el patrón dado y devuelve la cadena formateada
  02d significa:
    %  indica que es aquí va un valor
    0  indica que se rellena con ceros si es necesario
    2  indica que el ancho mínimo es de 2 dígitos
    d  indica que es un número entero decimal
    : Separador entre minutos y segundos
  */
  return sprintf("%02d:%02d", $minutos, $segs);
}
