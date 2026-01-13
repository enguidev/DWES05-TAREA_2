<?php

// Para normalizar la ruta del avatar (si es personalizada o URL completa) 
function normalizarRutaAvatar($ruta)
{
  if (!$ruta) return null; // Si no hay ruta, retornamos null

  /* preg_match verifica si la cadena empieza con "http://" o "https://"
     Si es así, devolvemos la ruta tal cual
  */
  if (preg_match('/^https?:\/\//', $ruta)) return $ruta;

  // Si no, le añadimos "./" al principio y quitamos "/" del principio
  return './' . ltrim($ruta, '/');
}

// Para mostrar la pantalla principal con el GIF del tablero y botones
function mostrarPantallaPrincipal($partidasGuardadas = [])
{
?>
  <!-- Pantalla principal a pantalla completa tipo arcade -->
  <div class="pantalla-arcade">
    <!-- Título arcade -->
    <div class="titulo-arcade">
      <img src="public/imagenes/inicio/caballo_negro_girando.gif" alt="Caballo" class="icono-titulo">
      <div class="titulo-contenedor">
        <h1>PHP CHESS</h1>
        <p class="subtitulo-arcade">DWES - Tarea 5</p>
      </div>
      <img src="public/imagenes/inicio/caballo_negro_girando.gif" alt="Caballo" class="icono-titulo">
    </div>

    <!-- GIF del tablero de ajedrez pantalla completa -->
    <img src="public/imagenes/inicio/tablero_animado.gif" alt="Ajedrez" class="gif-arcade-fondo">

    <!-- Overlay semi-transparente para los botones -->
    <div class="overlay-botones"></div>

    <!-- Botones de acción superpuestos -->
    <div class="botones-arcade">
      <!-- Botón para cargar partida guardada (habilitado solo si hay) -->
      <form method="post" style="display: inline;">
        <button type="submit" name="abrir_modal_cargar_inicial"
          class="btn-arcade btn-cargar-arcade"
          <?php echo empty($partidasGuardadas) ? 'disabled' : ''; ?>>
          📁 Cargar Partida
        </button>
      </form>

      <!-- Botón para crear nueva partida -->
      <form method="post" style="display: inline;">
        <button type="submit" name="iniciar_nueva_partida" class="btn-arcade btn-nueva-arcade">
          ♟️ Nueva Partida
        </button>
      </form>
    </div>
  </div>
<?php
}

// Para mostrar el formulario donde se eligen nombres, avatares y configuración de tiempo
function mostrarFormularioConfig($partidasGuardadas = [])
{
?>
  <!-- Contenedor principal -->
  <div class="container">
    <h1>Configuración de Partida</h1>
    <div class="config-wrapper">
      <!-- Sección inicial (cargar partida guardada) -->
      <div class="seccion-cargar-inicio">
        <p>¿Deseas continuar con una partida anterior?</p>

        <?php
        // Si hay partidas guardadas
        if (!empty($partidasGuardadas)):
        ?>
          <!-- Mostramos un botón para cargar una partida guardada -->
          <button type="button" class="btn-cargar-inicial" onclick="abrirModalCargarInicial()">📁 Cargar Partida Guardada</button>
          <!-- Texto alternativo -->
          <p class="texto-alternativa">O crea una nueva partida a continuación</p>
        <?php
        // Si no hay partidas guardadas
        else: ?>
          <!-- Lo indicamos -->
          <p class="texto-sin-partidas">No hay partidas guardadas. Crea una nueva partida.</p>
        <?php endif; ?>
      </div>

      <!-- Linea horizontal separadora -->
      <hr class="linea-horizontal">

      <!-- Formulario de configuración de nombres, avatares y tiempo -->
      <!--enctype="multipart/form-data" para subir archivos e imágenes-->
      <form method="post" enctype="multipart/form-data" class="config-form">
        <p class="configuracion-inicial"><strong>Configuración de los jugadores</strong></p>

        <!-- Configuración del jugador de blancas -->
        <div class="jugador-config blancas-config">
          <div class="icono-configuracion-nombres-jugadores" id="avatar-display-blancas">♔</div>
          <label><strong>Jugador Blancas:</strong></label>
          <!-- Campo de nombre para el jugador con piezas blancas -->
          <input type="text" name="nombre_blancas" placeholder="Nombre del jugador 1..." maxlength="20" class="input-nombre" autofocus>
          <small>Por defecto será jugador 1</small>
          <label>Avatar:</label>
          <!-- Selector principal de tipo de avatar -->
          <select name="tipo_avatar_blancas" class="select-avatar" id="tipo_avatar_blancas">
            <option value="predeterminado">Sin avatar</option>
            <option value="ficha">Ficha de ajedrez</option>
            <option value="usuario">Usuario</option>
            <option value="gif">GIFs predeterminados</option>
            <option value="campeones">Campeones de Ajedrez</option>
            <option value="personalizado">Imagen o GIF personalizado</option>
          </select>
          <!-- Subselect: fichas de ajedrez (blancas) -->
          <div id="opciones-ficha-blancas" class="subselect-container" style="display:none;">
            <label>Ficha blanca:</label>
            <select name="avatar_ficha_blancas" class="select-avatar">
              <option value="public/imagenes/fichas_blancas/rey_blanca.png">Rey</option>
              <option value="public/imagenes/fichas_blancas/dama_blanca.png">Dama</option>
              <option value="public/imagenes/fichas_blancas/torre_blanca.png">Torre</option>
              <option value="public/imagenes/fichas_blancas/caballo_blanca.png">Caballo</option>
              <option value="public/imagenes/fichas_blancas/alfil_blanca.png">Alfil</option>
              <option value="public/imagenes/fichas_blancas/peon_blanca.png">Peón</option>
            </select>
          </div>
          <!-- Subselect: GIFs predeterminados de ajedrez -->
          <div id="opciones-gif-blancas" class="subselect-container" style="display:none;">
            <label>GIF ajedrez:</label>
            <select name="avatar_gif_blancas" class="select-avatar">
              <option value="public/imagenes/avatares/gifs/ajedrez/jaque_mate.gif">Jaque Mate</option>
              <option value="public/imagenes/avatares/gifs/ajedrez/caballo_baila.gif">Caballo baila</option>
              <option value="public/imagenes/avatares/gifs/ajedrez/reloj_tictac.gif">Reloj tic-tac</option>
              <option value="public/imagenes/avatares/gifs/ajedrez/apertura.gif">Apertura</option>
            </select>
          </div>
          <!-- Subselect: Campeones de ajedrez -->
          <div id="opciones-campeones-blancas" class="subselect-container" style="display:none;">
            <label>Campeón:</label>
            <select name="avatar_campeon_blancas" class="select-avatar">
              <option value="public/imagenes/avatares/campeones/magnus_carlsen_1.jpg">Magnus Carlsen</option>
              <option value="public/imagenes/avatares/campeones/garry _gasparov.jpg">Garry Kasparov</option>
              <option value="public/imagenes/avatares/campeones/bobby_fischer.jpg">Bobby Fischer</option>
              <option value="public/imagenes/avatares/campeones/anatoly_karpov.png">Anatoly Karpov</option>
              <option value="public/imagenes/avatares/campeones/viswanathan_anand.jpg">Viswanathan Anand</option>
              <option value="public/imagenes/avatares/campeones/judit_polgar.jpg">Judit Polgar</option>
            </select>
          </div>
          <!-- Hidden para mantener compatibilidad backend -->
          <input type="hidden" name="avatar_blancas" id="avatar_blancas_hidden" value="predeterminado">
          <!-- Si elige imagen personalizada, mostrar input de archivo -->
          <div id="contenedor-personalizado-blancas" style="display: none; margin-top: 10px;">
            <input type="file" name="avatar_personalizado_blancas" id="avatar_personalizado_blancas" style="display: none;" accept="image/*">
            <label for="avatar_personalizado_blancas" class="btn-elegir-archivo">
              📁 Elegir imagen
            </label>
            <span id="nombre-archivo-blancas" class="nombre-archivo">Ningún archivo seleccionado</span>
            <p style="font-size: 0.85em; color: #666; margin-top: 5px; font-style: italic;">La imagen aparecerá arriba automáticamente</p>
          </div>
        </div>

        <!-- Separador visual entre jugadores -->
        <div class="vs-separator">VS</div>

        <!-- Configuración del jugador de negras -->
        <div class="jugador-config negras-config">
          <div class="icono-configuracion-nombres-jugadores" id="avatar-display-negras">♚</div>
          <label><strong>Jugador Negras:</strong></label>
          <!-- Campo de nombre para el jugador con piezas negras -->
          <input type="text" name="nombre_negras" placeholder="Nombre del jugador 2..." maxlength="20" class="input-nombre">
          <small>Por defecto sería jugador 2</small>
          <label>Avatar:</label>
          <!-- Selector principal de tipo de avatar -->
          <select name="tipo_avatar_negras" class="select-avatar" id="tipo_avatar_negras">
            <option value="predeterminado">Sin avatar</option>
            <option value="ficha">Ficha de ajedrez</option>
            <option value="usuario">Usuario</option>
            <option value="gif">GIFs predeterminados</option>
            <option value="campeones">Campeones de Ajedrez</option>
            <option value="personalizado">Imagen o GIF personalizado</option>
          </select>
          <!-- Subselect: fichas de ajedrez (negras) -->
          <div id="opciones-ficha-negras" class="subselect-container" style="display:none;">
            <label>Ficha negra:</label>
            <select name="avatar_ficha_negras" class="select-avatar">
              <option value="public/imagenes/fichas_negras/rey_negra.png">Rey</option>
              <option value="public/imagenes/fichas_negras/dama_negra.png">Dama</option>
              <option value="public/imagenes/fichas_negras/torre_negra.png">Torre</option>
              <option value="public/imagenes/fichas_negras/caballo_negra.png">Caballo</option>
              <option value="public/imagenes/fichas_negras/alfil_negra.png">Alfil</option>
              <option value="public/imagenes/fichas_negras/peon_negra.png">Peón</option>
            </select>
          </div>
          <!-- Subselect: GIFs predeterminados de ajedrez -->
          <div id="opciones-gif-negras" class="subselect-container" style="display:none;">
            <label>GIF ajedrez:</label>
            <select name="avatar_gif_negras" class="select-avatar">
              <option value="public/imagenes/avatares/gifs/ajedrez/jaque_mate.gif">Jaque Mate</option>
              <option value="public/imagenes/avatares/gifs/ajedrez/caballo_baila.gif">Caballo baila</option>
              <option value="public/imagenes/avatares/gifs/ajedrez/reloj_tictac.gif">Reloj tic-tac</option>
              <option value="public/imagenes/avatares/gifs/ajedrez/apertura.gif">Apertura</option>
            </select>
          </div>
          <!-- Subselect: Campeones de ajedrez -->
          <div id="opciones-campeones-negras" class="subselect-container" style="display:none;">
            <label>Campeón:</label>
            <select name="avatar_campeon_negras" class="select-avatar">
              <option value="public/imagenes/avatares/campeones/magnus_carlsen_1.jpg">Magnus Carlsen</option>
              <option value="public/imagenes/avatares/campeones/garry _gasparov.jpg">Garry Kasparov</option>
              <option value="public/imagenes/avatares/campeones/bobby_fischer.jpg">Bobby Fischer</option>
              <option value="public/imagenes/avatares/campeones/anatoly_karpov.png">Anatoly Karpov</option>
              <option value="public/imagenes/avatares/campeones/viswanathan_anand.jpg">Viswanathan Anand</option>
              <option value="public/imagenes/avatares/campeones/judit_polgar.jpg">Judit Polgar</option>
            </select>
          </div>
          <!-- Hidden para mantener compatibilidad backend -->
          <input type="hidden" name="avatar_negras" id="avatar_negras_hidden" value="predeterminado">
          <!-- Si elige imagen personalizada, mostrar input de archivo -->
          <div id="contenedor-personalizado-negras" style="display: none; margin-top: 10px;">
            <input type="file" name="avatar_personalizado_negras" id="avatar_personalizado_negras" style="display: none;" accept="image/*">
            <label for="avatar_personalizado_negras" class="btn-elegir-archivo">
              📁 Elegir imagen
            </label>
            <span id="nombre-archivo-negras" class="nombre-archivo">Ningún archivo seleccionado</span>
            <p style="font-size: 0.85em; color: #666; margin-top: 5px; font-style: italic;">La imagen aparecerá arriba automáticamente</p>
          </div>
        </div>

        <!-- Linea horizontal separadora -->
        <hr class="linea-horizontal">

        <p class="configuracion-inicial"><strong>Configuración del tiempo</strong></p>

        <div class="config-section-inicio">
          <!-- Opción de tiempo inicial (bullet, blitz, rápidas, clásicas) -->
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
          <!-- Opción de incremento Fischer (tiempo extra por movimiento) -->
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
            <small class="texto-ayuda-incremento">Incrementar tiempo adicional al mover</small>
          </div>
        </div>

        <hr class="linea-horizontal">

        <p class="configuracion-inicial"><strong>Opciones de interfaz</strong></p>

        <div class="config-section-inicio">
          <!-- Opción para mostrar coordenadas en el tablero -->
          <div class="config-option checkbox">
            <label><input type="checkbox" name="mostrar_coordenadas" checked> Mostrar coordenadas (A-H, 1-8)</label>
          </div>
          <!-- Opción para mostrar piezas capturadas -->
          <div class="config-option checkbox">
            <label><input type="checkbox" name="mostrar_capturas" checked> Mostrar piezas capturadas</label>
          </div>

        </div>

        <!-- Linea horizontal separadora -->
        <hr class="linea-horizontal">

        <div class="botones-inicio">
          <!-- Botón para iniciar la partida con la configuración elegida -->
          <button type="submit" name="iniciar_partida" class="btn-iniciar-partida">Iniciar Partida Nueva</button>
        </div>
      </form>
    </div>
  </div>
<?php
}

// Para cargar el modal de configuración desde un archivo aparte
function mostrarModalConfig()
{
  // Incluimos el archivo con el HTML del modal
  include 'src/modal_config.php';
}

// Para mostrar el modal para guardar la partida actual con un nombre
function mostrarModalGuardarPartida($nombreSugerido)
{
?>
  <div id="modalGuardar" class="modal-overlay">
    <div class="modal-content">
      <h2>💾 Guardar Partida</h2>
      <form method="post">
        <label for="nombre_partida">Nombre de la partida:</label>
        <!-- Campo de texto con el nombre sugerido ya relleno -->
        <input type="text" id="nombre_partida" name="nombre_partida" value="<?php echo htmlspecialchars($nombreSugerido); ?>" maxlength="100" required autofocus>
        <div class="modal-buttons">
          <!-- Botón para confirmar guardar con el nombre -->
          <button type="submit" name="confirmar_guardar" class="btn-confirmar">💾 Guardar</button>
          <!-- Botón para cancelar sin guardar -->
          <button type="button" class="btn-cancelar" onclick="cerrarModal('modalGuardar')">✖️ Cancelar</button>
        </div>
      </form>
    </div>
  </div>
<?php
}

// Para mostrar el modal con lista de partidas guardadas para cargar una
function mostrarModalCargarPartida($partidas)
{
?>
  <div id="modalCargar" class="modal-overlay">
    <div class="modal-content modal-lista">
      <h2>📁 Cargar Partida</h2>
      <?php if (empty($partidas)): ?>
        <!-- Si no hay partidas guardadas, lo indicamos -->
        <p class="mensaje-vacio">No hay partidas guardadas</p>
        <div class="modal-buttons">
          <form method="post" style="display: inline;">
            <button type="submit" name="cancelar_modal" class="btn-cancelar">✖️ Cerrar</button>
          </form>
        </div>
      <?php else: ?>
        <!-- Lista de partidas guardadas -->
        <div class="lista-partidas">
          <?php foreach ($partidas as $partida): ?>
            <div class="item-partida">
              <div class="info-partida">
                <!-- Nombre y fecha de la partida -->
                <div class="nombre-partida"><?php echo htmlspecialchars($partida['nombre']); ?></div>
                <div class="fecha-partida"><?php echo htmlspecialchars($partida['fecha']); ?></div>
              </div>
              <div class="acciones-partida">
                <!-- Botón para cargar la partida -->
                <form method="post" style="display: inline;">
                  <input type="hidden" name="archivo_partida" value="<?php echo htmlspecialchars($partida['archivo']); ?>">
                  <button type="submit" name="cargar_partida" class="btn-cargar-item">📂 Cargar</button>
                </form>
                <!-- Botón para eliminar la partida -->
                <button type="button" class="btn-eliminar-item" onclick="abrirModalConfirmarEliminar('<?php echo htmlspecialchars(addslashes($partida['nombre'])); ?>', '<?php echo htmlspecialchars($partida['archivo']); ?>', false)">🗑️</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-buttons">
          <form method="post" style="display: inline;">
            <button type="submit" name="cancelar_modal" class="btn-cancelar">✖️ Cerrar</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php
}

// Para mostrar modal para confirmar si el usuario quiere reiniciar la partida
function mostrarModalConfirmarReiniciar()
{
?>
  <div id="modalConfirmarReiniciar" class="modal-overlay">
    <div class="modal-content">
      <h2>🔄 Confirmar nueva partida</h2>
      <p>¿Deseas empezar una nueva partida? Perderás todo el progreso.</p>
      <!-- Advertencia para que el usuario sepa que es irreversible -->
      <p class="texto-advertencia">Esta acción no se puede deshacer.</p>
      <div class="modal-buttons">
        <form method="post" style="display: inline;">
          <!-- Botón para confirmar el reinicio -->
          <button type="submit" name="confirmar_reiniciar" class="btn-confirmar btn-reiniciar-confirm">✅ Sí, nueva partida</button>
        </form>
        <form method="post" style="display: inline;">
          <!-- Botón para cancelar sin reiniciar -->
          <button type="submit" name="cancelar_modal" class="btn-cancelar">✖️ Cancelar</button>
        </form>
      </div>
    </div>
  </div>
<?php
}

// Para mostrar modal para confirmar si quiere jugar revancha (nueva partida manteniendo jugadores)
function mostrarModalConfirmarRevancha()
{
?>
  <div id="modalConfirmarRevancha" class="modal-overlay">
    <div class="modal-content">
      <h2>🔁 Confirmar revancha</h2>
      <p>¿Deseas iniciar una revancha? Se mantendrán los jugadores y la configuración.</p>
      <!-- Información sobre qué se mantiene y qué se reinicia -->
      <p class="info-revancha">ℹ️ El tablero se reiniciará a la posición inicial manteniendo jugadores y configuración.</p>
      <div class="modal-buttons">
        <form method="post" style="display: inline;">
          <!-- Botón para confirmar la revancha -->
          <button type="submit" name="confirmar_revancha" class="btn-confirmar btn-revancha-confirm">🔁 Revancha</button>
        </form>
        <form method="post" style="display: inline;">
          <!-- Botón para cancelar -->
          <button type="submit" name="cancelar_modal" class="btn-cancelar">✖️ Cancelar</button>
        </form>
      </div>
    </div>
  </div>
<?php
}

// Para mostrar el modal para elegir a qué pieza se promociona el peón
function mostrarModalPromocion()
{
  // Obtenemos los datos de la promoción de la sesión
  $color = isset($_SESSION['promocion_en_curso']['color']) ? $_SESSION['promocion_en_curso']['color'] : null;
  $pos = isset($_SESSION['promocion_en_curso']['posicion']) ? $_SESSION['promocion_en_curso']['posicion'] : null;
  // Si no hay datos, no mostramos nada
  if (!$color || !$pos) return;
?>
  <div id="modalPromocion" class="modal-overlay modal-promocion-visible">
    <div class="modal-content">
      <h2>👑 Elegir pieza de promoción</h2>
      <!-- Explicamos que peón es el que se promociona -->
      <p>El peón de <?php echo htmlspecialchars($color); ?> en <?php echo htmlspecialchars($pos); ?> puede promoverse. Elige la pieza:</p>
      <form method="post" class="form-promocion">
        <div class="opciones-promocion">
          <!-- Botones para elegir Dama -->
          <button type="submit" name="confirmar_promocion" value="1" class="btn-confirmar" onclick="this.form.tipo_promocion.value='Dama'">♛ Dama</button>
          <!-- Botones para elegir Torre -->
          <button type="submit" name="confirmar_promocion" value="1" class="btn-confirmar" onclick="this.form.tipo_promocion.value='Torre'">♜ Torre</button>
          <!-- Botones para elegir Alfil -->
          <button type="submit" name="confirmar_promocion" value="1" class="btn-confirmar" onclick="this.form.tipo_promocion.value='Alfil'">♝ Alfil</button>
          <!-- Botones para elegir Caballo -->
          <button type="submit" name="confirmar_promocion" value="1" class="btn-confirmar" onclick="this.form.tipo_promocion.value='Caballo'">♞ Caballo</button>
        </div>
        <!-- Input oculto que se rellena al hacer clic en un botón -->
        <input type="hidden" name="tipo_promocion" value="">
        <div class="modal-buttons modal-buttons-promocion">
          <form method="post" style="display:inline;">
            <button type="submit" name="cancelar_modal" class="btn-cancelar">✖️ Cancelar</button>
          </form>
        </div>
      </form>
    </div>
  </div>
<?php
}

// Para mostrar el modal para confirmar si quiere hacer enroque
function mostrarModalEnroque()
{
  // Obtenemos los datos del enroque pendiente
  $tipo = isset($_SESSION['enroque_pendiente']['tipo']) ? $_SESSION['enroque_pendiente']['tipo'] : null;
  $color = isset($_SESSION['enroque_pendiente']['color']) ? $_SESSION['enroque_pendiente']['color'] : null;
  $origen = isset($_SESSION['enroque_pendiente']['origen']) ? $_SESSION['enroque_pendiente']['origen'] : null;
  $destino = isset($_SESSION['enroque_pendiente']['destino']) ? $_SESSION['enroque_pendiente']['destino'] : null;
  // Si no hay datos, no mostramos nada
  if (!$tipo || !$color) return;

  // Generamos el nombre del enroque (corto = O-O, largo = O-O-O)
  $nombreEnroque = ($tipo === 'corto') ? 'enroque corto (O-O)' : 'enroque largo (O-O-O)';
?>
  <div id="modalEnroque" class="modal-overlay">
    <div class="modal-content">
      <h2>🏰 Confirmación de enroque</h2>
      <!-- Explicamos qué movimiento se está haciendo -->
      <p>Has movido el rey de <?php echo htmlspecialchars($color); ?> a una posición que permite realizar el <strong><?php echo htmlspecialchars($nombreEnroque); ?></strong>.</p>
      <p>¿Deseas ejecutar el enroque ahora?</p>
      <!-- Información adicional sobre qué pasa si cancela -->
      <div class="modal-info" style="margin: 15px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; font-size: 0.9em;">
        <strong>Nota:</strong> Si cancelas, el rey no se moverá y conservarás la opción de enrocar más tarde.
      </div>
      <form method="post" class="form-enroque">
        <!-- Pasamos los datos como inputs ocultos -->
        <input type="hidden" name="origen_enroque" value="<?php echo htmlspecialchars($origen); ?>">
        <input type="hidden" name="destino_enroque" value="<?php echo htmlspecialchars($destino); ?>">
        <input type="hidden" name="tipo_enroque" value="<?php echo htmlspecialchars($tipo); ?>">
        <div class="modal-buttons">
          <!-- Botón para confirmar el enroque -->
          <button type="submit" name="confirmar_enroque" value="1" class="btn-confirmar">✅ Confirmar enroque</button>
          <!-- Botón para cancelar -->
          <button type="submit" name="cancelar_enroque" value="1" class="btn-cancelar">❌ Cancelar</button>
        </div>
      </form>
    </div>
  </div>
<?php
}

// Para mostrar la cabecera del juego con título y botones de pausa/configuración
function mostrarCabeceraJuego($partida)
{
?>
  <div class="header-juego">
    <h1>♟️ Partida de Ajedrez</h1>
    <div class="header-buttons">
      <!-- Botón para abrir configuración -->
      <button id="btnConfiguracion" class="btn-configuracion" title="Configuración">⚙️</button>
      <form method="post" style="display: inline;">
        <!-- Botón para pausar/reanudar según el estado actual -->
        <button type="submit" name="alternar_pausa" class="btn-pausa" id="btnPausa" title="Pausar/Reanudar">
          <?php echo (isset($_SESSION['pausa']) && $_SESSION['pausa']) ? '▶️' : '⏸️'; ?>
        </button>
      </form>
    </div>
  </div>
<?php
}

function mostrarBotonesControl($partida)
{
?>
  <!-- Botones de control durante la partida -->
  <div class="botones-control">
    <!-- Botón para deshacer el último movimiento -->
    <form method="post" style="display: inline;">
      <button type="submit" name="deshacer" class="btn-deshacer" id="btn-deshacer" <?php echo !$partida->tieneHistorial() ? 'disabled' : ''; ?>>↶ Deshacer</button>
    </form>
    <!-- Botón para jugar revancha (nueva partida con los mismos jugadores) -->
    <form method="post" style="display: inline;">
      <button type="submit" name="abrir_modal_revancha" class="btn-revancha" id="btn-revancha" title="Nueva partida con la misma configuración">🔁 Revancha</button>
    </form>
    <!-- Botón para guardar la partida actual -->
    <form method="post" style="display: inline;">
      <button type="submit" name="abrir_modal_guardar" class="btn-guardar" id="btn-guardar" <?php echo (!isset($_SESSION['pausa']) || !$_SESSION['pausa']) ? 'disabled' : ''; ?>>💾 Guardar partida</button>
    </form>
    <!-- Botón para reiniciar y volver a la pantalla de inicio -->
    <form method="post" style="display: inline;">
      <button type="submit" name="abrir_modal_reiniciar" class="btn-reiniciar" id="btn-reiniciar">🔄 Nueva partida</button>
    </form>
  </div>
<?php
}

// Para mostrar los relojes
function mostrarRelojes($jugadores, $marcador)
{
?>
  <!-- RELOJES - Mostramos los tiempos y nombres de ambos jugadores -->
  <div class="relojes-container">
    <!-- Reloj del jugador con piezas blancas -->
    <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'blancas' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-blancas">
      <div class="reloj-jugador">
        <?php
        // Intentamos obtener el avatar del jugador, si no tiene mostramos un círculo blanco
        $avatarBlancasSrc = normalizarRutaAvatar(isset($_SESSION['avatar_blancas']) ? $_SESSION['avatar_blancas'] : null);
        ?>
        <?php if ($avatarBlancasSrc): ?>
          <!-- Mostramos la imagen del avatar si existe -->
          <img src="<?php echo htmlspecialchars($avatarBlancasSrc); ?>" class="avatar-circular" alt="Avatar Blancas">
        <?php else: ?>
          <!-- Si no hay avatar mostramos un círculo blanco -->
          ⚪
        <?php endif; ?>
        <!-- Nombre del jugador con piezas blancas -->
        <?php echo $jugadores['blancas']->getNombre(); ?>
      </div>
      <!-- Tiempo restante del jugador blanco - Se resalta en rojo si le quedan menos de 60 segundos -->
      <div id="tiempo-blancas" class="reloj-tiempo <?php echo $_SESSION['tiempo_blancas'] < 60 ? 'tiempo-critico' : ''; ?>">
        <?php echo formatearTiempo($_SESSION['tiempo_blancas']); ?>
      </div>
      <!-- Puntuación del jugador blanco en esta partida -->
      <div class="reloj-puntos"><?php echo $marcador[0]; ?> pts</div>
    </div>
    <!-- Separador visual entre relojes -->
    <div class="reloj-separador">⏱️</div>
    <!-- Reloj del jugador con piezas negras -->
    <div class="reloj <?php echo $_SESSION['reloj_activo'] === 'negras' ? 'reloj-activo' : 'reloj-inactivo'; ?> reloj-negras">
      <div class="reloj-jugador">
        <?php
        // Intentamos obtener el avatar del jugador, si no tiene mostramos un círculo negro
        $avatarNegrasSrc = normalizarRutaAvatar(isset($_SESSION['avatar_negras']) ? $_SESSION['avatar_negras'] : null);
        ?>
        <?php if ($avatarNegrasSrc): ?>
          <!-- Mostramos la imagen del avatar si existe -->
          <img src="<?php echo htmlspecialchars($avatarNegrasSrc); ?>" class="avatar-circular" alt="Avatar Negras">
        <?php else: ?>
          <!-- Si no hay avatar mostramos un círculo negro -->
          ⚫
        <?php endif; ?>
        <!-- Nombre del jugador con piezas negras -->
        <?php echo $jugadores['negras']->getNombre(); ?>
      </div>
      <!-- Tiempo restante del jugador negro - Se resalta en rojo si le quedan menos de 60 segundos -->
      <div id="tiempo-negras" class="reloj-tiempo <?php echo $_SESSION['tiempo_negras'] < 60 ? 'tiempo-critico' : ''; ?>">
        <?php echo formatearTiempo($_SESSION['tiempo_negras']); ?>
      </div>
      <!-- Puntuación del jugador negro en esta partida -->
      <div class="reloj-puntos"><?php echo $marcador[1]; ?> pts</div>
    </div>
  </div>
<?php
}

// Para mostrar el tablero
function mostrarTablero($partida, $casillaSeleccionada, $turno, $piezasCapturadas)
{
?>
  <!-- TABLERO - El corazón del juego, aquí mostramos el tablero de ajedrez con todas las piezas -->
  <?php if ($_SESSION['config']['mostrar_capturas']): ?>
    <!-- Si está activada la opción de mostrar capturas, creamos un wrapper con el tablero y las piezas capturadas -->
    <div class="tablero-y-capturas-wrapper">
      <!-- Panel lateral izquierdo: Piezas negras capturadas por el jugador blanco -->
      <div class="piezas-capturadas-lado">
        <h3>Cap. negras:</h3>
        <div class="capturadas-vertical">
          <?php foreach ($piezasCapturadas['blancas'] as $pieza): ?>
            <!-- Mostramos la imagen de cada pieza negra capturada -->
            <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="pieza-capturada">
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- Si no se muestran capturas, usamos un wrapper más simple -->
      <div class="tablero-solo-wrapper">
      <?php endif; ?>

      <div class="tablero-wrapper">
        <div class="tablero-contenedor <?php echo $_SESSION['config']['mostrar_coordenadas'] ? '' : 'sin-coordenadas'; ?>">
          <?php
          // Letras de las columnas (A-H) para mostrar las coordenadas
          $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

          // Si está activado mostrar coordenadas, pintamos las letras en la parte superior
          if ($_SESSION['config']['mostrar_coordenadas']) {
            echo '<div class="coordenada-esquina-superior-izquierda"></div>';
            foreach ($letras as $letra) echo '<div class="coordenada-superior">' . $letra . '</div>';
            echo '<div class="coordenada-esquina-superior-derecha"></div>';
          }

          // Recorremos las filas desde arriba (8) hasta abajo (1)
          for ($fila = 8; $fila >= 1; $fila--):
            // Si está activado mostrar coordenadas, pintamos los números a la izquierda
            if ($_SESSION['config']['mostrar_coordenadas']) {
              echo '<div class="coordenada-izquierda">' . $fila . '</div>';
            }

            // Recorremos las columnas de izquierda a derecha (0-7 = A-H)
            for ($columna = 0; $columna < 8; $columna++):
              // Construimos la posición actual (ej: "A8", "B7", etc)
              $posicion = $letras[$columna] . $fila;
              // Obtenemos la pieza que está en esta casilla (si hay alguna)
              $pieza = obtenerPiezaEnCasilla($posicion, $partida);
              // Alternamos colores: si (fila + columna) es par = casilla blanca, si es impar = casilla negra
              $colorCasilla = (($fila + $columna) % 2 === 0) ? 'blanca' : 'negra';
              // Verificamos si esta casilla está seleccionada actualmente
              $esSeleccionada = ($casillaSeleccionada === $posicion);

              // Variables para determinar si este movimiento es válido o captura
              $esMovimientoPosible = false;
              $esCaptura = false;

              // Solo mostramos movimientos posibles si hay una casilla seleccionada y no estamos en pausa
              if ($casillaSeleccionada !== null && !$esSeleccionada && (!isset($_SESSION['pausa']) || !$_SESSION['pausa'])) {
                // Obtenemos la pieza seleccionada
                $piezaSeleccionada = obtenerPiezaEnCasilla($casillaSeleccionada, $partida);
                // Solo mostramos movimientos si la pieza pertenece al jugador actual
                if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $turno) {
                  // Obtenemos la pieza que está en la casilla destino (si hay)
                  $piezaEnDestino = obtenerPiezaEnCasilla($posicion, $partida);
                  // Variable para saber si hay pieza en el destino
                  $hayPiezaDestino = ($piezaEnDestino !== null);

                  // Los peones tienen movimientos especiales (diagonal para capturar, recto para avanzar)
                  if ($piezaSeleccionada instanceof Peon) {
                    // Para peones: si hay pieza enemiga en destino, es captura
                    $esCapturaNormal = ($hayPiezaDestino && $piezaEnDestino->getColor() !== $turno);
                    $movimientos = $piezaSeleccionada->simulaMovimiento($posicion, $esCapturaNormal);
                  } else {
                    // Para otras piezas, simulamos el movimiento normal
                    $movimientos = $piezaSeleccionada->simulaMovimiento($posicion);
                  }

                  // Si la pieza puede moverse a esta casilla
                  if (!empty($movimientos)) {
                    // Verificamos si hay piezas bloqueando el camino (excepto para caballos que saltan)
                    $bloqueado = false;
                    if (!($piezaSeleccionada instanceof Caballo)) {
                      // Recorremos el camino desde la casilla actual hasta la penúltima del recorrido
                      for ($i = 0; $i < count($movimientos) - 1; $i++) {
                        // Si encontramos una pieza en el camino, está bloqueado
                        if (obtenerPiezaEnCasilla($movimientos[$i], $partida) !== null) {
                          $bloqueado = true;
                          break;
                        }
                      }
                    }

                    // Si el camino no está bloqueado, determinamos si es movimiento o captura
                    if (!$bloqueado) {
                      if ($piezaEnDestino !== null) {
                        // Si hay pieza en destino y no es de nuestro color, es una captura
                        if ($piezaEnDestino->getColor() !== $turno) {
                          $esMovimientoPosible = true;
                          $esCaptura = true;
                        }
                      } else {
                        // Si no hay pieza en destino, es un movimiento normal
                        $esMovimientoPosible = true;
                      }
                    }
                  }

                  // DETECCIÓN DE CAPTURA AL PASO
                  // Si es un peón y el movimiento diagonal a casilla vacía no fue detectado, verificar captura al paso
                  if ($piezaSeleccionada instanceof Peon && !$hayPiezaDestino && !$esMovimientoPosible) {
                    // Convertir posiciones a coordenadas numéricas
                    $coordsOrigen = [$letras[array_search($casillaSeleccionada[0], $letras)], (int)$casillaSeleccionada[1]];
                    $coordsDestino = [$letras[array_search($posicion[0], $letras)], (int)$posicion[1]];

                    // Dirección de avance según el color
                    $direccion = ($turno === 'blancas') ? 1 : -1;

                    // Verificar si es movimiento diagonal de 1 casilla hacia adelante
                    $difFilas = $coordsDestino[1] - $coordsOrigen[1];
                    $difCols = abs(array_search($coordsDestino[0], $letras) - array_search($coordsOrigen[0], $letras));

                    if ($difFilas === $direccion && $difCols === 1) {
                      // Casilla donde estaría el peón a capturar (misma fila origen, columna destino)
                      $posCapturaEnPassant = $posicion[0] . $casillaSeleccionada[1];
                      $piezaPosibleCapturada = obtenerPiezaEnCasilla($posCapturaEnPassant, $partida);

                      // Verificar que hay un peón enemigo en esa posición
                      if ($piezaPosibleCapturada instanceof Peon && $piezaPosibleCapturada->getColor() !== $turno) {
                        // Obtener el último movimiento de la partida
                        $ultimoMovimiento = $partida->getUltimoMovimiento();

                        if ($ultimoMovimiento && $ultimoMovimiento['pieza'] === 'Peon' && $ultimoMovimiento['color'] !== $turno) {
                          // Convertir origen y destino del último movimiento a coordenadas
                          $umOrigen = $ultimoMovimiento['origen'];
                          $umDestino = $ultimoMovimiento['destino'];
                          $umOrigenFila = (int)$umOrigen[1];
                          $umDestinoFila = (int)$umDestino[1];

                          // Verificar que fue un avance de 2 casillas y acabó en la posición a capturar
                          $salto = abs($umDestinoFila - $umOrigenFila);
                          if ($salto === 2 && $umDestino === $posCapturaEnPassant) {
                            // ¡Captura al paso válida!
                            $esMovimientoPosible = true;
                            $esCaptura = true;
                          }
                        }
                      }
                    }
                  }
                }
              }
          ?>
              <!-- Casilla del tablero -->
              <div class="casilla <?php echo $colorCasilla; ?> <?php echo $esSeleccionada ? 'seleccionada' : ''; ?>">
                <?php if ($pieza !== null): ?>
                  <!-- Si hay una pieza en esta casilla, mostramos un botón para interactuar con ella -->
                  <form method="post" class="formulario">
                    <button type="submit" name="seleccionar_casilla" value="<?php echo $posicion; ?>"
                      class="btn-pieza-casilla <?php echo ($pieza->getColor() === $turno) ? 'puede-seleccionar' : 'no-puede-seleccionar'; ?> <?php echo $esCaptura ? 'btn-captura' : ''; ?>"
                      <?php echo (isset($_SESSION['pausa']) && $_SESSION['pausa']) ? 'disabled' : ''; ?>>
                      <!-- Mostramos la imagen de la pieza -->
                      <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="imagen-pieza">
                    </button>
                  </form>
                <?php elseif ($esMovimientoPosible): ?>
                  <!-- Si es un movimiento posible, mostramos un indicador visual (círculo verde) -->
                  <form method="post" class="formulario">
                    <button type="submit" name="seleccionar_casilla" value="<?php echo $posicion; ?>" class="btn-movimiento">
                      <!-- Indicador visual del movimiento posible -->
                      <span class="indicador-movimiento"></span>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endfor; ?>

            <!-- Si está activado mostrar coordenadas, pintamos los números a la derecha -->
            <?php if ($_SESSION['config']['mostrar_coordenadas']): ?>
              <div class="coordenada-derecha"><?php echo $fila; ?></div>
            <?php endif; ?>
          <?php endfor; ?>

          <!-- Si está activado mostrar coordenadas, pintamos las letras en la parte inferior -->
          <?php if ($_SESSION['config']['mostrar_coordenadas']): ?>
            <div class="coordenada-esquina-inferior-izquierda"></div>
            <?php foreach ($letras as $letra): ?>
              <div class="coordenada-inferior"><?php echo $letra; ?></div>
            <?php endforeach; ?>
            <div class="coordenada-esquina-inferior-derecha"></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Panel lateral derecho: Piezas blancas capturadas por el jugador negro -->
      <?php if ($_SESSION['config']['mostrar_capturas']): ?>
        <div class="piezas-capturadas-lado">
          <h3>Cap. blancas:</h3>
          <div class="capturadas-vertical">
            <?php foreach ($piezasCapturadas['negras'] as $pieza): ?>
              <!-- Mostramos la imagen de cada pieza blanca capturada -->
              <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="pieza-capturada">
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      </div>

      <!-- Llamamos a la función para mostrar los botones de control -->
      <?php mostrarBotonesControl($partida); ?>

      <!-- HISTORIAL DE MOVIMIENTOS - Mostramos todos los movimientos realizados en la partida -->
      <div class="historial-movimientos">
        <!-- Encabezado del historial (clickeable para expandir/contraer) -->
        <div class="historial-header" onclick="toggleHistorial()">
          <span><strong>Historial de movimientos</strong></span>
          <span id="historial-toggle" class="historial-toggle">▼</span>
        </div>
        <!-- Contenido del historial (inicialmente oculto) -->
        <div id="historial-contenido" class="historial-contenido" style="display: none;">
          <?php
          // Obtenemos el historial de movimientos desde la partida
          $historial = $partida->getHistorialMovimientos();
          if (empty($historial)):
          ?>
            <!-- Si no hay movimientos, mostramos un mensaje -->
            <p class="mensaje-sin-movimientos">No hay movimientos registrados</p>
          <?php else: ?>
            <!-- Si hay movimientos, los mostramos en una grilla de dos columnas -->
            <div class="historial-grid">
              <?php foreach ($historial as $mov): ?>
                <!-- Cada movimiento en su propia caja -->
                <div class="movimiento-item <?php echo ($mov['color'] === 'blancas') ? 'movimiento-blancas' : 'movimiento-negras'; ?>">
                  <!-- Número del movimiento (formato estándar de ajedrez: 1., 2., etc) -->
                  <small class="numero-movimiento">
                    <?php
                    // Calculamos el número del movimiento (2 medios movimientos = 1 movimiento completo)
                    $numeroMov = ceil($mov['numero'] / 2);
                    if ($mov['color'] === 'blancas') {
                      // Para blancas mostramos el número
                      echo $numeroMov . '.';
                    } else {
                      // Para negras mostramos "..." para indicar que es respuesta
                      echo '...';
                    }
                    ?>
                  </small>
                  <!-- Notación del movimiento en formato algebraico -->
                  <span class="notacion-movimiento <?php echo ($mov['color'] === 'blancas') ? 'notacion-blancas' : 'notacion-negras'; ?>">
                    <?php echo htmlspecialchars($mov['notacion']); ?>
                  </span>
                  <!-- Si fue una captura, mostramos una X roja -->
                  <?php if ($mov['captura']): ?>
                    <small class="icono-captura">✕</small>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- SECCIÓN DE INSTRUCCIONES Y CONTROLES -->
      <div class="instrucciones">
        <!-- Encabezado de instrucciones (clickeable para expandir/contraer) -->
        <div class="instrucciones-header" onclick="toggleInstrucciones()">
          <span><strong>Reglas y Controles</strong></span>
          <span id="instrucciones-toggle" class="instrucciones-toggle">▼</span>
        </div>
        <!-- Contenido de instrucciones (inicialmente oculto) -->
        <div id="instrucciones-contenido" class="instrucciones-contenido" style="display: none;">
          <!-- SECCIÓN: Cómo jugar -->
          <h4 class="titulo-seccion">Cómo jugar:</h4>
          <ol>
            <li><strong>Pausa/Reanudar</strong>: Usa el botón superior (⏸️/▶️) para pausar la partida</li>
            <li><strong>Reloj</strong>: Solo corre el reloj del jugador en turno</li>
            <li><strong>Movimientos válidos</strong>: Se marcan con círculos verdes</li>
            <li><strong>Capturas</strong>: Se marcan con borde rojo pulsante</li>
            <li><strong>Tiempo límite</strong>: Si llegas a 0:00, pierdes automáticamente</li>
          </ol>

          <!-- SECCIÓN: Gestión de partida -->
          <h4 class="titulo-seccion-separado">Gestión de partida:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>-Guardar</strong>: Guarda la partida actual para continuarla posteriormente (solo posible si pausas la partida)</li>
            <li><strong>-Cargar</strong>: Carga una partida guardada anteriormente</li>
            <li><strong>-Nueva partida</strong>: Inicia una nueva partida desde el principio</li>
            <li><strong>-Configuración</strong>: Ajusta opciones visuales y de tiempo</li>
          </ul>

          <!-- SECCIÓN: Configuración -->
          <h4 class="titulo-seccion-separado">Configuración:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>-Avatares</strong>: Personaliza la imagen de los jugadores</li>
            <li><strong>-Tiempo inicial</strong>: Elige cuánto tiempo tienen por partida</li>
            <li><strong>-Incremento Fischer</strong>: Tiempo adicional por cada movimiento</li>
            <li><strong>-Mostrar coordenadas</strong>: Activa/desactiva las letras y números del tablero</li>
            <li><strong>-Mostrar capturas</strong>: Visualiza las piezas capturadas</li>
          </ul>
        </div>
      </div>
    <?php
  }

  // Para mostrar modal para cargar una partida guardada desde la pantalla de inicio
  function mostrarModalCargarInicial($partidas)
  {
    ?>
      <!-- Overlay que cubre toda la pantalla y modal centrado -->
      <div id="modalCargarInicial" class="modal-overlay">
        <div class="modal-content modal-lista">
          <!-- Título del modal -->
          <h2>📁 Cargar Partida Guardada</h2>
          <?php if (empty($partidas)): ?>
            <!-- Si no hay partidas guardadas, mostramos un mensaje vacío -->
            <p class="mensaje-vacio">No hay partidas guardadas</p>
            <div class="modal-buttons">
              <button type="button" class="btn-cancelar" onclick="cerrarModal('modalCargarInicial')">✖️ Cerrar</button>
            </div>
          <?php else: ?>
            <!-- Si hay partidas, mostramos una lista con cada una -->
            <div class="lista-partidas">
              <?php foreach ($partidas as $partida): ?>
                <!-- Cada partida guardada en su propia caja -->
                <div class="item-partida">
                  <!-- Información de la partida (nombre y fecha) -->
                  <div class="info-partida">
                    <div class="nombre-partida"><?php echo htmlspecialchars($partida['nombre']); ?></div>
                    <div class="fecha-partida"><?php echo htmlspecialchars($partida['fecha']); ?></div>
                  </div>
                  <!-- Botones de acción para cada partida -->
                  <div class="acciones-partida">
                    <!-- Botón para cargar la partida -->
                    <form method="post" class="formulario-inline">
                      <input type="hidden" name="archivo_partida" value="<?php echo htmlspecialchars($partida['archivo']); ?>">
                      <button type="submit" name="cargar_partida_inicial" class="btn-cargar-item">📂 Cargar</button>
                    </form>
                    <!-- Botón para eliminar la partida -->
                    <form method="post" class="formulario-inline">
                      <input type="hidden" name="archivo_partida" value="<?php echo htmlspecialchars($partida['archivo']); ?>">
                      <button type="button" class="btn-eliminar-item" onclick="abrirModalConfirmarEliminar('<?php echo htmlspecialchars(addslashes($partida['nombre'])); ?>', '<?php echo htmlspecialchars($partida['archivo']); ?>', true)">🗑️</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <!-- Botón para cerrar el modal -->
            <div class="modal-buttons">
              <button type="button" class="btn-cancelar" onclick="cerrarModal('modalCargarInicial')">✖️ Cerrar</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php
  }

  // Para mostrar modal para confirmar la eliminación de una partida guardada
  function mostrarModalConfirmarEliminar($nombrePartida, $archivoPartida, $desdeInicio = false)
  {
    ?>
      <!-- Modal de confirmación con overlay oscuro de fondo -->
      <div id="modalConfirmarEliminar" class="modal-overlay">
        <div class="modal-content">
          <!-- Icono de advertencia y título -->
          <h2>⚠️ Confirmar eliminación</h2>
          <!-- Mostramos el nombre de la partida que se va a eliminar -->
          <p>¿Deseas eliminar la partida "<strong><?php echo htmlspecialchars($nombrePartida); ?></strong>"?</p>
          <!-- Advertencia de que la acción es irreversible -->
          <p class="texto-advertencia">Esta acción no se puede deshacer.</p>
          <div class="modal-buttons">
            <form method="post" class="formulario-inline">
              <input type="hidden" name="archivo_partida" value="<?php echo htmlspecialchars($archivoPartida); ?>">
              <button type="submit" name="<?php echo $desdeInicio ? 'eliminar_partida_inicial' : 'eliminar_partida'; ?>" class="btn-confirmar btn-eliminar">🗑️ Eliminar</button>
            </form>
            <button type="button" class="btn-cancelar" onclick="cerrarModal('modalConfirmarEliminar')">✖️ Cancelar</button>
          </div>
        </div>
      </div>
    <?php
  }
