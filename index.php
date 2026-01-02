<?php
session_start();

require_once 'modelo/Partida.php';

// Procesar configuración de nombres de jugadores
if (isset($_POST['iniciar_partida'])) {
  $nombreBlancas = !empty($_POST['nombre_blancas']) ? htmlspecialchars(trim($_POST['nombre_blancas'])) : "Jugador 1";
  $nombreNegras = !empty($_POST['nombre_negras']) ? htmlspecialchars(trim($_POST['nombre_negras'])) : "Jugador 2";

  $_SESSION['partida'] = serialize(new Partida($nombreBlancas, $nombreNegras));
  $_SESSION['casilla_seleccionada'] = null;
  $_SESSION['tiempo_restante'] = 600;
  $_SESSION['nombres_configurados'] = true;
}

// Inicializar o recuperar la partida
if (!isset($_SESSION['partida']) || isset($_POST['reiniciar'])) {
  // Si se presiona reiniciar, volver a la pantalla de configuración
  if (isset($_POST['reiniciar'])) {
    unset($_SESSION['partida']);
    unset($_SESSION['casilla_seleccionada']);
    unset($_SESSION['tiempo_restante']);
    unset($_SESSION['nombres_configurados']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }

  // Si no hay nombres configurados, mostrar formulario
  if (!isset($_SESSION['nombres_configurados'])) {
    // Mostrar pantalla de configuración (ver más abajo en el HTML)
  } else {
    $_SESSION['partida'] = serialize(new Partida("Jugador 1", "Jugador 2"));
    $_SESSION['casilla_seleccionada'] = null;
    $_SESSION['tiempo_restante'] = 600;
  }
}

// Solo continuar si hay una partida iniciada
if (isset($_SESSION['partida'])) {
  $partida = unserialize($_SESSION['partida']);

  // Procesar jugada
  if (isset($_POST['seleccionar_casilla'])) {
    $casilla = $_POST['seleccionar_casilla'];

    if ($_SESSION['casilla_seleccionada'] === null) {
      // Verificar que la pieza seleccionada sea del turno actual
      $piezaSeleccionada = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaSeleccionada && $piezaSeleccionada->getColor() === $partida->getTurno()) {
        $_SESSION['casilla_seleccionada'] = $casilla;
      }
    } else {
      // Si se hace clic en una pieza propia, cambiar la selección
      $piezaClickeada = obtenerPiezaEnCasilla($casilla, $partida);
      if ($piezaClickeada && $piezaClickeada->getColor() === $partida->getTurno()) {
        $_SESSION['casilla_seleccionada'] = $casilla;
      } else {
        // Intentar mover la pieza seleccionada
        $origen = $_SESSION['casilla_seleccionada'];
        $destino = $casilla;

        $partida->jugada($origen, $destino);
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
      'casilla_seleccionada' => $_SESSION['casilla_seleccionada']
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

  // Obtener posibilidades de reglas avanzadas
  $enroqueCorto = $partida->puedeEnrocarCorto($turno);
  $enroqueLargo = $partida->puedeEnrocarLargo($turno);
  $capturaAlPaso = $partida->capturaAlPasoDisponible($turno);
  $esTablas = $partida->esTablas();

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

  /**
   * Obtiene la ruta de la imagen de una pieza
   */
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

  /**
   * Obtiene la pieza en una posición del tablero
   */
  function obtenerPiezaEnCasilla($posicion, $partida)
  {
    $jugadores = $partida->getJugadores();

    $pieza = $jugadores['blancas']->getPiezaEnPosicion($posicion);
    if ($pieza) return $pieza;

    $pieza = $jugadores['negras']->getPiezaEnPosicion($posicion);
    if ($pieza) return $pieza;

    return null;
  }

  // Cerrar el if de partida iniciada
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
    <!-- PANTALLA DE CONFIGURACIÓN DE NOMBRES -->
    <div class="container">
      <h1>🎮 Configurar Partida de Ajedrez</h1>

      <div class="config-wrapper">
        <p class="config-intro">Antes de comenzar, introduce los nombres de los jugadores:</p>

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
    <!-- PANTALLA DE JUEGO NORMAL -->
    <div class="container">
      <h1>Partida de Ajedrez</h1>

      <div class="mensaje <?php echo $partida->estaTerminada() ? 'terminada' : ''; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
      </div>

      <div class="marcador-y-capturas">
        <div class="marcador">
          <div class="marcador-item blancas <?php echo $turno === 'blancas' ? 'turno-activo' : ''; ?>">
            <div class="nombre-jugador">⚪ <?php echo $jugadores['blancas']->getNombre(); ?></div>
            <div class="puntos-jugador"><?php echo $marcador[0]; ?> puntos</div>
            <?php if ($turno === 'blancas'): ?>
              <div class="indicador-turno">👈 Tu turno</div>
            <?php endif; ?>
          </div>
          <div class="marcador-item negras <?php echo $turno === 'negras' ? 'turno-activo' : ''; ?>">
            <div class="nombre-jugador">⚫ <?php echo $jugadores['negras']->getNombre(); ?></div>
            <div class="puntos-jugador"><?php echo $marcador[1]; ?> puntos</div>
            <?php if ($turno === 'negras'): ?>
              <div class="indicador-turno">👈 Tu turno</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- TABLERO CON PIEZAS CAPTURADAS A LOS LADOS -->
      <div class="tablero-y-capturas-wrapper">
        <!-- Piezas capturadas del lado izquierdo (BLANCAS capturadas por NEGRAS) -->
        <div class="piezas-capturadas-lado">
          <h3>Capturadas por negras:</h3>
          <div class="capturadas-vertical">
            <?php foreach ($piezasCapturadas['blancas'] as $pieza): ?>
              <img src="<?php echo obtenerImagenPieza($pieza); ?>" alt="Pieza capturada" class="pieza-capturada">
            <?php endforeach; ?>
          </div>
        </div>

        <!-- TABLERO DE AJEDREZ -->
        <div class="tablero-wrapper">
          <div class="tablero-contenedor">
            <?php
            $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

            // Esquina superior izquierda
            echo '<div class="coordenada-esquina-superior-izquierda"></div>';

            // Coordenadas superiores (letras)
            foreach ($letras as $letra) {
              echo '<div class="coordenada-superior">' . $letra . '</div>';
            }

            echo '<div class="coordenada-esquina-superior-derecha"></div>';

            // Generar filas del tablero (8 a 1)
            for ($fila = 8; $fila >= 1; $fila--):
              $numeroFila = $fila;
              echo '<div class="coordenada-izquierda">' . $numeroFila . '</div>';

              // Generar columnas (A-H)
              for ($columna = 0; $columna < 8; $columna++):
                $posicion = $letras[$columna] . $fila;
                $pieza = obtenerPiezaEnCasilla($posicion, $partida);

                // Determinar color de la casilla
                $colorCasilla = (($fila + $columna) % 2 === 0) ? 'blanca' : 'negra';

                // Verificar si está seleccionada
                $esSeleccionada = ($casillaSeleccionada === $posicion);

                // Verificar si es un movimiento posible
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

              <div class="coordenada-derecha"><?php echo $numeroFila; ?></div>
            <?php endfor; ?>

            <div class="coordenada-esquina-inferior-izquierda"></div>

            <?php foreach ($letras as $letra): ?>
              <div class="coordenada-inferior"><?php echo $letra; ?></div>
            <?php endforeach; ?>

            <div class="coordenada-esquina-inferior-derecha"></div>
          </div>
        </div>

        <!-- Piezas capturadas del lado derecho (NEGRAS capturadas por BLANCAS) -->
        <div class="piezas-capturadas-lado">
          <h3>Capturadas por blancas:</h3>
          <div class="capturadas-vertical">
            <?php foreach ($piezasCapturadas['negras'] as $pieza): ?>
              <img src="<?php echo obtenerImagenPieza($pieza); ?>" alt="Pieza capturada" class="pieza-capturada">
            <?php endforeach; ?>
          </div>
        </div>

      </div> <!-- Cierre de tablero-y-capturas-wrapper -->

      <div class="temporizador">
        <h3>Tiempo restante: <span id="tiempo">10:00</span></h3>
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
          <li>Haz clic en una pieza del color del turno actual (resaltado con borde dorado)</li>
          <li>Los círculos verdes indican casillas vacías donde puedes mover</li>
          <li>El borde rojo pulsante indica piezas enemigas que puedes capturar</li>
          <li>Haz clic en otra pieza propia para cambiar de selección</li>
          <li>Captura el rey enemigo para ganar la partida</li>
        </ol>
      </div>
    </div>
    <script>
      // Recuperar el tiempo de la sesión o iniciar en 600
      let tiempoRestante = <?php echo isset($_SESSION['tiempo_restante']) ? $_SESSION['tiempo_restante'] : 600; ?>;
      const tiempoElement = document.getElementById('tiempo');

      function actualizarTemporizador() {
        const minutos = Math.floor(tiempoRestante / 60);
        const segundos = tiempoRestante % 60;
        tiempoElement.textContent = `${minutos}:${segundos.toString().padStart(2, '0')}`;
        if (tiempoRestante > 0) {
          tiempoRestante--;
          // Guardar en localStorage para persistencia
          localStorage.setItem('tiempoRestante', tiempoRestante);
        } else {
          alert('Tiempo agotado!');
        }
      }

      // Intentar recuperar de localStorage si existe
      const tiempoGuardado = localStorage.getItem('tiempoRestante');
      if (tiempoGuardado && tiempoGuardado > 0) {
        tiempoRestante = parseInt(tiempoGuardado);
      }

      setInterval(actualizarTemporizador, 1000);
    </script>
  <?php endif; ?>
</body>

</html>