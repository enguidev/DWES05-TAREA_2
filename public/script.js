// ========================================
// CONFIGURACIÓN DEL MODAL DE AJUSTES
// ========================================
// Obtenemos los elementos principales del modal de configuración
const modal = document.getElementById("modalConfiguracion");
const btnConfiguracion = document.getElementById("btnConfiguracion");
const closeModal = document.querySelector(".close-modal");
const btnCancelar = document.querySelector(".btn-cancelar-config");

// Si todos los elementos existen, configuramos el comportamiento del modal
if (modal && btnConfiguracion && closeModal && btnCancelar) {
  // Función para reanudar la partida después de cerrar los ajustes
  function reanudarDesdeModal() {
    // Enviamos una solicitud al servidor para reanudar la partida
    fetch("index.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ reanudar_desde_configuracion: "1" }),
    })
      .then(() => {
        // Marcamos localmente que no estamos en pausa
        pausaLocal = false;
        // Reseteamos el contador de sincronización
        contadorSincronizacion = 0;
        // Sincronizamos inmediatamente con el servidor para obtener los tiempos correctos
        return fetch("index.php?ajax=actualizar_relojes");
      })
      .then((r) => r.json())
      .then((data) => {
        // Si hay partida activa y no está terminada, actualizamos los tiempos locales
        if (!data.sin_partida && !data.partida_terminada) {
          tiempoLocalBlancas = data.tiempo_blancas;
          tiempoLocalNegras = data.tiempo_negras;
          relojActivoLocal = data.reloj_activo;
        }
        // Actualizamos los relojes en la pantalla
        actualizarDisplayRelojes();
        // Quitamos el mensaje de pausa del DOM
        const msgDiv = document.querySelector(".mensaje");
        if (msgDiv) {
          msgDiv.classList.remove("pausa");
          msgDiv.classList.remove("terminada");
          msgDiv.textContent = "";
        }
      })
      .catch((e) => console.error("No se pudo reanudar desde ajustes:", e));
  }

  // Cuando se abre el modal de configuración
  btnConfiguracion.onclick = () => {
    // Mostramos el modal
    modal.style.display = "block";
    // Marcamos localmente que estamos en pausa
    pausaLocal = true;

    // Pausamos la partida en el servidor para que no corra el reloj
    fetch("index.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ pausar_desde_configuracion: "1" }),
    })
      .then(() => {
        // Confirmamos la pausa local
        pausaLocal = true;
        // Actualizamos el display de relojes
        actualizarDisplayRelojes();
        // Mostramos el mensaje de pausa en naranja
        const msgDiv = document.querySelector(".mensaje");
        if (msgDiv) {
          msgDiv.textContent = "\u23F8\uFE0F PARTIDA EN PAUSA";
          msgDiv.classList.remove("terminada");
          msgDiv.classList.add("pausa");
        }
      })
      .catch((e) => console.error("No se pudo pausar al abrir ajustes:", e));
  };

  // Cuando se hace clic en la X del modal
  closeModal.onclick = () => {
    modal.style.display = "none";
    // Reanudamos la partida
    reanudarDesdeModal();
  };

  // Cuando se hace clic en el botón Cancelar
  btnCancelar.onclick = () => {
    modal.style.display = "none";
    // Reanudamos la partida
    reanudarDesdeModal();
  };

  // Cuando se hace clic fuera del modal (en el overlay)
  window.onclick = (e) => {
    if (e.target == modal) {
      modal.style.display = "none";
      // Reanudamos la partida
      reanudarDesdeModal();
    }
  };
}

// ========================================
// CONTROL DEL BOTÓN GUARDAR CONFIGURACIÓN
// ========================================
// Obtenemos los elementos del formulario de configuración
const formConfig = modal ? modal.querySelector("form") : null;
const btnGuardarConfig = modal
  ? modal.querySelector(".btn-guardar-config")
  : null;
const chkCoords = modal
  ? modal.querySelector('input[name="mostrar_coordenadas"]')
  : null;
const chkCapturas = modal
  ? modal.querySelector('input[name="mostrar_capturas"]')
  : null;

// Si el formulario existe, controlamos que el botón guardar solo se active si hay cambios
if (formConfig && btnGuardarConfig && chkCoords && chkCapturas) {
  // Guardamos el estado inicial de los checkboxes
  const estadoInicial = {
    coords: chkCoords.checked,
    capturas: chkCapturas.checked,
  };

  // Función para actualizar el estado del botón guardar
  const actualizarEstadoGuardar = () => {
    // Verificamos si algo cambió comparando con el estado inicial
    const cambiado =
      chkCoords.checked !== estadoInicial.coords ||
      chkCapturas.checked !== estadoInicial.capturas;
    // Si nada cambió, deshabilitamos el botón
    btnGuardarConfig.disabled = !cambiado;
    btnGuardarConfig.classList.toggle("btn-disabled", !cambiado);
  };

  // Cuando se hace clic en guardar cambios
  btnGuardarConfig.addEventListener("click", (e) => {
    // Solo permitir si el botón está habilitado
    if (
      !btnGuardarConfig.disabled &&
      !btnGuardarConfig.classList.contains("btn-disabled")
    ) {
      e.preventDefault();

      // Creamos un campo oculto para reanudar desde config
      let inputReanudar = formConfig.querySelector(
        'input[name="reanudar_desde_configuracion"]'
      );
      if (!inputReanudar) {
        inputReanudar = document.createElement("input");
        inputReanudar.type = "hidden";
        inputReanudar.name = "reanudar_desde_configuracion";
        inputReanudar.value = "1";
        formConfig.appendChild(inputReanudar);
      }

      // Enviamos el formulario con los cambios
      formConfig.submit();
    }
  });

  // Cuando cambian los checkboxes, actualizamos el estado del botón
  chkCoords.addEventListener("change", actualizarEstadoGuardar);
  chkCapturas.addEventListener("change", actualizarEstadoGuardar);
  // Actualizamos el estado inicial
  actualizarEstadoGuardar();
}

// ========================================
// SISTEMA DE ACTUALIZACIÓN DE RELOJES
// ========================================
// Variables locales para controlar los relojes sin depender de AJAX cada 100ms
let intervaloRelojes = null; // Intervalo para actualizar relojes cada segundo
let tiempoLocalBlancas = 0; // Segundos restantes para blancas
let tiempoLocalNegras = 0; // Segundos restantes para negras
let relojActivoLocal = "blancas"; // Quién está jugando ahora
let pausaLocal = false; // Si la partida está en pausa
let contadorSincronizacion = 0; // Contador para sincronizar cada 5 segundos
let recargandoPagina = false; // Flag para evitar múltiples recargas cuando se agota el tiempo

// Función para formatear segundos a MM:SS
function formatearTiempo(segundos) {
  return (
    String(Math.floor(segundos / 60)).padStart(2, "0") +
    ":" +
    String(segundos % 60).padStart(2, "0")
  );
}

// Actualizar lo que se ve en el HTML de los relojes
function actualizarDisplayRelojes() {
  // Obtenemos los elementos donde se muestran los tiempos
  const tb = document.getElementById("tiempo-blancas");
  const tn = document.getElementById("tiempo-negras");

  // Si los elementos no existen, salimos
  if (!tb || !tn) return;

  // Mostramos los tiempos formateados
  tb.textContent = formatearTiempo(tiempoLocalBlancas);
  tn.textContent = formatearTiempo(tiempoLocalNegras);

  // Resaltamos en rojo SOLO el reloj del jugador que está jugando si le quedan menos de 60 segundos
  if (relojActivoLocal === "blancas") {
    tiempoLocalBlancas < 60
      ? tb.classList.add("tiempo-critico")
      : tb.classList.remove("tiempo-critico");
    tn.classList.remove("tiempo-critico");
  } else {
    tiempoLocalNegras < 60
      ? tn.classList.add("tiempo-critico")
      : tn.classList.remove("tiempo-critico");
    tb.classList.remove("tiempo-critico");
  }

  // Actualizamos los estilos de reloj activo/inactivo en todos los relojes
  document.querySelectorAll(".reloj").forEach((r) => {
    if (r.classList.contains("reloj-blancas")) {
      // Si es el reloj de blancas y blancas está jugando
      relojActivoLocal === "blancas"
        ? (r.classList.add("reloj-activo"),
          r.classList.remove("reloj-inactivo"))
        : (r.classList.remove("reloj-activo"),
          r.classList.add("reloj-inactivo"));
    } else if (r.classList.contains("reloj-negras")) {
      // Si es el reloj de negras y negras está jugando
      relojActivoLocal === "negras"
        ? (r.classList.add("reloj-activo"),
          r.classList.remove("reloj-inactivo"))
        : (r.classList.remove("reloj-activo"),
          r.classList.add("reloj-inactivo"));
    }
  });
}

// Función que se ejecuta cada segundo para decrementar el reloj
function actualizarTiempoLocal() {
  // Si ya estamos recargando la página, no hacer nada
  if (recargandoPagina) return;

  // Verificamos que existen los elementos del reloj
  if (
    !document.getElementById("tiempo-blancas") ||
    !document.getElementById("tiempo-negras")
  ) {
    return;
  }

  // Si no está en pausa, decrementamos el reloj del jugador actual
  if (!pausaLocal) {
    if (relojActivoLocal === "blancas" && tiempoLocalBlancas > 0) {
      tiempoLocalBlancas--;
    } else if (relojActivoLocal === "negras" && tiempoLocalNegras > 0) {
      tiempoLocalNegras--;
    }

    // Si se agotó el tiempo, recargamos la página SOLO UNA VEZ
    if (
      (tiempoLocalBlancas <= 0 || tiempoLocalNegras <= 0) &&
      !recargandoPagina
    ) {
      recargandoPagina = true;
      // Detenemos el intervalo de actualización
      clearInterval(intervaloRelojes);
      intervaloRelojes = null;
      // Recargamos la página para mostrar el resultado
      location.reload();
      return;
    }
  }

  // Actualizamos lo que se ve en pantalla
  actualizarDisplayRelojes();

  // Cada 5 segundos sincronizamos con el servidor para verificar que los tiempos sean correctos
  contadorSincronizacion++;
  if (contadorSincronizacion >= 5) {
    sincronizarConServidor();
    contadorSincronizacion = 0;
  }
}

// Función para sincronizar los tiempos con el servidor
function sincronizarConServidor() {
  // Si ya estamos recargando, no sincronizar
  if (recargandoPagina) return;

  // Solicitamos los tiempos actuales al servidor
  fetch("index.php?ajax=actualizar_relojes")
    .then((r) => {
      if (!r.ok) throw new Error("Error en respuesta HTTP: " + r.status);
      return r.json();
    })
    .then((data) => {
      // Si ya estamos recargando, ignorar los datos
      if (recargandoPagina) return;

      // Si no hay partida, salimos
      if (data.sin_partida) return;

      // Si la partida terminó, detenemos todo sin recargar
      if (data.partida_terminada) {
        if (intervaloRelojes !== null) {
          clearInterval(intervaloRelojes);
          intervaloRelojes = null;
        }
        return;
      }

      // Si el servidor nos envía tiempos válidos
      if (
        data.tiempo_blancas !== undefined &&
        data.tiempo_negras !== undefined
      ) {
        // Actualizamos nuestros tiempos locales con los del servidor
        tiempoLocalBlancas = data.tiempo_blancas;
        tiempoLocalNegras = data.tiempo_negras;
        relojActivoLocal = data.reloj_activo;
        pausaLocal = data.pausa || false;

        // Log para debugging si la partida está en pausa
        if (data.pausa) {
          console.warn(
            "⚠️ LA PARTIDA ESTÁ EN PAUSA - Los movimientos están bloqueados"
          );
        }

        // Actualizamos la pantalla
        actualizarDisplayRelojes();

        // Si el tiempo se agotó desde el servidor, detenemos el intervalo
        if (data.tiempo_blancas <= 0 || data.tiempo_negras <= 0) {
          if (intervaloRelojes !== null) {
            clearInterval(intervaloRelojes);
            intervaloRelojes = null;
          }
        }
      }
    })
    .catch((e) => {
      console.error("Error al sincronizar relojes:", e);
    });
}

// ========================================
// INICIALIZACIÓN AL CARGAR LA PÁGINA
// ========================================
// Manejar avatares personalizados y funciones adicionales
document.addEventListener("DOMContentLoaded", function () {
  // Reseteamos el flag de recarga cuando la página se carga
  recargandoPagina = false;

  // Si no hay un intervalo de relojes activo, lo iniciamos
  if (!intervaloRelojes && document.getElementById("tiempo-blancas")) {
    // Primero sincronizamos con el servidor para verificar el estado de la partida
    fetch("index.php?ajax=actualizar_relojes")
      .then((r) => r.json())
      .then((data) => {
        // Si no hay partida o ya terminó, no iniciamos los relojes
        if (data.sin_partida || data.partida_terminada) {
          return;
        }
        // Si hay partida activa, inicializamos las variables locales
        tiempoLocalBlancas = data.tiempo_blancas;
        tiempoLocalNegras = data.tiempo_negras;
        relojActivoLocal = data.reloj_activo;
        pausaLocal = data.pausa || false;
        // Actualizamos la pantalla
        actualizarDisplayRelojes();
        // Iniciamos el intervalo para actualizar cada segundo
        intervaloRelojes = setInterval(actualizarTiempoLocal, 1000);
      })
      .catch((e) => console.error("Error al inicializar relojes:", e));
  }

  // ========================================
  // GESTIÓN DE AVATARES PERSONALIZADOS
  // ========================================
  // Obtenemos los selectores de avatar (nueva estructura)
  const tipoBlancas = document.querySelector(
    'select[name="tipo_avatar_blancas"]'
  );
  const tipoNegras = document.querySelector(
    'select[name="tipo_avatar_negras"]'
  );
  const fichaBlancas = document.querySelector(
    'select[name="avatar_ficha_blancas"]'
  );
  const fichaNegras = document.querySelector(
    'select[name="avatar_ficha_negras"]'
  );
  const gifBlancas = document.querySelector(
    'select[name="avatar_gif_blancas"]'
  );
  const gifNegras = document.querySelector('select[name="avatar_gif_negras"]');
  const hiddenBlancas = document.getElementById("avatar_blancas_hidden");
  const hiddenNegras = document.getElementById("avatar_negras_hidden");
  const inputBlancas = document.getElementById("avatar_personalizado_blancas");
  const inputNegras = document.getElementById("avatar_personalizado_negras");

  // Función para validar que el archivo sea una imagen válida
  function validarArchivo(file) {
    // Solo permitimos JPEG, PNG y GIF
    const allowedTypes = ["image/jpeg", "image/png", "image/gif"];
    const maxSize = 2 * 1024 * 1024; // Máximo 2MB

    // Verificamos el tipo de archivo
    if (!allowedTypes.includes(file.type)) {
      alert("Solo se permiten archivos de imagen (JPEG, PNG, GIF)");
      return false;
    }

    // Verificamos el tamaño
    if (file.size > maxSize) {
      alert("El archivo es demasiado grande. Máximo 2MB.");
      return false;
    }

    return true;
  }

  // Función para mostrar una previsualización de la imagen seleccionada en el avatar display
  function mostrarPrevisualizacionAvatar(input, avatarDisplayId) {
    const file = input.files[0];
    // Si hay archivo y es válido
    if (file && validarArchivo(file)) {
      // Leemos el archivo como URL de datos
      const reader = new FileReader();
      reader.onload = function (e) {
        // Obtenemos el elemento donde mostraremos el avatar
        const avatarDisplay = document.getElementById(avatarDisplayId);
        if (avatarDisplay) {
          // Limpiamos el contenido anterior
          avatarDisplay.innerHTML = "";
          // Creamos la imagen
          const img = document.createElement("img");
          img.src = e.target.result;
          img.style.width = "100%";
          img.style.height = "100%";
          img.style.borderRadius = "50%";
          img.style.objectFit = "cover";
          img.style.border = "3px solid #5568d3";
          img.style.boxShadow = "0 4px 10px rgba(85, 104, 211, 0.3)";
          // La añadimos al avatar display
          avatarDisplay.appendChild(img);
        }
      };
      // Leemos el archivo
      reader.readAsDataURL(file);
    }
  }

  // Función para actualizar el avatar display con una imagen predefinida
  function actualizarAvatarDisplay(rutaImagen, avatarDisplayId) {
    const avatarDisplay = document.getElementById(avatarDisplayId);
    if (avatarDisplay && rutaImagen && rutaImagen !== "predeterminado") {
      // Limpiamos el contenido anterior
      avatarDisplay.innerHTML = "";
      // Creamos la imagen
      const img = document.createElement("img");
      img.src = rutaImagen;
      // Fallback: si la imagen no carga, restaurar símbolo por defecto
      img.onerror = () => {
        avatarDisplay.innerHTML =
          avatarDisplayId === "avatar-display-blancas" ? "♔" : "♚";
      };
      img.style.width = "100%";
      img.style.height = "100%";
      img.style.borderRadius = "50%";
      img.style.objectFit = "cover";
      img.style.border = "3px solid #5568d3";
      img.style.boxShadow = "0 4px 10px rgba(85, 104, 211, 0.3)";
      // La añadimos al avatar display
      avatarDisplay.appendChild(img);
    } else if (
      avatarDisplay &&
      (!rutaImagen || rutaImagen === "predeterminado")
    ) {
      // Si se selecciona "Sin avatar", volvemos al símbolo
      const esBlancas = avatarDisplayId === "avatar-display-blancas";
      avatarDisplay.innerHTML = esBlancas ? "♔" : "♚";
      avatarDisplay.style.display = "";
    }
  }

  // MANEJO DE AVATARES DEL JUGADOR BLANCO (nueva estructura)
  if (tipoBlancas && inputBlancas && hiddenBlancas) {
    const contenedorBlancas = document.getElementById(
      "contenedor-personalizado-blancas"
    );
    const nombreArchivoBlancas = document.getElementById(
      "nombre-archivo-blancas"
    );
    const contFichaBlancas = document.getElementById("opciones-ficha-blancas");
    const contGifBlancas = document.getElementById("opciones-gif-blancas");
    const contCampeonesBlancas = document.getElementById(
      "opciones-campeones-blancas"
    );

    function setAvatarBlancas(valor) {
      hiddenBlancas.value = valor || "predeterminado";
      actualizarAvatarDisplay(valor, "avatar-display-blancas");
    }

    tipoBlancas.addEventListener("change", function () {
      const v = this.value;
      // Reset visibilidad
      if (contFichaBlancas) contFichaBlancas.style.display = "none";
      if (contGifBlancas) contGifBlancas.style.display = "none";
      if (contCampeonesBlancas) contCampeonesBlancas.style.display = "none";
      if (contenedorBlancas) contenedorBlancas.style.display = "none";

      if (v === "predeterminado") {
        setAvatarBlancas("predeterminado");
      } else if (v === "usuario") {
        setAvatarBlancas("public/imagenes/avatares/user_white.png");
      } else if (v === "ficha") {
        if (contFichaBlancas) contFichaBlancas.style.display = "block";
        if (fichaBlancas) {
          setAvatarBlancas(fichaBlancas.value);
        }
      } else if (v === "gif") {
        if (contGifBlancas) contGifBlancas.style.display = "block";
        if (gifBlancas) {
          setAvatarBlancas(gifBlancas.value);
        }
      } else if (v === "campeones") {
        if (contCampeonesBlancas) contCampeonesBlancas.style.display = "block";
        const campeonBlancas = document.querySelector(
          'select[name="avatar_campeon_blancas"]'
        );
        if (campeonBlancas) {
          setAvatarBlancas(campeonBlancas.value);
        }
      } else if (v === "personalizado") {
        // Mostrar input de archivo y marcar como personalizado
        if (contenedorBlancas) contenedorBlancas.style.display = "block";
        hiddenBlancas.value = "personalizado";
      }
    });

    if (fichaBlancas) {
      fichaBlancas.addEventListener("change", function () {
        setAvatarBlancas(this.value);
      });
    }
    if (gifBlancas) {
      gifBlancas.addEventListener("change", function () {
        setAvatarBlancas(this.value);
      });
    }
    const campeonBlancas = document.querySelector(
      'select[name="avatar_campeon_blancas"]'
    );
    if (campeonBlancas) {
      campeonBlancas.addEventListener("change", function () {
        setAvatarBlancas(this.value);
      });
    }

    inputBlancas.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        if (nombreArchivoBlancas)
          nombreArchivoBlancas.textContent = this.files[0].name;
        mostrarPrevisualizacionAvatar(this, "avatar-display-blancas");
      }
    });
  }

  // MANEJO DE AVATARES DEL JUGADOR NEGRO (nueva estructura)
  if (tipoNegras && inputNegras && hiddenNegras) {
    const contenedorNegras = document.getElementById(
      "contenedor-personalizado-negras"
    );
    const nombreArchivoNegras = document.getElementById(
      "nombre-archivo-negras"
    );
    const contFichaNegras = document.getElementById("opciones-ficha-negras");
    const contGifNegras = document.getElementById("opciones-gif-negras");
    const contCampeonesNegras = document.getElementById(
      "opciones-campeones-negras"
    );

    function setAvatarNegras(valor) {
      hiddenNegras.value = valor || "predeterminado";
      actualizarAvatarDisplay(valor, "avatar-display-negras");
    }

    tipoNegras.addEventListener("change", function () {
      const v = this.value;
      if (contFichaNegras) contFichaNegras.style.display = "none";
      if (contGifNegras) contGifNegras.style.display = "none";
      if (contCampeonesNegras) contCampeonesNegras.style.display = "none";
      if (contenedorNegras) contenedorNegras.style.display = "none";

      if (v === "predeterminado") {
        setAvatarNegras("predeterminado");
      } else if (v === "usuario") {
        setAvatarNegras("public/imagenes/avatares/user_black.png");
      } else if (v === "ficha") {
        if (contFichaNegras) contFichaNegras.style.display = "block";
        if (fichaNegras) setAvatarNegras(fichaNegras.value);
      } else if (v === "gif") {
        if (contGifNegras) contGifNegras.style.display = "block";
        if (gifNegras) setAvatarNegras(gifNegras.value);
      } else if (v === "campeones") {
        if (contCampeonesNegras) contCampeonesNegras.style.display = "block";
        const campeonNegras = document.querySelector(
          'select[name="avatar_campeon_negras"]'
        );
        if (campeonNegras) {
          setAvatarNegras(campeonNegras.value);
        }
      } else if (v === "personalizado") {
        if (contenedorNegras) contenedorNegras.style.display = "block";
        hiddenNegras.value = "personalizado";
      }
    });

    if (fichaNegras)
      fichaNegras.addEventListener("change", function () {
        setAvatarNegras(this.value);
      });
    if (gifNegras)
      gifNegras.addEventListener("change", function () {
        setAvatarNegras(this.value);
      });
    const campeonNegras = document.querySelector(
      'select[name="avatar_campeon_negras"]'
    );
    if (campeonNegras) {
      campeonNegras.addEventListener("change", function () {
        setAvatarNegras(this.value);
      });
    }

    inputNegras.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        if (nombreArchivoNegras)
          nombreArchivoNegras.textContent = this.files[0].name;
        mostrarPrevisualizacionAvatar(this, "avatar-display-negras");
      }
    });
  }
});

// ========================================
// FUNCIONES PARA GESTIÓN DE MODALES
// ========================================

// Función para cerrar un modal por su ID
function cerrarModal(modalId) {
  // Obtenemos el modal por su ID
  const modal = document.getElementById(modalId);
  if (modal) {
    // Lo ocultamos cambiando su display a none
    modal.style.display = "none";
  }
}

// Función genérica para abrir diferentes modales de confirmación
// Soporta: eliminar, reiniciar, cargar
function abrirModalConfirmacion(tipo, opciones = {}) {
  // Variables para los diferentes tipos de modal
  let titulo = "";
  let icono = "";
  let mensaje = "";
  let bottonClass = "";
  let accionHTML = "";
  let modeloId = "modalConfirmacion";

  // Configuramos el contenido según el tipo de modal
  if (tipo === "eliminar") {
    // Modal para confirmar eliminación de partida guardada
    titulo = "⚠️ Confirmar eliminación";
    icono = "🗑️";
    mensaje = `¿Deseas eliminar la partida "<strong>${opciones.nombre}</strong>"?`;
    bottonClass = "btn-eliminar";
    const desdeInicio = opciones.desdeInicio ? true : false;
    const nombreAction = desdeInicio
      ? "eliminar_partida_inicial"
      : "eliminar_partida";
    accionHTML = `
      <form method="post" style="display: inline;">
        <input type="hidden" name="archivo_partida" value="${opciones.archivo}">
        <button type="submit" name="${nombreAction}" class="btn-confirmar ${bottonClass}">${icono} Eliminar</button>
      </form>
    `;
    modeloId = "modalConfirmarEliminar";
  } else if (tipo === "reiniciar") {
    // Modal para confirmar reinicio de partida
    titulo = "🔄 Confirmar reinicio";
    icono = "🔄";
    mensaje = "¿Deseas reiniciar la partida? Perderás todo el progreso.";
    bottonClass = "btn-reiniciar-confirm";
    accionHTML = `
      <form method="post" style="display: inline;">
        <button type="submit" name="confirmar_reiniciar" class="btn-confirmar ${bottonClass}">${icono} Reiniciar</button>
      </form>
    `;
    modeloId = "modalConfirmarReiniciar";
  } else if (tipo === "cargar") {
    // Modal para confirmar carga de partida guardada
    titulo = "📁 Confirmar carga";
    icono = "📁";
    mensaje =
      "Si cargas una partida guardada, la actual se perderá. ¿Deseas continuar?";
    bottonClass = "btn-cargar-confirm";
    accionHTML = `
      <form method="post" style="display: inline;">
        <input type="hidden" name="archivo_partida" value="${opciones.archivo}">
        <button type="submit" name="cargar_partida" class="btn-confirmar ${bottonClass}">${icono} Cargar</button>
      </form>
    `;
    modeloId = "modalConfirmarCargar";
  }

  // Creamos el HTML del modal con los valores configurados
  const modalHTML = `
    <div id="${modeloId}" class="modal-overlay">
      <div class="modal-content">
        <h2>${titulo}</h2>
        <p>${mensaje}</p>
        <p class="texto-advertencia">Esta acción no se puede deshacer.</p>
        <div class="modal-buttons">
          ${accionHTML}
          <button type="button" class="btn-cancelar" onclick="cerrarModal('${modeloId}')">✖️ Cancelar</button>
        </div>
      </div>
    </div>
  `;

  // Eliminamos el modal anterior si existe
  const modalAnterior = document.getElementById(modeloId);
  if (modalAnterior) {
    modalAnterior.remove();
  }

  // Añadimos el nuevo modal al DOM
  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Mostramos el modal
  const newModal = document.getElementById(modeloId);
  if (newModal) {
    newModal.style.display = "flex";
  }

  // Si es un reinicio, pausamos la partida automáticamente
  if (tipo === "reiniciar") {
    // Pausamos con AJAX sin recargar la página
    fetch("index.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "alternar_pausa=1",
    });
  }
}

// Función para mantener compatibilidad con el código existente (eliminar partida)
function abrirModalConfirmarEliminar(nombre, archivo, desdeInicio) {
  abrirModalConfirmacion("eliminar", { nombre, archivo, desdeInicio });
}

// Función para abrir el modal de cargar partida desde la pantalla inicial
function abrirModalCargarInicial() {
  // Obtenemos el modal
  const modal = document.getElementById("modalCargarInicial");
  if (modal) {
    // Lo mostramos
    modal.style.display = "flex";
  }
}

// ========================================
// FUNCIONES PARA EXPANDIR/CONTRAER SECCIONES
// ========================================

// Función para mostrar/ocultar el historial de movimientos
function toggleHistorial() {
  // Obtenemos el contenedor del historial y el icono de toggle
  const contenido = document.getElementById("historial-contenido");
  const toggle = document.getElementById("historial-toggle");

  if (contenido && toggle) {
    if (contenido.style.display === "none") {
      // Si está oculto, lo mostramos
      contenido.style.display = "block";
      // Rotamos el icono hacia abajo
      toggle.style.transform = "rotate(180deg)";
    } else {
      // Si está visible, lo ocultamos
      contenido.style.display = "none";
      // Volvemos el icono a la posición normal
      toggle.style.transform = "rotate(0deg)";
    }
  }
}

// Función para mostrar/ocultar las instrucciones y reglas
function toggleInstrucciones() {
  // Obtenemos el contenedor de instrucciones y el icono de toggle
  const contenido = document.getElementById("instrucciones-contenido");
  const toggle = document.getElementById("instrucciones-toggle");

  if (contenido && toggle) {
    if (contenido.style.display === "none") {
      // Si está oculto, lo mostramos
      contenido.style.display = "block";
      // Rotamos el icono hacia abajo
      toggle.style.transform = "rotate(180deg)";
    } else {
      // Si está visible, lo ocultamos
      contenido.style.display = "none";
      // Volvemos el icono a la posición normal
      toggle.style.transform = "rotate(0deg)";
    }
  }
}
