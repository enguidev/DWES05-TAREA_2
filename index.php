<?php
session_start();

require_once 'modelo/Partida.php';

// ============================================
// FUNCIONES AUXILIARES
// ============================================

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

// ============================================
// FIN DE FUNCIONES AUXILIARES
// ============================================

// Si es una petición AJAX para actualizar relojes
if (isset($_GET['ajax']) && $_GET['ajax'] === 'update_clocks') {
  if (isset($_SESSION['tiempo_blancas']) && isset($_SESSION['tiempo_negras']) && isset($_SESSION['reloj_activo'])) {
    $ahora = time();

    // Solo actualizar si no está en pausa
    if (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) {
      if (isset($_SESSION['ultimo_tick'])) {
        $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

        if ($tiempoTranscurrido > 0) {
          if ($_SESSION['reloj_activo'] === 'blancas') {
            $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);
          } else {
            $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
          }
          $_SESSION['ultimo_tick'] = $ahora;
        }
      } else {
        $_SESSION['ultimo_tick'] = $ahora;
      }
    }

    header('Content-Type: application/json');
    echo json_encode([
      'tiempo_blancas' => $_SESSION['tiempo_blancas'],
      'tiempo_negras' => $_SESSION['tiempo_negras'],
      'reloj_activo' => $_SESSION['reloj_activo'],
      'pausa' => isset($_SESSION['pausa']) ? $_SESSION['pausa'] : false
    ]);
  }
  exit;
}

// Procesar configuración (solo opciones visuales)
if (isset($_POST['guardar_configuracion'])) {
  // Solo permitir cambiar opciones visuales, no el tiempo
  $_SESSION['config']['mostrar_coordenadas'] = isset($_POST['mostrar_coordenadas']);
  $_SESSION['config']['mostrar_capturas'] = isset($_POST['mostrar_capturas']);
}
$configDefecto = [
  'tiempo_inicial' => 600,
  'incremento' => 0,
  'mostrar_coordenadas' => true,
  'mostrar_capturas' => true
];

if (!isset($_SESSION['config'])) {
  $_SESSION['config'] = $configDefecto;
}

// Iniciar partida con nombres Y configuración
if (isset($_POST['iniciar_partida'])) {
  $nombreBlancas = !empty($_POST['nombre_blancas']) ? htmlspecialchars(trim($_POST['nombre_blancas'])) : "Jugador 1";
  $nombreNegras = !empty($_POST['nombre_negras']) ? htmlspecialchars(trim($_POST['nombre_negras'])) : "Jugador 2";

  // Guardar configuración elegida
  $_SESSION['config'] = [
    'tiempo_inicial' => (int)$_POST['tiempo_inicial'],
    'incremento' => (int)$_POST['incremento'],
    'mostrar_coordenadas' => isset($_POST['mostrar_coordenadas']),
    'mostrar_capturas' => isset($_POST['mostrar_capturas'])
  ];

  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  $_SESSION['casilla_seleccionada'] = null;
  $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['reloj_activo'] = 'blancas';
  $_SESSION['ultimo_tick'] = time();
  $_SESSION['nombres_configurados'] = true;
  $_SESSION['pausa'] = false;
}

// Pausar/Reanudar
if (isset($_POST['toggle_pausa'])) {
  if (isset($_SESSION['pausa'])) {
    $_SESSION['pausa'] = !$_SESSION['pausa'];

    // Si se reanuda, resetear ultimo_tick
    if (!$_SESSION['pausa']) {
      $_SESSION['ultimo_tick'] = time();
    }
  }
}

// Reiniciar
if (isset($_POST['reiniciar'])) {
  unset($_SESSION['partida']);
  unset($_SESSION['casilla_seleccionada']);
  unset($_SESSION['tiempo_blancas']);
  unset($_SESSION['tiempo_negras']);
  unset($_SESSION['reloj_activo']);
  unset($_SESSION['ultimo_tick']);
  unset($_SESSION['nombres_configurados']);
  unset($_SESSION['pausa']);
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Solo si hay partida
if (isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);

  // Procesar jugada (solo si no está en pausa)
  if (isset($_POST['seleccionar_casilla']) && (!isset($_SESSION['pausa']) || !$_SESSION['pausa'])) {
    $casilla = $_POST['seleccionar_casilla'];

    if ($_SESSION['casilla_seleccionada'] === null) {
      $piezaSeleccionada = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $partida->getTurno()) {
        $_SESSION['casilla_seleccionada'] = $casilla;
      }
    } else {
      $piezaClickeada = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaClickeada && $piezaClickeada->getColor() === $partida->getTurno()) {
        $_SESSION['casilla_seleccionada'] = $casilla;
      } else {
        $origen = $_SESSION['casilla_seleccionada'];
        $destino = $casilla;

        $exito = $partida->jugada($origen, $destino);

        if ($exito) {
          // Actualizar el tiempo antes de cambiar de turno
          $ahora = time();
          $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

          $turnoAnterior = $_SESSION['reloj_activo'];

          // Restar el tiempo transcurrido al jugador que acaba de mover
          if ($turnoAnterior === 'blancas') {
            $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);
          } else {
            $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
          }

          // Incremento Fischer (después de restar el tiempo transcurrido)
          if ($_SESSION['config']['incremento'] > 0) {
            if ($turnoAnterior === 'blancas') {
              $_SESSION['tiempo_blancas'] += $_SESSION['config']['incremento'];
            } else {
              $_SESSION['tiempo_negras'] += $_SESSION['config']['incremento'];
            }
          }

          // Cambiar de turno y resetear el tick
          $_SESSION['reloj_activo'] = ($turnoAnterior === 'blancas') ? 'negras' : 'blancas';
          $_SESSION['ultimo_tick'] = time();
        }

        $_SESSION['casilla_seleccionada'] = null;
        $_SESSION['partida'] = serialize($partida);
      }
    }
  }

  // Deshacer
  if (isset($_POST['deshacer'])) {
    $partida->deshacerJugada();
    $_SESSION['casilla_seleccionada'] = null;
    $_SESSION['partida'] = serialize($partida);
  }

  // Guardar
  if (isset($_POST['guardar'])) {
    $data = [
      'partida' => serialize($partida),
      'casilla_seleccionada' => $_SESSION['casilla_seleccionada'],
      'tiempo_blancas' => $_SESSION['tiempo_blancas'],
      'tiempo_negras' => $_SESSION['tiempo_negras'],
      'reloj_activo' => $_SESSION['reloj_activo'],
      'config' => $_SESSION['config'],
      'pausa' => $_SESSION['pausa']
    ];
    file_put_contents('partida_guardada.json', json_encode($data));
  }

  // Cargar
  if (isset($_POST['cargar'])) {
    if (file_exists('partida_guardada.json')) {
      $data = json_decode(file_get_contents('partida_guardada.json'), true);
      $_SESSION['partida'] = $data['partida'];
      $_SESSION['casilla_seleccionada'] = $data['casilla_seleccionada'];
      $_SESSION['tiempo_blancas'] = $data['tiempo_blancas'];
      $_SESSION['tiempo_negras'] = $data['tiempo_negras'];
      $_SESSION['reloj_activo'] = $data['reloj_activo'];
      $_SESSION['ultimo_tick'] = time();
      if (isset($data['config'])) {
        $_SESSION['config'] = $data['config'];
      }
      if (isset($data['pausa'])) {
        $_SESSION['pausa'] = $data['pausa'];
      } else {
        $_SESSION['pausa'] = false;
      }
      $partida = unserialize($_SESSION['partida']);
    }
  }

  $casillaSeleccionada = $_SESSION['casilla_seleccionada'];
  $marcador = $partida->marcador();
  $mensaje = $partida->getMensaje();
  $turno = $partida->getTurno();
  $jugadores = $partida->getJugadores();

  $piezasCapturadas = ['blancas' => [], 'negras' => []];

  foreach ($jugadores['blancas']->getPiezas() as $pieza) {
    if ($pieza->estCapturada()) $piezasCapturadas['blancas'][] = $pieza;
  }

  foreach ($jugadores['negras']->getPiezas() as $pieza) {
    if ($pieza->estCapturada()) $piezasCapturadas['negras'][] = $pieza;
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partida de Ajedrez</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <?php if (!isset($_SESSION['nombres_configurados'])): ?>
    <div class="container">
      <h1>🎮 Configurar Partida</h1>
      <div class="config-wrapper">
        <form method="post" class="config-form">
          <p class="config-intro"><strong>📋 Paso 1: Nombres de los jugadores</strong></p>

          <div class="jugador-config blancas-config">
            <div class="config-icon">♔</div>
            <label><strong>⚪ Jugador Blancas:</strong></label>
            <input type="text" name="nombre_blancas" placeholder="María, Juan..." maxlength="20" class="input-nombre" autofocus>
            <small>Las blancas empiezan primero</small>
          </div>

          <div class="vs-separator">VS</div>

          <div class="jugador-config negras-config">
            <div class="config-icon">♚</div>
            <label><strong>⚫ Jugador Negras:</strong></label>
            <input type="text" name="nombre_negras" placeholder="Pedro, Ana..." maxlength="20" class="input-nombre">
            <small>Las negras juegan segundo</small>
          </div>

          <hr style="margin: 30px 0; border: none; border-top: 2px solid #e0e0e0;">

          <p class="config-intro"><strong>⏱️ Paso 2: Configuración del tiempo</strong></p>

          <div class="config-section-inicio">
            <div class="config-option">
              <label>Tiempo inicial por jugador:</label>
              <select name="tiempo_inicial" class="select-tiempo">
                <option value="60">1 minuto (Bullet)</option>
                <option value="180">3 minutos (Blitz)</option>
                <option value="300">5 minutos (Blitz)</option>
                <option value="600" selected>10 minutos (Rápidas)</option>
                <option value="900">15 minutos (Rápidas)</option>
                <option value="1800">30 minutos (Clásicas)</option>
                <option value="3600">60 minutos (Clásicas)</option>
              </select>
            </div>
            <div class="config-option">
              <label>Incremento Fischer:</label>
              <select name="incremento" class="select-tiempo">
                <option value="0" selected>Sin incremento</option>
                <option value="1">+1 segundo</option>
                <option value="2">+2 segundos</option>
                <option value="3">+3 segundos</option>
                <option value="5">+5 segundos</option>
                <option value="10">+10 segundos</option>
              </select>
              <small style="display: block; margin-top: 5px;">Al mover, recuperas tiempo adicional</small>
            </div>
          </div>

          <hr style="margin: 30px 0; border: none; border-top: 2px solid #e0e0e0;">

          <p class="config-intro"><strong>🎨 Paso 3: Opciones de interfaz</strong></p>

          <div class="config-section-inicio">
            <div class="config-option checkbox">
              <label><input type="checkbox" name="mostrar_coordenadas" checked> Mostrar coordenadas (A-H, 1-8)</label>
            </div>
            <div class="config-option checkbox">
              <label><input type="checkbox" name="mostrar_capturas" checked> Mostrar piezas capturadas</label>
            </div>
          </div>

          <button type="submit" name="iniciar_partida" class="btn-iniciar-partida">🎯 Iniciar Partida</button>
          <p class="config-nota">💡 <em>Campos vacíos usarán nombres por defecto</em></p>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="container">
      <!-- MODAL CONFIGURACIÓN -->
      <div id="modalConfig" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>⚙️ Configuración</h2>
            <span class="close-modal">&times;</span>
          </div>
          <form method="post" class="modal-form">
            <div class="config-section">
              <h3>🎨 Opciones de Interfaz</h3>
              <p style="color: #666; margin-bottom: 15px;">Puedes mostrar u ocultar elementos visuales</p>
              <div class="config-option checkbox">
                <label><input type="checkbox" name="mostrar_coordenadas" <?php echo $_SESSION['config']['mostrar_coordenadas'] ? 'checked' : ''; ?>> Coordenadas del tablero (A-H, 1-8)</label>
              </div>
              <div class="config-option checkbox">
                <label><input type="checkbox" name="mostrar_capturas" <?php echo $_SESSION['config']['mostrar_capturas'] ? 'checked' : ''; ?>> Panel de piezas capturadas</label>
              </div>
            </div>
            <div class="config-info">
              <h3>⏱️ Información del Tiempo</h3>
              <p><strong>Tiempo inicial:</strong> <?php
                                                  $mins = $_SESSION['config']['tiempo_inicial'] / 60;
                                                  echo $mins . ' minuto' . ($mins != 1 ? 's' : '');
                                                  ?></p>
              <p><strong>Incremento Fischer:</strong> <?php
                                                      echo $_SESSION['config']['incremento'] > 0
                                                        ? '+' . $_SESSION['config']['incremento'] . ' segundo' . ($_SESSION['config']['incremento'] != 1 ? 's' : '')
                                                        : 'Sin incremento';
                                                      ?></p>
              <small style="color: #999; display: block; margin-top: 10px;">
                ℹ️ El tiempo y el incremento no se pueden cambiar durante la partida
              </small>
            </div>
            <div class="modal-buttons">
              <button type="submit" name="guardar_configuracion" class="btn-guardar-config">💾 Guardar Cambios</button>
              <button type="button" class="btn-cancelar-config">❌ Cancelar</button>
            </div>
          </form>
        </div>
      </div>

      <div class="header-juego">
        <h1>♟️ Partida de Ajedrez</h1>
        <div class="header-buttons">
          <button id="btnConfig" class="btn-config">⚙️</button>
          <form method="post" style="display: inline;">
            <button type="submit" name="toggle_pausa" class="btn-pausa" id="btnPausa">
              <?php echo (isset($_SESSION['pausa']) && $_SESSION['pausa']) ? '▶️ Reanudar' : '⏸️ Pausar'; ?>
            </button>
          </form>
        </div>
      </div>

      <div class="mensaje <?php echo $partida->estaTerminada() ? 'terminada' : ''; ?>">
        <?php
        if (isset($_SESSION['pausa']) && $_SESSION['pausa']) {
          echo "⏸️ PARTIDA EN PAUSA";
        } else {
          echo htmlspecialchars($mensaje);
        }
        ?>
      </div>

      <!-- RELOJES -->
      <div class="relojes-container">
        <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'blancas' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-blancas">
          <div class="reloj-jugador">⚪ <?php echo $jugadores['blancas']->getNombre(); ?></div>
          <div id="tiempo-blancas" class="reloj-tiempo <?php echo $_SESSION['tiempo_blancas'] < 60 ? 'tiempo-critico' : ''; ?>">
            <?php echo formatearTiempo($_SESSION['tiempo_blancas']); ?>
          </div>
          <div class="reloj-puntos"><?php echo $marcador[0]; ?> pts</div>
        </div>
        <div class="reloj-separador">⏱️</div>
        <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'negras' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-negras">
          <div class="reloj-jugador">⚫ <?php echo $jugadores['negras']->getNombre(); ?></div>
          <div id="tiempo-negras" class="reloj-tiempo <?php echo $_SESSION['tiempo_negras'] < 60 ? 'tiempo-critico' : ''; ?>">
            <?php echo formatearTiempo($_SESSION['tiempo_negras']); ?>
          </div>
          <div class="reloj-puntos"><?php echo $marcador[1]; ?> pts</div>
        </div>
      </div>

      <!-- TABLERO -->
      <?php if ($_SESSION['config']['mostrar_capturas']): ?>
        <div class="tablero-y-capturas-wrapper">
          <div class="piezas-capturadas-lado">
            <h3>Cap. negras:</h3>
            <div class="capturadas-vertical">
              <?php foreach ($piezasCapturadas['blancas'] as $pieza): ?>
                <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="pieza-capturada">
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="tablero-solo-wrapper">
          <?php endif; ?>

          <div class="tablero-wrapper">
            <div class="tablero-contenedor">
              <?php
              $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

              if ($_SESSION['config']['mostrar_coordenadas']) {
                echo '<div class="coordenada-esquina-superior-izquierda"></div>';
                foreach ($letras as $letra) echo '<div class="coordenada-superior">' . $letra . '</div>';
                echo '<div class="coordenada-esquina-superior-derecha"></div>';
              }

              for ($fila = 8; $fila >= 1; $fila--):
                if ($_SESSION['config']['mostrar_coordenadas']) {
                  echo '<div class="coordenada-izquierda">' . $fila . '</div>';
                }

                for ($columna = 0; $columna < 8; $columna++):
                  $posicion = $letras[$columna] . $fila;
                  $pieza = obtenerPiezaEnCasilla($posicion, $partida);
                  $colorCasilla = (($fila + $columna) % 2 === 0) ? 'blanca' : 'negra';
                  $esSeleccionada = ($casillaSeleccionada === $posicion);

                  $esMovimientoPosible = false;
                  $esCaptura = false;

                  // Solo mostrar movimientos si no está en pausa
                  if ($casillaSeleccionada !== null && !$esSeleccionada && (!isset($_SESSION['pausa']) || !$_SESSION['pausa'])) {
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
                        <button type="submit" name="seleccionar_casilla" value="<?php echo $posicion; ?>"
                          class="btn-pieza-casilla <?php echo ($pieza->getColor() === $turno) ? 'puede-seleccionar' : 'no-puede-seleccionar'; ?> <?php echo $esCaptura ? 'btn-captura' : ''; ?>"
                          <?php echo (isset($_SESSION['pausa']) && $_SESSION['pausa']) ? 'disabled' : ''; ?>>
                          <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="imagen-pieza">
                        </button>
                      </form>
                    <?php elseif ($esMovimientoPosible): ?>
                      <form method="post" class="formulario">
                        <button type="submit" name="seleccionar_casilla" value="<?php echo $posicion; ?>" class="btn-movimiento">
                          <span class="indicador-movimiento"></span>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endfor; ?>

                <?php if ($_SESSION['config']['mostrar_coordenadas']): ?>
                  <div class="coordenada-derecha"><?php echo $fila; ?></div>
                <?php endif; ?>
              <?php endfor; ?>

              <?php if ($_SESSION['config']['mostrar_coordenadas']): ?>
                <div class="coordenada-esquina-inferior-izquierda"></div>
                <?php foreach ($letras as $letra): ?>
                  <div class="coordenada-inferior"><?php echo $letra; ?></div>
                <?php endforeach; ?>
                <div class="coordenada-esquina-inferior-derecha"></div>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($_SESSION['config']['mostrar_capturas']): ?>
            <div class="piezas-capturadas-lado">
              <h3>Cap. blancas:</h3>
              <div class="capturadas-vertical">
                <?php foreach ($piezasCapturadas['negras'] as $pieza): ?>
                  <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="pieza-capturada">
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          </div>

          <div class="botones-control">
            <form method="post" style="display: inline;">
              <button type="submit" name="deshacer" class="btn-deshacer">↶ Deshacer</button>
            </form>
            <form method="post" style="display: inline;">
              <button type="submit" name="guardar" class="btn-guardar">💾 Guardar</button>
            </form>
            <form method="post" style="display: inline;">
              <button type="submit" name="cargar" class="btn-cargar">📁 Cargar</button>
            </form>
            <form method="post" style="display: inline;">
              <button type="submit" name="reiniciar" class="btn-reiniciar">🔄 Nueva Partida</button>
            </form>
          </div>

          <div class="instrucciones">
            <p><strong>🎮 Cómo jugar:</strong></p>
            <ol>
              <li>⏸️ <strong>Pausa/Reanudar</strong>: Usa el botón superior para pausar</li>
              <li>⏱️ Solo corre el reloj del jugador en turno</li>
              <li>🟢 Círculos verdes = movimientos posibles</li>
              <li>🔴 Borde rojo pulsante = capturas posibles</li>
              <li>⏰ Si llegas a 0:00, pierdes automáticamente</li>
              <li>💾 Puedes guardar la partida y continuarla después</li>
            </ol>
          </div>
        </div>

        <script>
          // Modal de configuración
          const modal = document.getElementById('modalConfig');
          const btnConfig = document.getElementById('btnConfig');
          const closeModal = document.querySelector('.close-modal');
          const btnCancelar = document.querySelector('.btn-cancelar-config');

          btnConfig.onclick = () => modal.style.display = 'block';
          closeModal.onclick = () => modal.style.display = 'none';
          btnCancelar.onclick = () => modal.style.display = 'none';
          window.onclick = (e) => {
            if (e.target == modal) modal.style.display = 'none';
          }

          // Actualizar relojes con AJAX
          function actualizarRelojes() {
            fetch('?ajax=update_clocks')
              .then(r => r.json())
              .then(data => {
                const fmt = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                document.getElementById('tiempo-blancas').textContent = fmt(data.tiempo_blancas);
                document.getElementById('tiempo-negras').textContent = fmt(data.tiempo_negras);

                const tb = document.getElementById('tiempo-blancas');
                const tn = document.getElementById('tiempo-negras');
                data.tiempo_blancas < 60 ? tb.classList.add('tiempo-critico') : tb.classList.remove('tiempo-critico');
                data.tiempo_negras < 60 ? tn.classList.add('tiempo-critico') : tn.classList.remove('tiempo-critico');

                document.querySelectorAll('.reloj').forEach(r => {
                  if (r.classList.contains('reloj-blancas')) {
                    data.reloj_activo === 'blancas' ? (r.classList.add('reloj-activo'), r.classList.remove('reloj-inactivo')) : (r.classList.remove('reloj-activo'), r.classList.add('reloj-inactivo'));
                  } else if (r.classList.contains('reloj-negras')) {
                    data.reloj_activo === 'negras' ? (r.classList.add('reloj-activo'), r.classList.remove('reloj-inactivo')) : (r.classList.remove('reloj-activo'), r.classList.add('reloj-inactivo'));
                  }
                });

                if (data.tiempo_blancas <= 0 || data.tiempo_negras <= 0) {
                  alert('¡Tiempo agotado para ' + (data.tiempo_blancas <= 0 ? 'blancas' : 'negras') + '!');
                  location.reload();
                }
              })
              .catch(e => console.error('Error:', e));
          }

          setInterval(actualizarRelojes, 1000);
        </script>
      <?php endif; ?>
</body>

</html>