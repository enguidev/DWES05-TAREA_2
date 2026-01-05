<?php

// Funciones auxiliares para la aplicación de ajedrez

/**
 * Obtiene la imagen correspondiente a una pieza
 */
function obtenerImagenPieza($pieza)
{
  if ($pieza === null) {
    return '';
  }

  $color = $pieza->getColor();
  $carpeta = ($color === 'blancas') ? 'public/imagenes/fichas_blancas' : 'public/imagenes/fichas_negras';
  $colorNombre = ($color === 'blancas') ? 'blanca' : 'negra';

  // Mapa de clases de piezas a nombres de archivos
  $piezaNombres = [
    'Torre' => 'torre',
    'Caballo' => 'caballo',
    'Alfil' => 'alfil',
    'Dama' => 'dama',
    'Rey' => 'rey',
    'Peon' => 'peon'
  ];

  $tipoPieza = get_class($pieza);
  if (!isset($piezaNombres[$tipoPieza])) {
    return '';
  }

  $nombreArchivo = $piezaNombres[$tipoPieza] . '_' . $colorNombre . '.png';
  return $carpeta . '/' . $nombreArchivo;
}

/**
 * Obtiene la pieza en una casilla específica
 */
function obtenerPiezaEnCasilla($posicion, $partida)
{
  $jugadores = $partida->getJugadores();
  $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion);
  if ($pieza) {
    return $pieza;
  }
  $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion);
  return $pieza ?: null;
}

/**
 * Formatea segundos a formato MM:SS
 */
function formatearTiempo($segundos)
{
  $minutos = floor($segundos / 60);
  $segs = $segundos % 60;
  return sprintf("%02d:%02d", $minutos, $segs);
}
