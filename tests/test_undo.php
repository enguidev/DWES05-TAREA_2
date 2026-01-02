<?php
require_once __DIR__ . '/../modelo/Partida.php';

$partida = new Partida();

echo "Tablero inicial:\n";
echo $partida->muestraTablero();

echo "\n1. Blancas: E2->E4\n";
$partida->jugada('E2', 'E4');
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "\nDeshaciendo última jugada...\n";
$partida->deshacerJugada();
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "\nIntentando deshacer otra vez (debería fallar)...\n";
$partida->deshacerJugada();
echo $partida->getMensaje() . PHP_EOL;
