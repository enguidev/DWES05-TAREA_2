<?php
session_start();

require_once 'modelo/Partida.php';

// Inicializar o recuperar la partida
if (!isset($_SESSION['partida']) || isset($_POST['reiniciar'])) {
  $_SESSION['partida'] = serialize(new Partida("Jugador 1", "Jugador 2"));
  $_SESSION['casilla_seleccionada'] = null;
}

$partida = unserialize($_SESSION['partida']);

// Procesar jugada
if (isset($_POST['seleccionar_casilla'])) {
  $casilla = $_POST['seleccionar_casilla'];

  if ($_SESSION['casilla_seleccionada'] === null) {
    // Verificar que la pieza seleccionada sea del turno actual
    $piezaSeleccionada = obtenerPiezaEnCasilla($casilla, $partida);
    if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $partida->getTurno()) {
      $_SESSION['casilla_seleccionada'] = $casilla;
    }
  } else {
    // Si se hace clic en una pieza propia, cambiar la selección
    $piezaClickeada = obtenerPiezaEnCasilla($casilla, $partida);
    if ($piezaClickeada && $piezaClickeada->getColor() === $partida->getTurno()) {
      $_SESSION['casilla_seleccionada'] = $casilla;
    } else {
      // Intentar mover la pieza seleccionada
      $origen = $_SESSION['casilla_seleccionada'];
      $destino = $casilla;

      $partida->jugada($origen, $destino);
      $_SESSION['casilla_seleccionada'] = null;

      $_SESSION['partida'] = serialize($partida);
    }
  }
}

$casillaSeleccionada = $_SESSION['casilla_seleccionada'];
$marcador = $partida->marcador();
$mensaje = $partida->getMensaje();
$turno = $partida->getTurno();
$jugadores = $partida->getJugadores();

// Obtener piezas capturadas
$piezasCapturadas = [
  'blancas' => [],
  'negras' => []
];

foreach ($jugadores['blancas']->getPiezas() as $pieza) {
  if ($pieza->estCapturada()) {
    $piezasCapturadas['blancas'][] = $pieza;
  }
}

foreach ($jugadores['negras']->getPiezas() as $pieza) {
  if ($pieza->estCapturada()) {
    $piezasCapturadas['negras'][] = $pieza;
  }
}

/**
 * Obtiene la ruta de la imagen de una pieza
 */
function obtenerImagenPieza($pieza)
{
  if ($pieza === null) return '';

  $color = $pieza->getColor();
  $carpeta = ($color === 'blancas') ? 'imagenes/fichas_blancas' : 'imagenes/fichas_negras';
  $colorNombre = ($color === 'blancas') ? 'blanco' : 'negro';

  if ($pieza instanceof Torre) {
    $nombre = 'torre_' . $colorNombre;
  } elseif ($pieza instanceof Caballo) {
    $nombre = 'caballo_' . $colorNombre;
  } elseif ($pieza instanceof Alfil) {
    $nombre = 'alfil_' . $colorNombre;
  } elseif ($pieza instanceof Dama) {
    $nombre = 'dama_' . $colorNombre;
  } elseif ($pieza instanceof Rey) {
    $nombre = 'rey_' . $colorNombre;
  } elseif ($pieza instanceof Peon) {
    $nombre = 'peon_' . $colorNombre;
  } else {
    return '';
  }

  return $carpeta . '/' . $nombre . '.png';
}

/**
 * Obtiene la pieza en una posición del tablero
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partida de Ajedrez - DWES05</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div class="container">
    <h1>Partida de Ajedrez</h1>

    <div class="mensaje <?php echo $partida->estaTerminada() ? 'terminada' : ''; ?>">
      <?php echo htmlspecialchars($mensaje); ?>
    </div>

    <div class="marcador-y-capturas">
      <div class="marcador">
        <div class="marcador-item blancas <?php echo $turno === 'blancas' ? 'turno-activo' : ''; ?>">
          <strong>⚪ Blancas:</strong> <?php echo $marcador[0]; ?> puntos
          <?php if ($turno === 'blancas'): ?>
            <div style="font-size: 0.9em; margin-top: 5px;">👈 Tu turno</div>
          <?php endif; ?>
        </div>
        <div class="marcador-item negras <?php echo $turno === 'negras' ? 'turno-activo' : ''; ?>">
          <strong>⚫ Negras:</strong> <?php echo $marcador[1]; ?> puntos
          <?php if ($turno === 'negras'): ?>
            <div style="font-size: 0.9em; margin-top: 5px;">👈 Tu turno</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="piezas-capturadas">
        <div class="capturadas-grupo">
          <h3>♟️ Piezas blancas capturadas:</h3>
          <div class="capturadas-lista">
            <?php foreach ($piezasCapturadas['blancas'] as $pieza): ?>
              <img src="<?php echo obtenerImagenPieza($pieza); ?>"
                alt="Capturada"
                class="pieza-capturada">
            <?php endforeach; ?>
            <?php if (empty($piezasCapturadas['blancas'])): ?>
              <span style="color: #999; font-size: 0.9em;">Ninguna</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="capturadas-grupo">
          <h3>♟️ Piezas negras capturadas:</h3>
          <div class="capturadas-lista">
            <?php foreach ($piezasCapturadas['negras'] as $pieza): ?>
              <img src="<?php echo obtenerImagenPieza($pieza); ?>"
                alt="Capturada"
                class="pieza-capturada">
            <?php endforeach; ?>
            <?php if (empty($piezasCapturadas['negras'])): ?>
              <span style="color: #999; font-size: 0.9em;">Ninguna</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="tablero-wrapper">
      <div class="tablero-contenedor">
        <div class="coordenada-esquina-superior-izquierda"></div>

        <?php
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($letras as $letra):
        ?>
          <div class="coordenada-superior"><?php echo $letra; ?></div>
        <?php endforeach; ?>

        <div class="coordenada-esquina-superior-derecha"></div>

        <?php
        for ($fila = 0; $fila < 8; $fila++):
          $numeroFila = 8 - $fila;
        ?>
          <div class="coordenada-izquierda"><?php echo $numeroFila; ?></div>

          <?php
          for ($col = 0; $col < 8; $col++):
            $columna = $letras[$col];
            $posicion = $columna . $numeroFila;

            $colorCasilla = ($fila + $col) % 2 == 0 ? 'blanca' : 'negra';
            $pieza = obtenerPiezaEnCasilla($posicion, $partida);
            $esSeleccionada = ($casillaSeleccionada === $posicion);

            // Calcular movimientos posibles
            $esMovimientoPosible = false;
            $esCaptura = false;

            if ($casillaSeleccionada !== null && !$esSeleccionada) {
              $piezaSeleccionada = obtenerPiezaEnCasilla($casillaSeleccionada, $partida);
              if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $turno) {
                $piezaEnDestino = obtenerPiezaEnCasilla($posicion, $partida);
                $hayPiezaDestino = ($piezaEnDestino !== null);

                if ($piezaSeleccionada instanceof Peon) {
                  $movimientos = $piezaSeleccionada->simulaMovimiento($posicion, $hayPiezaDestino);
                } else {
                  $movimientos = $piezaSeleccionada->simulaMovimiento($posicion);
                }

                if (!empty($movimientos)) {
                  $bloqueado = false;

                  if (!($piezaSeleccionada instanceof Caballo)) {
                    for ($i = 0; $i < count($movimientos) - 1; $i++) {
                      if (obtenerPiezaEnCasilla($movimientos[$i], $partida) !== null) {
                        $bloqueado = true;
                        break;
                      }
                    }
                  }

                  if (!$bloqueado) {
                    if ($piezaEnDestino !== null) {
                      if ($piezaEnDestino->getColor() !== $turno) {
                        $esMovimientoPosible = true;
                        $esCaptura = true;
                      }
                    } else {
                      $esMovimientoPosible = true;
                    }
                  }
                }
              }
            }
          ?>
            <div class="casilla <?php echo $colorCasilla; ?> <?php echo $esSeleccionada ? 'seleccionada' : ''; ?>">
              <?php if ($pieza !== null): ?>
                <form method="post" class="formulario">
                  <?php
                  $puedeSeleccionar = ($pieza->getColor() === $turno);
                  $claseBoton = $puedeSeleccionar ? 'puede-seleccionar' : 'no-puede-seleccionar';
                  ?>
                  <button type="submit"
                    name="seleccionar_casilla"
                    value="<?php echo $posicion; ?>"
                    class="btn-pieza-casilla <?php echo $claseBoton; ?> <?php echo $esCaptura ? 'btn-captura' : ''; ?>">
                    <img src="<?php echo obtenerImagenPieza($pieza); ?>"
                      alt="<?php echo get_class($pieza); ?>"
                      class="imagen-pieza">
                  </button>
                </form>
              <?php elseif ($esMovimientoPosible): ?>
                <form method="post" class="formulario">
                  <button type="submit"
                    name="seleccionar_casilla"
                    value="<?php echo $posicion; ?>"
                    class="btn-movimiento">
                    <span class="indicador-movimiento"></span>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          <?php endfor; ?>

          <div class="coordenada-derecha"><?php echo $numeroFila; ?></div>
        <?php endfor; ?>

        <div class="coordenada-esquina-inferior-izquierda"></div>

        <?php foreach ($letras as $letra): ?>
          <div class="coordenada-inferior"><?php echo $letra; ?></div>
        <?php endforeach; ?>

        <div class="coordenada-esquina-inferior-derecha"></div>
      </div>
    </div>

    <div class="botones-control">
      <form method="post" style="display: inline;">
        <button type="submit" name="reiniciar" class="btn-reiniciar">
          🔄 Reiniciar Partida
        </button>
      </form>
    </div>

    <div class="instrucciones">
      <p><strong>🎮 Cómo jugar:</strong></p>
      <ol>
        <li>Haz clic en una pieza del color del turno actual (resaltado con borde dorado)</li>
        <li>Los círculos verdes indican casillas vacías donde puedes mover</li>
        <li>El borde rojo pulsante indica piezas enemigas que puedes capturar</li>
        <li>Haz clic en otra pieza propia para cambiar de selección</li>
        <li>Captura el rey enemigo para ganar la partida</li>
      </ol>
    </div>
  </div>
</body>

</html>