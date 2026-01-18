<?php

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
                  <form method="post" action="index.php" class="formulario" style="margin:0;padding:0;">
                    <button type="submit" name="seleccionar_casilla" value="<?php echo $posicion; ?>"
                      class="btn-pieza-casilla <?php echo ($pieza->getColor() === $turno) ? 'puede-seleccionar' : 'no-puede-seleccionar'; ?> <?php echo $esCaptura ? 'btn-captura' : ''; ?>"
                      <?php echo (isset($_SESSION['pausa']) && $_SESSION['pausa']) ? 'disabled' : ''; ?>
                      title="Pieza en <?php echo $posicion; ?> - Click para seleccionar">
                      <!-- Mostramos la imagen de la pieza -->
                      <img src="<?php echo obtenerImagenPieza($pieza); ?>" class="imagen-pieza">
                    </button>
                  </form>
                <?php elseif ($esMovimientoPosible): ?>
                  <!-- Si es un movimiento posible, mostramos un indicador visual (círculo verde) -->
                  <form method="post" action="index.php" class="formulario">
                    <button type="submit" name="seleccionar_casilla" value="<?php echo $posicion; ?>"
                      class="btn-movimiento" title="Mover a <?php echo $posicion; ?>">
                      <div class="indicador-movimiento"></div>
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
            <li><strong>Reloj</strong>: Solo corre el reloj del jugador en turno. Si llega a 0:00, pierdes</li>
            <li><strong>Seleccionar pieza</strong>: Haz clic en una pieza para ver movimientos válidos (círculos verdes)</li>
            <li><strong>Movimientos válidos</strong>: Se marcan con círculos verdes. El programa evita movimientos ilegales</li>
            <li><strong>Capturas</strong>: Se marcan con borde rojo pulsante. No puedes capturar tus propias piezas</li>
          </ol>

          <!-- SECCIÓN: Reglas de Ajedrez Avanzadas -->
          <h4 class="titulo-seccion-separado">Reglas de Ajedrez Avanzadas:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>Jaque</strong>: Tu rey está bajo amenaza. Debes hacer un movimiento legal que quite el jaque</li>
            <li><strong>Jaque Mate</strong>: Tu rey está en jaque y NO hay ningún movimiento legal. ¡Pierdes la partida!</li>
            <li><strong>Tablas (Empate)</strong>: Ocurre en dos casos:
              <ul style="margin-top: 8px; margin-left: 20px;">
                <li><strong>Ahogado (Stalemate)</strong>: Tu rey NO está en jaque, pero no tienes ningún movimiento legal</li>
                <li><strong>Material insuficiente</strong>: Solo quedan reyes, o rey+caballo/alfil vs rey (imposible dar jaque mate)</li>
              </ul>
            </li>
            <li><strong>Enroque</strong>: Movimiento especial del rey y la torre (cada uno 1 vez por partida máximo):
              <ul style="margin-top: 8px; margin-left: 20px;">
                <li><strong>Enroque corto (O-O)</strong>: Rey e→g, Torre h→f (lado derecho)</li>
                <li><strong>Enroque largo (O-O-O)</strong>: Rey e→c, Torre a→d (lado izquierdo)</li>
                <li>Requisitos: Rey y torre no han movido, casillas intermedias vacías, rey no en jaque ni pasa por jaque</li>
              </ul>
            </li>
            <li><strong>Captura al Paso</strong>: Si un peón avanza 2 casillas y queda junto a uno enemigo, este puede capturarlo en diagonal. Solo disponible en el turno inmediatamente siguiente</li>
            <li><strong>Promoción de Peón</strong>: Cuando un peón llega a la última fila, se transforma en Dama, Torre, Alfil o Caballo (elegir en modal)</li>
            <li><strong>Bloqueo de piezas</strong>: Torres, alfiles y damas NO pueden saltar. Los caballos SÍ pueden saltar</li>
          </ul>

          <!-- SECCIÓN: Gestión de partida -->
          <h4 class="titulo-seccion-separado">Gestión de partida:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>Deshacer</strong>: Deshace el último movimiento realizado (máximo 10 movimientos)</li>
            <li><strong>Revancha</strong>: Inicia una nueva partida manteniendo los mismos jugadores y configuración</li>
            <li><strong>Guardar partida</strong>: Guarda la partida actual (estado, piezas, tiempo, historial) para continuarla después. Solo disponible en pausa</li>
            <li><strong>Cargar partida</strong>: Carga una partida guardada previamente en formato JSON</li>
            <li><strong>Volver al inicio</strong>: Regresa a la pantalla inicial para configurar una nueva partida</li>
          </ul>

          <!-- SECCIÓN: Avatares y Personalización -->
          <h4 class="titulo-seccion-separado">Avatares y Personalización:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>Avatares predeterminados</strong>: Símbolos del rey (♔/♚)</li>
            <li><strong>Fichas personalizadas</strong>: Imágenes de ajedrez predeterminadas (fichas blancas/negras)</li>
            <li><strong>GIFs personalizados</strong>: Animaciones cortas para los jugadores</li>
            <li><strong>Campeones de Ajedrez</strong>: Imágenes de legendarios campeones (Kasparov, Fischer, etc.)</li>
            <li><strong>Avatar personalizado</strong>: Sube tu propia imagen (JPG, PNG, GIF)</li>
          </ul>

          <!-- SECCIÓN: Configuración -->
          <h4 class="titulo-seccion-separado">Configuración:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>Tiempo inicial</strong>: Elige cuánto tiempo tienen ambos jugadores (minutos)</li>
            <li><strong>Incremento Fischer</strong>: Tiempo adicional ganado por cada movimiento realizado</li>
            <li><strong>Modo sin tiempo</strong>: Juega sin límite de tiempo (si lo activas, ignora tiempo inicial e incremento)</li>
            <li><strong>Número de retrocesos</strong>: Configura cuántos movimientos puedes deshacer (máximo 10)</li>
            <li><strong>Mostrar coordenadas</strong>: Activa/desactiva las letras (A-H) y números (1-8) del tablero</li>
            <li><strong>Mostrar piezas capturadas</strong>: Visualiza las piezas capturadas de cada jugador en paneles laterales</li>
          </ul>

          <!-- SECCIÓN: Notación Algebraica -->
          <h4 class="titulo-seccion-separado">Historial de Movimientos (Notación Algebraica):</h4>
          <ul class="lista-sin-estilo">
            <li><strong>Peón</strong>: Solo destino (ej: e4, d8=D por promoción)</li>
            <li><strong>Otras piezas</strong>: Letra pieza + destino (ej: Nf3=caballo f3, Bd5=alfil d5)</li>
            <li><strong>Capturas</strong>: Letra pieza + 'x' + destino (ej: Nxf3, Bxd5)</li>
            <li><strong>Enroque</strong>: O-O (corto) u O-O-O (largo)</li>
            <li><strong>Jaque</strong>: Se añade '+' al final (ej: Nf3+)</li>
            <li><strong>Jaque Mate</strong>: Se añade '#' al final (ej: Qf7#)</li>
          </ul>

          <!-- SECCIÓN: Puntuación -->
          <h4 class="titulo-seccion-separado">Puntuación:</h4>
          <ul class="lista-sin-estilo">
            <li><strong>Peón</strong>: 1 punto</li>
            <li><strong>Caballo</strong>: 3 puntos</li>
            <li><strong>Alfil</strong>: 3 puntos</li>
            <li><strong>Torre</strong>: 5 puntos</li>
            <li><strong>Dama</strong>: 9 puntos</li>
            <li><strong>Rey</strong>: Sin valor (su captura = fin de partida)</li>
          </ul>
        </div>
      </div>
    <?php
  }
