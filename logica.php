<?php

/**
 * Lógica de la aplicación de ajedrez
 */

/**
 * Procesa la petición AJAX para actualizar relojes
 */
function procesarAjaxUpdateClocks()
{
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
    session_write_close();
  }
  exit;
}

/**
 * Procesa la configuración guardada
 */
function procesarGuardarConfiguracion()
{
  // Solo permitir cambiar opciones visuales, no el tiempo
  $_SESSION['config']['mostrar_coordenadas'] = isset($_POST['mostrar_coordenadas']);
  $_SESSION['config']['mostrar_capturas'] = isset($_POST['mostrar_capturas']);
}

/**
 * Inicia una nueva partida
 */
function iniciarPartida()
{
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

/**
 * Procesa la pausa/reanudación
 */
function procesarTogglePausa()
{
  if (isset($_SESSION['pausa'])) {
    $_SESSION['pausa'] = !$_SESSION['pausa'];

    // Si se reanuda, resetear ultimo_tick
    if (!$_SESSION['pausa']) {
      $_SESSION['ultimo_tick'] = time();
    }
  }
}

/**
 * Reinicia la partida
 */
function reiniciarPartida()
{
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

/**
 * Procesa una jugada
 */
function procesarJugada($partida)
{
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
}

/**
 * Deshace una jugada
 */
function deshacerJugada($partida)
{
  $partida->deshacerJugada();
  $_SESSION['casilla_seleccionada'] = null;
  $_SESSION['partida'] = serialize($partida);
}

/**
 * Guarda la partida
 */
function guardarPartida($partida)
{
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

/**
 * Carga la partida
 */
function cargarPartida()
{
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
    return unserialize($_SESSION['partida']);
  }
  return null;
}

/**
 * Renderiza el formulario de configuración
 */
function renderConfigForm()
{
?>
  <div class="container">
    <h1>Configuración de Partida</h1>
    <div class="config-wrapper">
      <form method="post" class="config-form">
        <p class="configuracion-inicial"><strong>Nombres de los jugadores</strong></p>

        <div class="jugador-config blancas-config">
          <div class="icono-configuracion-nombres-jugadores">♔</div>
          <label><strong>Jugador Blancas:</strong></label>
          <input type="text" name="nombre_blancas" placeholder="Nombre del jugador 1..." maxlength="20" class="input-nombre" autofocus>
          <small>Por defecto sería jugador 1</small>
        </div>

        <div class="vs-separator">VS</div>

        <div class="jugador-config negras-config">
          <div class="icono-configuracion-nombres-jugadores">♚</div>
          <label><strong>Jugador Negras:</strong></label>
          <input type="text" name="nombre_negras" placeholder="Nombre del jugador 2..." maxlength="20" class="input-nombre">
          <small>Por defecto sería jugador 2</small>
        </div>

        <hr class="linea-horizontal">

        <p class="configuracion-inicial"><strong>Configuración del tiempo</strong></p>

        <div class="config-section-inicio">
          <div class="config-option">
            <label>Tiempo inicial por jugador:</label>
            <select name="tiempo_inicial" class="select-tiempo">
              <option value="60">1 minuto (Bullet)</option>
              <option value="180">3 minutos (Blitz)</option>
              <option value="300">5 minutos (Blitz)</option>
              <option value="600">10 minutos (Rápidas)</option>
              <option value="900">15 minutos (Rápidas)</option>
              <option value="1800" selected>30 minutos (Clásicas)</option>
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
            <small style="display: block; margin-top: 5px;">Incrementar tiempo adicional al mover</small>
          </div>
        </div>

        <hr class="linea-horizontal">

        <p class="configuracion-inicial"><strong>Opciones de interfaz</strong></p>

        <div class="config-section-inicio">
          <div class="config-option checkbox">
            <label><input type="checkbox" name="mostrar_coordenadas" checked> Mostrar coordenadas (A-H, 1-8)</label>
          </div>
          <div class="config-option checkbox">
            <label><input type="checkbox" name="mostrar_capturas" checked> Mostrar piezas capturadas</label>
          </div>

        </div>
        <hr class="linea-horizontal">

        <button type="submit" name="iniciar_partida" class="btn-iniciar-partida">Iniciar Partida</button>
      </form>
    </div>
  </div>
<?php
}

/**
 * Renderiza el modal de configuración
 */
function renderModalConfig()
{
?>
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
<?php
}

/**
 * Renderiza el header del juego
 */
function renderGameHeader()
{
?>
  <div class="header-juego">
    <h1>♟️ Partida de Ajedrez</h1>
    <div class="header-buttons">
      <button id="btnConfig" class="btn-config">⚙️</button>
      <form method="post" style="display: inline;">
        <button type="submit" name="toggle_pausa" class="btn-pausa" id="btnPausa">
          <?php echo (isset($_SESSION['pausa']) && $_SESSION['pausa']) ? '▶️' : '⏸️'; ?>
        </button>
      </form>
    </div>
  </div>
<?php
}

/**
 * Renderiza los relojes
 */
function renderRelojes($jugadores, $marcador)
{
?>
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
<?php
}

/**
 * Renderiza el tablero
 */
function renderTablero($partida, $casillaSeleccionada, $turno, $piezasCapturadas)
{
?>
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
        <div class="tablero-contenedor <?php echo $_SESSION['config']['mostrar_coordenadas'] ? '' : 'sin-coordenadas'; ?>">
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
          <button type="submit" name="reiniciar" class="btn-reiniciar">🔄 Reiniciar</button>
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
    <?php
  }
