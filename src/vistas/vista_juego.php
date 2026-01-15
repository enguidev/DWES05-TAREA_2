<?php

// Para mostrar la cabecera del juego con título y botones de pausa/configuración
function mostrarCabeceraJuego($partida)
{
?>
  <div class="header-juego">
    <h1><img src="public/imagenes/fichas_negras/rey_negra.png" alt="Rey negro" style="width: 100px; height: 100px; vertical-align: middle; transform: translateY(-2px); margin-right: 12px; background: transparent; border-radius: 8px;"> Partida de Ajedrez</h1>
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
      <button type="submit" name="abrir_modal_reiniciar" class="btn-reiniciar" id="btn-reiniciar">🏠 Volver al inicio</button>
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
