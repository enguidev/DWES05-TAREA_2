<?php

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
        <p class="subtitulo-arcade">Carlos Engui García | 2º DAW</p>
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
  <!-- Botón de salir arriba a la derecha (fuera del container) -->
  <div class="boton-salir-config-top">
    <form method="post">
      <button type="submit" name="salir_configuracion" class="btn-salir-config">← Volver al inicio</button>
    </form>
  </div>

  <!-- Contenedor principal -->
  <div class="container">
    <h1>Configuración de Partida</h1>
    <div class="config-wrapper">
      <!-- Sección inicial (cargar partida guardada) -->
      <div class="seccion-cargar-inicio">
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
          <p class="texto-sin-partidas">No hay partidas guardadas</p>
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
          <!-- Subselect: GIFs predeterminados -->
          <div id="opciones-gif-blancas" class="subselect-container" style="display:none;">
            <label>GIF predeterminado:</label>
            <select name="avatar_gif_blancas" class="select-avatar">
              <option value="public/imagenes/avatares/gifs_predeterminados/alfil_cambia_color.gif">Alfil cambia color</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/anand_vs_kasparov.gif">Anand vs Kasparov</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/anciano.gif">Anciano</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/bart_simpson_multijugando.gif">Bart Simpson</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/figura_animada.gif">Figura animada</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/gato_moviendo_ficha.gif">Gato moviendo ficha</option>
            </select>
          </div>
          <!-- Subselect: Campeones de ajedrez -->
          <div id="opciones-campeones-blancas" class="subselect-container" style="display:none;">
            <label>Campeón:</label>
            <select name="avatar_campeon_blancas" class="select-avatar">
              <option value="public/imagenes/avatares/campeones/magnus_carlsen.jpg">Magnus Carlsen</option>
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
          <!-- Subselect: GIFs predeterminados -->
          <div id="opciones-gif-negras" class="subselect-container" style="display:none;">
            <label>GIF predeterminado:</label>
            <select name="avatar_gif_negras" class="select-avatar">
              <option value="public/imagenes/avatares/gifs_predeterminados/alfil_cambia_color.gif">Alfil cambia color</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/anand_vs_kasparov.gif">Anand vs Kasparov</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/anciano.gif">Anciano</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/bart_simpson_multijugando.gif">Bart Simpson</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/figura_animada.gif">Figura animada</option>
              <option value="public/imagenes/avatares/gifs_predeterminados/gato_moviendo_ficha.gif">Gato moviendo ficha</option>
            </select>
          </div>
          <!-- Subselect: Campeones de ajedrez -->
          <div id="opciones-campeones-negras" class="subselect-container" style="display:none;">
            <label>Campeón:</label>
            <select name="avatar_campeon_negras" class="select-avatar">
              <option value="public/imagenes/avatares/campeones/magnus_carlsen.jpg">Magnus Carlsen</option>
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
