<?php
session_start();

require_once 'modelo/Partida.php';

// Configuración por defecto
$configDefecto = [
  'tiempo_inicial' => 600,  // 10 minutos
  'incremento' => 0,        // Segundos adicionales por jugada (Fischer)
  'mostrar_coordenadas' => true,
  'mostrar_capturas' => true,
  'sonido_movimiento' => true,
  'confirmar_movimiento' => false
];

// Inicializar configuración si no existe
if (!isset($_SESSION['config'])) {
  $_SESSION['config'] = $configDefecto;
}

// Procesar actualización de configuración
if (isset($_POST['guardar_configuracion'])) {
  $_SESSION['config'] = [
    'tiempo_inicial' => (int)$_POST['tiempo_inicial'],
    'incremento' => (int)$_POST['incremento'],
    'mostrar_coordenadas' => isset($_POST['mostrar_coordenadas']),
    'mostrar_capturas' => isset($_POST['mostrar_capturas']),
    'sonido_movimiento' => isset($_POST['sonido_movimiento']),
    'confirmar_movimiento' => isset($_POST['confirmar_movimiento'])
  ];

  // Reiniciar temporizadores con nuevo tiempo
  if (isset($_SESSION['partida'])) {
    $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
    $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  }
}

// Procesar configuración de nombres de jugadores
if (isset($_POST['iniciar_partida'])) {
  $nombreBlancas = !empty($_POST['nombre_blancas']) ? htmlspecialchars(trim($_POST['nombre_blancas'])) : "Jugador 1";
  $nombreNegras = !empty($_POST['nombre_negras']) ? htmlspecialchars(trim($_POST['nombre_negras'])) : "Jugador 2";

  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  $_SESSION['casilla_seleccionada'] = null;
  $_SESSION['tiempo_blancas'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['tiempo_negras'] = $_SESSION['config']['tiempo_inicial'];
  $_SESSION['reloj_activo'] = 'blancas';
  $_SESSION['ultimo_tick'] = time();
  $_SESSION['nombres_configurados'] = true;
}

// Inicializar o recuperar la partida
if (!isset($_SESSION['partida']) || isset($_POST['reiniciar'])) {
  if (isset($_POST['reiniciar'])) {
    unset($_SESSION['partida']);
    unset($_SESSION['casilla_seleccionada']);
    unset($_SESSION['tiempo_blancas']);
    unset($_SESSION['tiempo_negras']);
    unset($_SESSION['reloj_activo']);
    unset($_SESSION['ultimo_tick']);
    unset($_SESSION['nombres_configurados']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }
}

// Actualizar tiempo del reloj activo
if (isset($_SESSION['partida']) && isset($_SESSION['reloj_activo'])) {
  $ahora = time();
  $tiempoTranscurrido = $ahora - $_SESSION['ultimo_tick'];

  if ($_SESSION['reloj_activo'] === 'blancas') {
    $_SESSION['tiempo_blancas'] = max(0, $_SESSION['tiempo_blancas'] - $tiempoTranscurrido);
  } else {
    $_SESSION['tiempo_negras'] = max(0, $_SESSION['tiempo_negras'] - $tiempoTranscurrido);
  }

  $_SESSION['ultimo_tick'] = $ahora;

  // Verificar tiempo agotado
  if ($_SESSION['tiempo_blancas'] <= 0 || $_SESSION['tiempo_negras'] <= 0) {
    $perdedor = $_SESSION['tiempo_blancas'] <= 0 ? 'blancas' : 'negras';
    $_SESSION['mensaje_tiempo'] = "¡Tiempo agotado para " . $perdedor . "!";
  }
}

// Solo continuar si hay una partida iniciada
if (isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);

  // Procesar jugada
  if (isset($_POST['seleccionar_casilla'])) {
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
          // Cambiar reloj al otro jugador
          $turnoAnterior = ($_SESSION['reloj_activo'] === 'blancas') ? 'blancas' : 'negras';

          // Agregar incremento Fischer si está configurado
          if ($_SESSION['config']['incremento'] > 0) {
            if ($turnoAnterior === 'blancas') {
              $_SESSION['tiempo_blancas'] += $_SESSION['config']['incremento'];
            } else {
              $_SESSION['tiempo_negras'] += $_SESSION['config']['incremento'];
            }
          }

          // Cambiar reloj activo
          $_SESSION['reloj_activo'] = ($turnoAnterior === 'blancas') ? 'negras' : 'blancas';
          $_SESSION['ultimo_tick'] = time();
        }

        $_SESSION['casilla_seleccionada'] = null;
        $_SESSION['partida'] = serialize($partida);
      }
    }
  }

  // Procesar deshacer jugada
  if (isset($_POST['deshacer'])) {
    $partida->deshacerJugada();
    $_SESSION['casilla_seleccionada'] = null;
    $_SESSION['partida'] = serialize($partida);
  }

  // Procesar guardar partida
  if (isset($_POST['guardar'])) {
    $data = [
      'partida' => serialize($partida),
      'casilla_seleccionada' => $_SESSION['casilla_seleccionada'],
      'tiempo_blancas' => $_SESSION['tiempo_blancas'],
      'tiempo_negras' => $_SESSION['tiempo_negras'],
      'reloj_activo' => $_SESSION['reloj_activo'],
      'config' => $_SESSION['config']
    ];
    file_put_contents('partida_guardada.json', json_encode($data));
    $mensaje = "Partida guardada.";
  }

  // Procesar cargar partida
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
      $partida = unserialize($_SESSION['partida']);
      $mensaje = "Partida cargada.";
    } else {
      $mensaje = "No hay partida guardada.";
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

  function obtenerImagenPieza($pieza)
  {
    if ($pieza === null) return '';

    $color = $pieza->getColor();
    $carpeta = ($color === 'blancas') ? 'imagenes/fichas_blancas' : 'imagenes/fichas_negras';
    $colorNombre = ($color === 'blancas') ? 'blanca' : 'negra';

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

  function obtenerPiezaEnCasilla($posicion, $partida)
  {
    $jugadores = $partida->getJugadores();

    $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion);
    if ($pieza) return $pieza;

    $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion);
    if ($pieza) return $pieza;

    return null;
  }

  function formatearTiempo($segundos)
  {
    $minutos = floor($segundos / 60);
    $segs = $segundos % 60;
    return sprintf("%02d:%02d", $minutos, $segs);
  }
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
  <?php if (!isset($_SESSION['nombres_configurados'])): ?>
    <!-- PANTALLA DE CONFIGURACIÓN INICIAL -->
    <div class="container">
      <h1>🎮 Configurar Partida de Ajedrez</h1>

      <div class="config-wrapper">
        <p class="config-intro">Configura tu partida antes de comenzar:</p>

        <form method="post" class="config-form">
          <div class="jugador-config blancas-config">
            <div class="config-icon">♔</div>
            <label for="nombre_blancas">
              <strong>⚪ Jugador Blancas:</strong>
            </label>
            <input
              type="text"
              id="nombre_blancas"
              name="nombre_blancas"
              placeholder="Ejemplo: María, Juan, etc."
              maxlength="20"
              class="input-nombre"
              autofocus>
            <small>Las blancas siempre empiezan primero</small>
          </div>

          <div class="vs-separator">VS</div>

          <div class="jugador-config negras-config">
            <div class="config-icon">♚</div>
            <label for="nombre_negras">
              <strong>⚫ Jugador Negras:</strong>
            </label>
            <input
              type="text"
              id="nombre_negras"
              name="nombre_negras"
              placeholder="Ejemplo: Pedro, Ana, etc."
              maxlength="20"
              class="input-nombre">
            <small>Las negras juegan segundo</small>
          </div>

          <button type="submit" name="iniciar_partida" class="btn-iniciar-partida">
            🎯 Iniciar Partida
          </button>

          <p class="config-nota">
            💡 <em>Si dejas los campos vacíos, se usarán "Jugador 1" y "Jugador 2" por defecto.</em>
          </p>
        </form>
      </div>
    </div>
  <?php else: ?>
    <!-- MODAL DE CONFIGURACIÓN -->
    <div id="modalConfig" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>⚙️ Configuración de Partida</h2>
          <span class="close-modal">&times;</span>
        </div>
        <form method="post" class="modal-form">
          <div class="config-section">
            <h3>⏱️ Temporizadores</h3>
            <div class="config-option">
              <label for="tiempo_inicial">Tiempo inicial por jugador:</label>
              <select name="tiempo_inicial" id="tiempo_inicial">
                <option value="60" <?php echo $_SESSION['config']['tiempo_inicial'] == 60 ? 'selected' : ''; ?>>1 minuto (Bullet)</option>
                <option value="180" <?php echo $_SESSION['config']['tiempo_inicial'] == 180 ? 'selected' : ''; ?>>3 minutos (Blitz)</option>
                <option value="300" <?php echo $_SESSION['config']['tiempo_inicial'] == 300 ? 'selected' : ''; ?>>5 minutos (Blitz)</option>
                <option value="600" <?php echo $_SESSION['config']['tiempo_inicial'] == 600 ? 'selected' : ''; ?>>10 minutos (Rápidas)</option>
                <option value="900" <?php echo $_SESSION['config']['tiempo_inicial'] == 900 ? 'selected' : ''; ?>>15 minutos (Rápidas)</option>
                <option value="1800" <?php echo $_SESSION['config']['tiempo_inicial'] == 1800 ? 'selected' : ''; ?>>30 minutos (Clásicas)</option>
              </select>
            </div>
            <div class="config-option">
              <label for="incremento">Incremento por jugada (Fischer):</label>
              <select name="incremento" id="incremento">
                <option value="0" <?php echo $_SESSION['config']['incremento'] == 0 ? 'selected' : ''; ?>>Sin incremento</option>
                <option value="1" <?php echo $_SESSION['config']['incremento'] == 1 ? 'selected' : ''; ?>>+1 segundo</option>
                <option value="2" <?php echo $_SESSION['config']['incremento'] == 2 ? 'selected' : ''; ?>>+2 segundos</option>
                <option value="3" <?php echo $_SESSION['config']['incremento'] == 3 ? 'selected' : ''; ?>>+3 segundos</option>
                <option value="5" <?php echo $_SESSION['config']['incremento'] == 5 ? 'selected' : ''; ?>>+5 segundos</option>
                <option value="10" <?php echo $_SESSION['config']['incremento'] == 10 ? 'selected' : ''; ?>>+10 segundos</option>
              </select>
            </div>
          </div>

          <div class="config-section">
            <h3>🎨 Interfaz</h3>
            <div class="config-option checkbox">
              <label>
                <input type="checkbox" name="mostrar_coordenadas" <?php echo $_SESSION['config']['mostrar_coordenadas'] ? 'checked' : ''; ?>>
                Mostrar coordenadas del tablero (A-H, 1-8)
              </label>
            </div>
            <div class="config-option checkbox">
              <label>
                <input type="checkbox" name="mostrar_capturas" <?php echo $_SESSION['config']['mostrar_capturas'] ? 'checked' : ''; ?>>
                Mostrar piezas capturadas
              </label>
            </div>
          </div>

          <div class="config-section">
            <h3>🔊 Sonido y Confirmación</h3>
            <div class="config-option checkbox">
              <label>
                <input type="checkbox" name="sonido_movimiento" <?php echo $_SESSION['config']['sonido_movimiento'] ? 'checked' : ''; ?>>
                Sonido al mover pieza
              </label>
            </div>
            <div class="config-option checkbox">
              <label>
                <input type="checkbox" name="confirmar_movimiento" <?php echo $_SESSION['config']['confirmar_movimiento'] ? 'checked' : ''; ?>>
                Pedir confirmación antes de mover
              </label>
            </div>
          </div>

          <div class="modal-buttons">
            <button type="submit" name="guardar_configuracion" class="btn-guardar-config">💾 Guardar Configuración</button>
            <button type="button" class="btn-cancelar-config">❌ Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- PANTALLA DE JUEGO -->
    <div class="container">
      <div class="header-juego">
        <h1>Partida de Ajedrez</h1>
        <button id="btnConfig" class="btn-config" title="Configuración">⚙️</button>
      </div>

      <?php if (isset($_SESSION['mensaje_tiempo'])): ?>
        <div class="mensaje terminada">
          <?php echo htmlspecialchars($_SESSION['mensaje_tiempo']); ?>
        </div>
      <?php else: ?>
        <div class="mensaje <?php echo $partida->estaTerminada() ? 'terminada' : ''; ?>">
          <?php echo htmlspecialchars($mensaje); ?>
        </div>
      <?php endif; ?>

      <!-- RELOJES DE AJEDREZ -->
      <div class="relojes-container">
        <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'blancas' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-blancas">
          <div class="reloj-jugador">⚪ <?php echo $jugadores['blancas']->getNombre(); ?></div>
          <div class="reloj-tiempo <?php echo $_SESSION['tiempo_blancas'] < 60 ? 'tiempo-critico' : ''; ?>">
            <?php echo formatearTiempo($_SESSION['tiempo_blancas']); ?>
          </div>
          <div class="reloj-puntos"><?php echo $marcador[0]; ?> puntos</div>
        </div>

        <div class="reloj-separador">⏱️</div>

        <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'negras' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-negras">
          <div class="reloj-jugador">⚫ <?php echo $jugadores['negras']->getNombre(); ?></div>
          <div class="reloj-tiempo <?php echo $_SESSION['tiempo_negras'] < 60 ? 'tiempo-critico' : ''; ?>">
            <?php echo formatearTiempo($_SESSION['tiempo_negras']); ?>
          </div>
          <div class="reloj-puntos"><?php echo $marcador[1]; ?> puntos</div>
        </div>
      </div>

      <!-- TABLERO CON PIEZAS CAPTURADAS -->
      <?php if ($_SESSION['config']['mostrar_capturas']): ?>
        <div class="tablero-y-capturas-wrapper">
          <div class="piezas-capturadas-lado">
            <h3>Capturadas por negras:</h3>
            <div class="capturadas-vertical">
              <?php foreach ($piezasCapturadas['blancas'] as $pieza): ?>
                <img src="<?php echo obtenerImagenPieza($pieza); ?>" alt="Pieza capturada" class="pieza-capturada">
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
                foreach ($letras as $letra) {
                  echo '<div class="coordenada-superior">' . $letra . '</div>';
                }
                echo '<div class="coordenada-esquina-superior-derecha"></div>';
              }

              for ($fila = 8; $fila >= 1; $fila--):
                $numeroFila = $fila;
                if ($_SESSION['config']['mostrar_coordenadas']) {
                  echo '<div class="coordenada-izquierda">' . $numeroFila . '</div>';
                }

                for ($columna = 0; $columna < 8; $columna++):
                  $posicion = $letras[$columna] . $fila;
                  $pieza = obtenerPiezaEnCasilla($posicion, $partida);
                  $colorCasilla = (($fila + $columna) % 2 === 0) ? 'blanca' : 'negra';
                  $esSeleccionada = ($casillaSeleccionada === $posicion);

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

                <?php if ($_SESSION['config']['mostrar_coordenadas']): ?>
                  <div class="coordenada-derecha"><?php echo $numeroFila; ?></div>
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
              <h3>Capturadas por blancas:</h3>
              <div class="capturadas-vertical">
                <?php foreach ($piezasCapturadas['negras'] as $pieza): ?>
                  <img src="<?php echo obtenerImagenPieza($pieza); ?>" alt="Pieza capturada" class="pieza-capturada">
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          </div>

          <div class="botones-control">
            <form method="post" style="display: inline;">
              <button type="submit" name="reiniciar" class="btn-reiniciar">
                🔄 Reiniciar Partida
              </button>
            </form>
            <form method="post" style="display: inline;">
              <button type="submit" name="deshacer" class="btn-deshacer">
                ↶ Deshacer Jugada
              </button>
            </form>
            <form method="post" style="display: inline;">
              <button type="submit" name="guardar" class="btn-guardar">
                💾 Guardar Partida
              </button>
            </form>
            <form method="post" style="display: inline;">
              <button type="submit" name="cargar" class="btn-cargar">
                📁 Cargar Partida
              </button>
            </form>
          </div>

          <div class="instrucciones">
            <p><strong>🎮 Cómo jugar:</strong></p>
            <ol>
              <li>El reloj del jugador activo corre automáticamente</li>
              <li>Al mover una pieza, el reloj cambia al oponente</li>
              <li>Los círculos verdes indican movimientos posibles</li>
              <li>El borde rojo indica piezas capturables</li>
              <li>Si tu tiempo llega a 0, pierdes la partida</li>
            </ol>
          </div>
        </div>

        <script>
          // Modal de configuración
          const modal = document.getElementById('modalConfig');
          const btnConfig = document.getElementById('btnConfig');
          const closeModal = document.querySelector('.close-modal');
          const btnCancelar = document.querySelector('.btn-cancelar-config');

          btnConfig.onclick = function() {
            modal.style.display = 'block';
          }

          closeModal.onclick = function() {
            modal.style.display = 'none';
          }

          btnCancelar.onclick = function() {
            modal.style.display = 'none';
          }

          window.onclick = function(event) {
            if (event.target == modal) {
              modal.style.display = 'none';
            }
          }

          // Auto-refresh para actualizar los relojes
          setInterval(function() {
            location.reload();
          }, 1000);
        </script>
      <?php endif; ?>
</body>

</html>