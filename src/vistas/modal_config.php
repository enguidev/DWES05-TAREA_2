<!-- MODAL DE CONFIGURACIÓN (para personalizar opciones visuales de la partida -->
<div id="modalConfiguracion" class="modal">
  <div class="modal-content">
    <!-- Encabezado del modal con título y botón cerrar -->
    <div class="modal-header">
      <h2>Configuración</h2>
      <!-- Botón X para cerrar el modal (manejado por JavaScript) -->
      <span class="close-modal">&times;</span>
    </div>
    <!-- Formulario para cambiar las configuraciones -->
    <form method="post" class="modal-form">
      <!-- Campo oculto que marca que queremos guardar configuración -->
      <input type="hidden" name="guardar_configuracion" value="1">

      <!-- 1ª SECCIÓN: Opciones de Interfaz Visual -->
      <div class="config-section">
        <h3>Opciones de Interfaz</h3>
        <p class="config-description">Puedes mostrar u ocultar elementos visuales</p>

        <!-- Opción para mostrar/ocultar coordenadas del tablero -->
        <div class="config-option checkbox">
          <label>
            <!-- Casilla de verificación (si estaba marcada, la deja marcada) -->
            <input type="checkbox" name="mostrar_coordenadas" <?php echo $_SESSION['config']['mostrar_coordenadas'] ? 'checked' : ''; ?>>
            Coordenadas del tablero (A-H, 1-8)
          </label>
        </div>

        <!-- Opción para mostrar/ocultar panel de piezas capturadas -->
        <div class="config-option checkbox">
          <label>
            <!-- Casilla de verificación (si estaba marcada, la deja marcada) -->
            <input type="checkbox" name="mostrar_capturas" <?php echo $_SESSION['config']['mostrar_capturas'] ? 'checked' : ''; ?>>
            Panel de piezas capturadas
          </label>
        </div>
      </div>

      <!-- 2ª SECCIÓN: Opciones de Juego -->
      <div class="config-section">
        <h3>Opciones de Juego</h3>
        <p class="config-description">Configura el modo y los límites de la partida</p>

        <!-- Opción para jugar sin tiempo -->
        <div class="config-option checkbox">
          <label>
            <input type="checkbox" name="sin_tiempo" <?php echo isset($_SESSION['config']['sin_tiempo']) && $_SESSION['config']['sin_tiempo'] ? 'checked' : ''; ?>>
            Modo sin tiempo (ignora tiempo inicial e incremento)
          </label>
          <small class="config-note">Si activas esto, la partida no tendrá límite de tiempo</small>
        </div>

        <!-- Número de retrocesos permitidos -->
        <div class="config-option">
          <label for="num_retrocesos">
            <strong>🔙 Número máximo de retrocesos (Deshacer):</strong>
          </label>
          <div style="display: flex; align-items: center; gap: 12px; margin-top: 12px;">
            <input type="number" id="num_retrocesos" name="num_retrocesos" min="0" max="20" step="1"
              value="<?php echo isset($_SESSION['config']['num_retrocesos']) ? $_SESSION['config']['num_retrocesos'] : '10'; ?>"
              style="width: 110px; padding: 6px 10px;">
            <div style="display: flex; align-items: center; gap: 8px; background: #5568d3; color: white; padding: 6px 12px; border-radius: 6px; font-weight: bold;">
              <span id="num_retrocesos_valor" style="min-width: 20px; text-align: center;">
                <?php echo isset($_SESSION['config']['num_retrocesos']) ? $_SESSION['config']['num_retrocesos'] : '10'; ?>
              </span>
              <span style="font-size: 0.9em;">movimientos</span>
            </div>
          </div>
          <small class="config-note">Usa las flechas (0–20). Default: 10 movimientos</small>
        </div>

        <!-- Auto-guardar todas las partidas -->
        <div class="config-option checkbox">
          <label>
            <input type="checkbox" name="auto_guardar_partidas" <?php echo !empty($_SESSION['config']['auto_guardar_partidas']) ? 'checked' : ''; ?>>
            💾 Guardar automáticamente todas las partidas
          </label>
          <small class="config-note">Se guarda tras cada jugada para poder reproducirla o estudiarla después</small>
        </div>
      </div>

      <!-- 3ª SECCIÓN: Información de Tiempo (informativo, no editable) -->
      <div class="config-info">
        <h3>⏱️ Información del Tiempo</h3>
        <!-- Mostramos el tiempo inicial configurado en la partida actual -->
        <p>
          <strong>Tiempo inicial:</strong>
          <?php

          // Convertimos segundos a minutos y mostramos la unidad correcta (minuto/minutos)
          $mins = $_SESSION['config']['tiempo_inicial'] / 60; // Convertimos a minutos

          echo $mins . ' minuto' . ($mins != 1 ? 's' : ''); // Mostramos con plural si es necesario
          ?>
        </p>

        <!-- Mostramos el incremento Fischer (tiempo extra por movimiento) -->
        <p>
          <strong>➕Incremento Fischer:</strong>
          <?php
          // Si hay incremento lo mostramos, si no decimos "Sin incremento"
          echo $_SESSION['config']['incremento'] > 0
            ? '+' . $_SESSION['config']['incremento'] . ' segundo' . ($_SESSION['config']['incremento'] != 1 ? 's' : '')
            : 'Sin incremento';
          ?>
        </p>

        <!-- Nota informativa sobre que el tiempo no se puede cambiar durante la partida -->
        <small class="config-note">
          ℹ️El tiempo y el incremento no se pueden cambiar durante la partida
        </small>
      </div>

      <!-- 3ª SECCIÓN: Botones de acción -->
      <div class="modal-buttons">
        <!-- Botón para guardar los cambios realizados en la configuración -->
        <button type="submit" name="guardar_configuracion" class="btn-guardar-config">💾 Guardar Cambios</button>
        <!-- Botón para cerrar sin guardar cambios -->
        <button type="button" class="btn-cancelar-config">❌ Cancelar</button>
      </div>
    </form>
  </div>
</div>