// Modal de configuración
const modal = document.getElementById("modalConfig");
const btnConfig = document.getElementById("btnConfig");
const closeModal = document.querySelector(".close-modal");
const btnCancelar = document.querySelector(".btn-cancelar-config");

if (modal && btnConfig && closeModal && btnCancelar) {
  btnConfig.onclick = () => (modal.style.display = "block");
  closeModal.onclick = () => (modal.style.display = "none");
  btnCancelar.onclick = () => (modal.style.display = "none");
  window.onclick = (e) => {
    if (e.target == modal) modal.style.display = "none";
  };
}

// Actualizar relojes con contador local y sincronización periódica
let intervaloRelojes = null;
let tiempoLocalBlancas = 0;
let tiempoLocalNegras = 0;
let relojActivoLocal = "blancas";
let pausaLocal = false;
let contadorSincronizacion = 0;
let recargandoPagina = false; // Flag para evitar múltiples recargas

// Función para formatear tiempo
function formatearTiempo(segundos) {
  return (
    String(Math.floor(segundos / 60)).padStart(2, "0") +
    ":" +
    String(segundos % 60).padStart(2, "0")
  );
}

// Actualizar display del reloj en el DOM
function actualizarDisplayRelojes() {
  const tb = document.getElementById("tiempo-blancas");
  const tn = document.getElementById("tiempo-negras");

  if (!tb || !tn) return;

  tb.textContent = formatearTiempo(tiempoLocalBlancas);
  tn.textContent = formatearTiempo(tiempoLocalNegras);

  // Aplicar clase de tiempo crítico SOLO al reloj activo
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

  // Actualizar reloj activo/inactivo
  document.querySelectorAll(".reloj").forEach((r) => {
    if (r.classList.contains("reloj-blancas")) {
      relojActivoLocal === "blancas"
        ? (r.classList.add("reloj-activo"),
          r.classList.remove("reloj-inactivo"))
        : (r.classList.remove("reloj-activo"),
          r.classList.add("reloj-inactivo"));
    } else if (r.classList.contains("reloj-negras")) {
      relojActivoLocal === "negras"
        ? (r.classList.add("reloj-activo"),
          r.classList.remove("reloj-inactivo"))
        : (r.classList.remove("reloj-activo"),
          r.classList.add("reloj-inactivo"));
    }
  });
}

// Decrementar tiempo local cada segundo
function actualizarTiempoLocal() {
  // Si ya estamos recargando, no hacer nada
  if (recargandoPagina) return;

  if (
    !document.getElementById("tiempo-blancas") ||
    !document.getElementById("tiempo-negras")
  ) {
    return;
  }

  // Si no está en pausa, decrementar el reloj activo
  if (!pausaLocal) {
    if (relojActivoLocal === "blancas" && tiempoLocalBlancas > 0) {
      tiempoLocalBlancas--;
    } else if (relojActivoLocal === "negras" && tiempoLocalNegras > 0) {
      tiempoLocalNegras--;
    }

    // Verificar si se agotó el tiempo - solo recargar UNA VEZ
    if (
      (tiempoLocalBlancas <= 0 || tiempoLocalNegras <= 0) &&
      !recargandoPagina
    ) {
      recargandoPagina = true;
      clearInterval(intervaloRelojes);
      intervaloRelojes = null;
      // Recargar sin timeout para evitar que se ejecute otra vez
      location.reload();
      return;
    }
  }

  actualizarDisplayRelojes();

  // Sincronizar con el servidor cada 5 segundos
  contadorSincronizacion++;
  if (contadorSincronizacion >= 5) {
    sincronizarConServidor();
    contadorSincronizacion = 0;
  }
}

// Sincronizar con el servidor
function sincronizarConServidor() {
  // Si ya estamos recargando, no sincronizar
  if (recargandoPagina) return;

  fetch("index.php?ajax=update_clocks")
    .then((r) => {
      if (!r.ok) throw new Error("Error en respuesta HTTP: " + r.status);
      return r.json();
    })
    .then((data) => {
      // Si ya estamos recargando, ignorar datos
      if (recargandoPagina) return;

      if (data.sin_partida) return;

      // Si la partida terminó, detener todo y no recargar
      if (data.partida_terminada) {
        if (intervaloRelojes !== null) {
          clearInterval(intervaloRelojes);
          intervaloRelojes = null;
        }
        return;
      }

      if (
        data.tiempo_blancas !== undefined &&
        data.tiempo_negras !== undefined
      ) {
        // Actualizar variables locales con datos del servidor
        tiempoLocalBlancas = data.tiempo_blancas;
        tiempoLocalNegras = data.tiempo_negras;
        relojActivoLocal = data.reloj_activo;
        pausaLocal = data.pausa || false;

        // DEBUG: Mostrar estado de pausa
        if (data.pausa) {
          console.warn(
            "⚠️ LA PARTIDA ESTÁ EN PAUSA - Los movimientos están bloqueados"
          );
        }

        actualizarDisplayRelojes();

        // Verificar tiempo agotado solo si no estamos ya recargando
        if (
          (data.tiempo_blancas <= 0 || data.tiempo_negras <= 0) &&
          !recargandoPagina
        ) {
          recargandoPagina = true;
          clearInterval(intervaloRelojes);
          intervaloRelojes = null;
          location.reload();
        }
      }
    })
    .catch((e) => {
      console.error("Error al sincronizar relojes:", e);
    });
}

// Manejar selección de avatar personalizado
document.addEventListener("DOMContentLoaded", function () {
  // Iniciar intervalo de actualización de relojes cuando el DOM está listo
  if (!intervaloRelojes && document.getElementById("tiempo-blancas")) {
    // Primero sincronizar para verificar si la partida está terminada
    fetch("index.php?ajax=update_clocks")
      .then((r) => r.json())
      .then((data) => {
        if (data.sin_partida || data.partida_terminada) {
          // No iniciar relojes si no hay partida o ya terminó
          return;
        }
        // Si hay partida activa, inicializar variables y relojes
        tiempoLocalBlancas = data.tiempo_blancas;
        tiempoLocalNegras = data.tiempo_negras;
        relojActivoLocal = data.reloj_activo;
        pausaLocal = data.pausa || false;
        actualizarDisplayRelojes();
        // Iniciar intervalo
        intervaloRelojes = setInterval(actualizarTiempoLocal, 1000);
      })
      .catch((e) => console.error("Error al inicializar relojes:", e));
  }

  const selectBlancas = document.querySelector('select[name="avatar_blancas"]');
  const selectNegras = document.querySelector('select[name="avatar_negras"]');
  const inputBlancas = document.getElementById("avatar_custom_blancas");
  const inputNegras = document.getElementById("avatar_custom_negras");

  // Función para validar archivo
  function validarArchivo(file) {
    const allowedTypes = ["image/jpeg", "image/png", "image/gif"];
    const maxSize = 2 * 1024 * 1024; // 2MB

    if (!allowedTypes.includes(file.type)) {
      alert("Solo se permiten archivos de imagen (JPEG, PNG, GIF)");
      return false;
    }

    if (file.size > maxSize) {
      alert("El archivo es demasiado grande. Máximo 2MB.");
      return false;
    }

    return true;
  }

  // Previsualización de imagen
  function mostrarPrevisualizacion(input, previewId) {
    const file = input.files[0];
    if (file && validarArchivo(file)) {
      const reader = new FileReader();
      reader.onload = function (e) {
        let preview = document.getElementById(previewId);
        if (!preview) {
          preview = document.createElement("img");
          preview.id = previewId;
          preview.className = "avatar-preview";
          preview.style.maxWidth = "120px";
          preview.style.maxHeight = "120px";
          preview.style.borderRadius = "50%";
          preview.style.marginLeft = "10px";
          preview.style.border = "3px solid #5568d3";
          preview.style.boxShadow = "0 4px 10px rgba(85, 104, 211, 0.3)";
          input.parentNode.appendChild(preview);
        }
        preview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }

  if (selectBlancas && inputBlancas) {
    selectBlancas.addEventListener("change", function () {
      if (this.value === "custom") {
        inputBlancas.style.display = "block";
        inputBlancas.addEventListener("change", function () {
          mostrarPrevisualizacion(this, "preview_blancas");
        });
      } else {
        inputBlancas.style.display = "none";
        const preview = document.getElementById("preview_blancas");
        if (preview) preview.remove();
      }
    });
  }

  if (selectNegras && inputNegras) {
    selectNegras.addEventListener("change", function () {
      if (this.value === "custom") {
        inputNegras.style.display = "block";
        inputNegras.addEventListener("change", function () {
          mostrarPrevisualizacion(this, "preview_negras");
        });
      } else {
        inputNegras.style.display = "none";
        const preview = document.getElementById("preview_negras");
        if (preview) preview.remove();
      }
    });
  }
});

// Funciones para modales
function cerrarModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
  }
}

// Función genérica para mostrar modales de confirmación
function abrirModalConfirmacion(tipo, opciones = {}) {
  let titulo = "";
  let icono = "";
  let mensaje = "";
  let bottonClass = "";
  let accionHTML = "";
  let modeloId = "modalConfirmacion";

  if (tipo === "eliminar") {
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

  // Crear el HTML del modal
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

  // Eliminar modal anterior si existe
  const modalAnterior = document.getElementById(modeloId);
  if (modalAnterior) {
    modalAnterior.remove();
  }

  // Agregar modal al DOM
  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Mostrar modal
  const newModal = document.getElementById(modeloId);
  if (newModal) {
    newModal.style.display = "flex";
  }

  // Si es reiniciar, pausar la partida automáticamente sin recargar
  if (tipo === "reiniciar") {
    // Pausar con AJAX sin recargar la página
    fetch("index.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "toggle_pausa=1",
    });
  }
}

// Mantener compatibilidad con función anterior
function abrirModalConfirmarEliminar(nombre, archivo, desdeInicio) {
  abrirModalConfirmacion("eliminar", { nombre, archivo, desdeInicio });
}

// Función para abrir modal cargar desde pantalla inicial
function abrirModalCargarInicial() {
  const modal = document.getElementById("modalCargarInicial");
  if (modal) {
    modal.style.display = "flex";
  }
}

// Función para desplegar/contraer las instrucciones
function toggleInstrucciones() {
  const contenido = document.getElementById("instrucciones-contenido");
  const toggle = document.getElementById("instrucciones-toggle");

  if (contenido && toggle) {
    if (contenido.style.display === "none") {
      contenido.style.display = "block";
      toggle.style.transform = "rotate(180deg)";
    } else {
      contenido.style.display = "none";
      toggle.style.transform = "rotate(0deg)";
    }
  }
}
