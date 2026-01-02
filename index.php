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
  $colorNombre = ($color === 'blancas') ? 'blanca' : 'negro';

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
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      padding: 40px;
      max-width: 900px;
      width: 100%;
    }

    h1 {
      font-size: 2.5em;
      color: #333;
      text-align: center;
      margin-bottom: 20px;
    }

    .mensaje {
      background: #e3f2fd;
      border-left: 4px solid #2196F3;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 5px;
      font-size: 1.1em;
      text-align: center;
    }

    .mensaje.terminada {
      background: #fff3cd;
      border-left: 4px solid #ffc107;
      font-weight: bold;
      font-size: 1.3em;
    }

    .marcador-y-capturas {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
      gap: 20px;
    }

    .marcador {
      display: flex;
      flex-direction: column;
      gap: 15px;
      flex: 1;
    }

    .marcador-item {
      padding: 15px;
      border-radius: 10px;
      text-align: center;
      font-size: 1.1em;
      transition: all 0.3s ease;
      border: 3px solid transparent;
    }

    .marcador-item.blancas {
      background: #f0f0f0;
      border-color: #999;
    }

    .marcador-item.negras {
      background: #333;
      color: white;
      border-color: #666;
    }

    .marcador-item.turno-activo {
      border-color: #ffd700;
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
      transform: scale(1.05);
    }

    .piezas-capturadas {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .capturadas-grupo {
      background: #f8f9fa;
      padding: 10px;
      border-radius: 8px;
      min-height: 60px;
    }

    .capturadas-grupo h3 {
      font-size: 0.9em;
      margin-bottom: 8px;
      color: #666;
    }

    .capturadas-lista {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
    }

    .pieza-capturada {
      width: 30px;
      height: 30px;
      opacity: 0.6;
    }

    .tablero-wrapper {
      margin-bottom: 30px;
    }

    .tablero-contenedor {
      display: grid;
      grid-template-columns: 30px repeat(8, 1fr) 30px;
      grid-template-rows: 30px repeat(8, 1fr) 30px;
      gap: 0;
      margin: 0 auto;
      max-width: 700px;
    }

    .coordenada-esquina-superior-izquierda {
      border-top: 2px solid #333;
      border-left: 2px solid #333;
      border-right: 1px solid #ddd;
      border-top-left-radius: 10px;
    }

    .coordenada-esquina-superior-derecha {
      border-top: 2px solid #333;
      border-right: 2px solid #333;
      border-left: 1px solid #ddd;
      border-top-right-radius: 10px;
    }

    .coordenada-esquina-inferior-izquierda {
      border-top: 1px solid #ddd;
      border-right: 1px solid #ddd;
      border-left: 2px solid #333;
      border-bottom: 2px solid #333;
      border-bottom-left-radius: 10px;
    }

    .coordenada-esquina-inferior-derecha {
      border-top: 1px solid #ddd;
      border-right: 2px solid #333;
      border-left: 1px solid #ddd;
      border-bottom: 2px solid #333;
      border-bottom-right-radius: 10px;
    }

    .coordenada-superior,
    .coordenada-inferior,
    .coordenada-izquierda,
    .coordenada-derecha {
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 0.9em;
      color: #333;
      background: #f8f9fa;
    }

    .coordenada-superior {
      border-top: 2px solid #333;
      border-left: 1px solid #ddd;
      border-right: 1px solid #ddd;
    }

    .coordenada-inferior {
      border-bottom: 2px solid #333;
      border-left: 1px solid #ddd;
      border-right: 1px solid #ddd;
    }

    .coordenada-izquierda,
    .coordenada-derecha {
      border-top: 1px solid #ddd;
      border-bottom: 1px solid #ddd;
    }

    .coordenada-izquierda {
      border-left: 2px solid #333;
    }

    .coordenada-derecha {
      border-right: 2px solid #333;
    }

    .casilla {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      padding: 0;
      margin: 0;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .casilla.blanca {
      background: #f0d9b5;
    }

    .casilla.negra {
      background: #222222;
    }

    .casilla.seleccionada {
      box-shadow: inset 0 0 0 3px #ffd700;
      background: #fff59d !important;
    }

    .formulario {
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0;
      padding: 0;
    }

    .btn-pieza-casilla {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      border: none;
      background: transparent;
      cursor: pointer;
      padding: 0;
      margin: 0;
      transition: all 0.2s ease;
    }

    .btn-pieza-casilla:hover {
      transform: scale(1.15);
    }

    .btn-pieza-casilla.puede-seleccionar {
      cursor: pointer;
    }

    .btn-pieza-casilla.puede-seleccionar:hover {
      filter: brightness(1.3) drop-shadow(0 0 8px rgba(255, 215, 0, 0.8));
    }

    .btn-pieza-casilla.no-puede-seleccionar {
      cursor: not-allowed;
      opacity: 0.5;
    }

    .imagen-pieza {
      width: 100%;
      height: 100%;
      object-fit: contain;
      pointer-events: none;
      filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3)) brightness(1.1) contrast(1.1);
      transform: scale(1.3);
    }

    .btn-movimiento {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      border: none;
      background: transparent;
      cursor: pointer;
      margin: 0;
      padding: 0;
      transition: all 0.2s ease;
    }

    .btn-movimiento:hover {
      background: rgba(76, 175, 80, 0.2);
    }

    .indicador-movimiento {
      width: 70%;
      height: 70%;
      background: rgba(76, 175, 80, 0.7);
      border-radius: 50%;
      border: 3px solid #388E3C;
      box-shadow: 0 2px 8px rgba(76, 175, 80, 0.5);
      transition: all 0.2s ease;
    }

    .btn-movimiento:hover .indicador-movimiento {
      background: #66BB6A;
      transform: scale(1.3);
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.8);
    }

    /* Indicador especial para capturas */
    .btn-captura .imagen-pieza {
      cursor: pointer;
    }

    .btn-captura::after {
      content: '';
      position: absolute;
      width: 90%;
      height: 90%;
      border: 3px solid #e74c3c;
      border-radius: 50%;
      background: rgba(231, 76, 60, 0.15);
      animation: pulse 1.5s infinite;
      pointer-events: none;
    }

    .btn-captura:hover::after {
      background: rgba(231, 76, 60, 0.3);
      border-width: 4px;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
        opacity: 1;
      }

      50% {
        transform: scale(1.1);
        opacity: 0.7;
      }
    }

    .botones-control {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .btn-reiniciar {
      font-size: 1.1em;
      font-weight: bold;
      padding: 15px 30px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      background: #e74c3c;
      color: white;
    }

    .btn-reiniciar:hover {
      background: #c0392b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }

    .instrucciones {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      border-left: 4px solid #667eea;
    }

    .instrucciones p {
      margin-bottom: 10px;
      color: #333;
    }

    .instrucciones ol {
      margin-left: 20px;
      color: #555;
    }

    .instrucciones li {
      margin-bottom: 8px;
      line-height: 1.6;
    }

    @media (max-width: 768px) {
      .container {
        padding: 20px;
      }

      h1 {
        font-size: 2em;
      }

      .tablero-contenedor {
        max-width: 100%;
      }

      .marcador-y-capturas {
        flex-direction: column;
      }
    }
  </style>
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

<<<<<<< HEAD
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
=======
    <!-- Tablero -->
    <div class="tablero-contenedor">
      <!-- Esquina superior izquierda -->
      <div class="coordenada-esquina-superior-izquierda"></div>

      <!-- Letras superiores (A-H) -->
      <?php
      $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
      foreach ($letras as $letra):
      ?>
        <div class="coordenada-superior"><?php echo $letra; ?></div>
      <?php endforeach; ?>

      <!-- Esquina superior derecha -->
      <div class="coordenada-esquina-superior-derecha"></div>

      <?php
      // Recorrer el tablero (fila 8 a fila 1)
      for ($fila = 0; $fila < 8; $fila++):
        $numeroFila = 8 - $fila;
      ?>
        <!-- Número izquierdo -->
        <div class="coordenada-izquierda"><?php echo $numeroFila; ?></div>

        <?php
        for ($col = 0; $col < 8; $col++):
          $columna = $letras[$col];
          $posicion = $columna . $numeroFila;

          // Color de la casilla
          $colorCasilla = ($fila + $col) % 2 == 0 ? 'blanca' : 'negra';

          // Obtener pieza en esta casilla
          $pieza = obtenerPiezaEnCasilla($posicion, $partida);

          // Verificar si es la casilla seleccionada
          $esSeleccionada = ($casillaSeleccionada === $posicion);

          // Si hay una casilla seleccionada, calcular movimientos posibles
          $esMovimientoPosible = false;
          if ($casillaSeleccionada !== null && !$esSeleccionada) {
            $piezaSeleccionada = obtenerPiezaEnCasilla($casillaSeleccionada, $partida);
            if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $turno) {
              // Verificar si hay una pieza en el destino
              $piezaEnDestino = obtenerPiezaEnCasilla($posicion, $partida);
              $esCaptura = ($piezaEnDestino !== null);

              // Para peones, verificar con el parámetro de captura
              if ($piezaSeleccionada instanceof Peon) {
                $movimientos = $piezaSeleccionada->simulaMovimiento($posicion, $esCaptura);
              } else {
                $movimientos = $piezaSeleccionada->simulaMovimiento($posicion);
              }

              if (!empty($movimientos)) {
                // Verificar que no hay piezas bloqueando (excepto caballo)
                $bloqueado = false;

                if (!($piezaSeleccionada instanceof Caballo)) {
                  // Revisar todas las casillas intermedias (no la final)
                  for ($i = 0; $i < count($movimientos) - 1; $i++) {
                    if (obtenerPiezaEnCasilla($movimientos[$i], $partida) !== null) {
                      $bloqueado = true;
                      break;
                    }
                  }
                }

                // Si no está bloqueado y el destino es válido
                if (!$bloqueado) {
                  // Si hay pieza en destino, debe ser enemiga
                  if ($piezaEnDestino !== null) {
                    if ($piezaEnDestino->getColor() !== $turno) {
                      $esMovimientoPosible = true;
                    }
                  } else {
                    // Casilla vacía, movimiento válido
                    $esMovimientoPosible = true;
                  }
                }
              }
            }
          }
        ?>
          <div class="casilla <?php echo $colorCasilla; ?> <?php echo $esSeleccionada ? 'seleccionada' : ''; ?>">
            <?php if ($pieza !== null): ?>
              <!-- Hay una pieza en esta casilla -->
              <form method="post" class="formulario">
                <button type="submit"
                  name="seleccionar_casilla"
                  value="<?php echo $posicion; ?>"
                  class="btn-pieza-casilla">
                  <img src="<?php echo obtenerImagenPieza($pieza); ?>"
                    alt="<?php echo get_class($pieza); ?>"
                    class="imagen-pieza">
                </button>
              </form>
            <?php elseif ($esMovimientoPosible): ?>
              <!-- Casilla vacía pero es un movimiento posible -->
              <form method="post" class="formulario">
                <button type="submit"
                  name="seleccionar_casilla"
                  value="<?php echo $posicion; ?>"
                  class="btn-movimiento">
                  <span class="indicador-movimiento"></span>
                </button>
              </form>
>>>>>>> d8fd5f50e5d568bdc48788ea3d115d5b7d4f426a
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