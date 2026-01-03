<?php

/**
 * Funciones auxiliares para la aplicación de ajedrez
 */

/**
 * Obtiene la imagen correspondiente a una pieza
 */
function obtenerImagenPieza($pieza)
{
  if ($pieza === null) return '';
  $color = $pieza->getColor();
  $carpeta = ($color === 'blancas') ? 'imagenes/fichas_blancas' : 'imagenes/fichas_negras';
  $colorNombre = ($color === 'blancas') ? 'blanca' : 'negra';

  if ($pieza instanceof Torre) $nombre = 'torre_' . $colorNombre;
  elseif ($pieza instanceof Caballo) $nombre = 'caballo_' . $colorNombre;
  elseif ($pieza instanceof Alfil) $nombre = 'alfil_' . $colorNombre;
  elseif ($pieza instanceof Dama) $nombre = 'dama_' . $colorNombre;
  elseif ($pieza instanceof Rey) $nombre = 'rey_' . $colorNombre;
  elseif ($pieza instanceof Peon) $nombre = 'peon_' . $colorNombre;
  else return '';

  return $carpeta . '/' . $nombre . '.png';
}

/**
 * Obtiene la pieza en una casilla específica
 */
function obtenerPiezaEnCasilla($posicion, $partida)
{
  $jugadores = $partida->getJugadores();
  $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion);
  if ($pieza) return $pieza;
  $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion);
  if ($pieza) return $pieza;
  return null;
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
