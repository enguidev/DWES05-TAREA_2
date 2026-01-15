<?php

// ============================================
// ARCHIVO DE VISTAS - MÓDULO PRINCIPAL
// ============================================
// Este archivo es un contenedor que incluye todos los módulos de vistas
// Mantiene las funciones auxiliares aquí

// Para normalizar la ruta del avatar (si es personalizada o URL completa) 
function normalizarRutaAvatar($ruta)
{
  if (!$ruta) return null; // Si no hay ruta, retornamos null

  /* preg_match verifica si la cadena empieza con "http://" o "https://"
     Si es así, devolvemos la ruta tal cual
  */
  if (preg_match('/^https?:\/\//', $ruta)) return $ruta;

  // Si no, le añadimos "./" al principio y quitamos "/" del principio
  // ltrim($ruta, '/') elimina las barras iniciales
  return './' . ltrim($ruta, '/');
}

// ============================================
// INCLUIR MÓDULOS DE VISTAS
// ============================================

// Vistas de la pantalla inicial
require_once 'src/vistas/vista_inicio.php';

// Vistas de modales
require_once 'src/vistas/vista_modales.php';

// Vistas del juego (cabecera, botones, relojes)
require_once 'src/vistas/vista_juego.php';

// Vista del tablero
require_once 'src/vistas/vista_tablero.php';
