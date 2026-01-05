<?php
/**
 * Modal de configuración de la partida de ajedrez
 */
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