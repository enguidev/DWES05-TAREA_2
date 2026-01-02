<?php
require_once __DIR__ . '/../modelo/Partida.php';

$partida = new Partida();

echo "Tablero inicial:\n";
echo $partida->muestraTablero();

echo "\n1. Blancas: F2->F3\n";
$partida->jugada('F2','F3');
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "\n1... Negras: E7->E5\n";
$partida->jugada('E7','E5');
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "\n2. Blancas: G2->G4\n";
$partida->jugada('G2','G4');
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "\n2... Negras: D8->H4 (Qh4#)\n";
$partida->jugada('D8','H4');
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "Partida terminada? " . ($partida->estaTerminada() ? 'sí' : 'no') . PHP_EOL;
echo "Jaque mate a blancas? " . ($partida->esJaqueMate('blancas') ? 'sí' : 'no') . PHP_EOL;
