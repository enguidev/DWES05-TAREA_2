<?php

// Para cargar el modal de configuración desde un archivo aparte
function mostrarModalConfig()
{
  // Incluimos el archivo con el HTML del modal
  include 'src/vistas/modal_config.php';
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
      <h2>🏠 Volver al inicio</h2>
      <p>¿Deseas volver al inicio? Perderás el progreso de la partida actual.</p>
      <!-- Advertencia para que el usuario sepa que es irreversible -->
      <p class="texto-advertencia">Esta acción no se puede deshacer.</p>
      <div class="modal-buttons">
        <form method="post" style="display: inline;">
          <!-- Botón para confirmar el reinicio -->
          <button type="submit" name="confirmar_reiniciar" class="btn-confirmar btn-reiniciar-confirm">✅ Sí, volver al inicio</button>
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
