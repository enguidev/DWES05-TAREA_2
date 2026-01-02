<?php
require_once __DIR__ . '/../modelo/Partida.php';

$partida = new Partida();

echo "Tablero inicial:\n";
echo $partida->muestraTablero();

echo "\nMoviendo peón blanco D2->D4\n";
$partida->jugada('D2', 'D4');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón negro D7->D5\n";
$partida->jugada('D7', 'D5');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón blanco D4->D5 (captura)\n";
$partida->jugada('D4', 'D5');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón negro E7->E6\n";
$partida->jugada('E7', 'E6');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón blanco E2->E4\n";
$partida->jugada('E2', 'E4');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón negro E6->E5\n";
$partida->jugada('E6', 'E5');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón blanco E4->E5 (captura)\n";
$partida->jugada('E4', 'E5');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón negro F7->F6\n";
$partida->jugada('F7', 'F6');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón blanco E5->E6 (captura)\n";
$partida->jugada('E5', 'E6');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón negro G7->G6\n";
$partida->jugada('G7', 'G6');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón blanco E6->E7 (captura)\n";
$partida->jugada('E6', 'E7');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón negro H7->H6\n";
$partida->jugada('H7', 'H6');
echo $partida->getMensaje() . PHP_EOL;

echo "\nMoviendo peón blanco E7->E8 (captura y promoción)\n";
$partida->jugada('E7', 'E8');
echo $partida->getMensaje() . PHP_EOL;
echo $partida->muestraTablero();

echo "\nVerificando que E8 tenga una dama blanca (D mayúscula)\n";
