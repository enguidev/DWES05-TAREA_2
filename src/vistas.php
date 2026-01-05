<?php

/**
 * Funciones de renderizado para la aplicación de ajedrez
 */

/**
 * Renderiza el formulario de configuración inicial
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
          <small>Por defecto será jugador 1</small>
          <label>Avatar:</label>
          <select name="avatar_blancas" class="select-avatar">
            <option value="default">Sin avatar</option>
            <option value="rey_blanca.png">Rey Blanco</option>
            <option value="dama_blanca.png">Dama Blanca</option>
            <option value="torre_blanca.png">Torre Blanca</option>
          </select>
        </div>

        <div class="vs-separator">VS</div>

        <div class="jugador-config negras-config">
          <div class="icono-configuracion-nombres-jugadores">♚</div>
          <label><strong>Jugador Negras:</strong></label>
          <input type="text" name="nombre_negras" placeholder="Nombre del jugador 2..." maxlength="20" class="input-nombre">
          <small>Por defecto sería jugador 2</small>
          <label>Avatar:</label>
          <select name="avatar_negras" class="select-avatar">
            <option value="default">Sin avatar</option>
            <option value="rey_negra.png">Rey Negro</option>
            <option value="dama_negra.png">Dama Negra</option>
            <option value="torre_negra.png">Torre Negra</option>
          </select>
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
  include 'src/modal_config.php';
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
      <div class="reloj-jugador">
        <?php if (isset($_SESSION['avatar_blancas']) && $_SESSION['avatar_blancas']): ?>
          <img src="public/imagenes/avatares/<?php echo htmlspecialchars($_SESSION['avatar_blancas']); ?>" class="avatar-circular" alt="Avatar Blancas">
        <?php else: ?>
          ⚪
        <?php endif; ?>
        <?php echo $jugadores['blancas']->getNombre(); ?>
      </div>
      <div id="tiempo-blancas" class="reloj-tiempo <?php echo $_SESSION['tiempo_blancas'] < 60 ? 'tiempo-critico' : ''; ?>">
        <?php echo formatearTiempo($_SESSION['tiempo_blancas']); ?>
      </div>
      <div class="reloj-puntos"><?php echo $marcador[0]; ?> pts</div>
    </div>
    <div class="reloj-separador">⏱️</div>
    <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'negras' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-negras">
      <div class="reloj-jugador">
        <?php if (isset($_SESSION['avatar_negras']) && $_SESSION['avatar_negras']): ?>
          <img src="public/imagenes/avatares/<?php echo htmlspecialchars($_SESSION['avatar_negras']); ?>" class="avatar-circular" alt="Avatar Negras">
        <?php else: ?>
          ⚫
        <?php endif; ?>
        <?php echo $jugadores['negras']->getNombre(); ?>
      </div>
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
