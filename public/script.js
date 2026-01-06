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

// Actualizar relojes con AJAX
function actualizarRelojes() {
  // Solo actualizar si los elementos existen (es decir, hay partida activa)
  if (
    !document.getElementById("tiempo-blancas") ||
    !document.getElementById("tiempo-negras")
  ) {
    return;
  }
  fetch("index.php?ajax=update_clocks")
    .then((r) => r.json())
    .then((data) => {
      console.log("Datos recibidos:", data);
      const fmt = (s) =>
        String(Math.floor(s / 60)).padStart(2, "0") +
        ":" +
        String(s % 60).padStart(2, "0");

      // Solo actualizar si los datos son válidos
      if (
        data.tiempo_blancas !== undefined &&
        data.tiempo_negras !== undefined
      ) {
        document.getElementById("tiempo-blancas").textContent = fmt(
          data.tiempo_blancas
        );
        document.getElementById("tiempo-negras").textContent = fmt(
          data.tiempo_negras
        );

        const tb = document.getElementById("tiempo-blancas");
        const tn = document.getElementById("tiempo-negras");

        // Aplicar clase de tiempo crítico
        data.tiempo_blancas < 60
          ? tb.classList.add("tiempo-critico")
          : tb.classList.remove("tiempo-critico");
        data.tiempo_negras < 60
          ? tn.classList.add("tiempo-critico")
          : tn.classList.remove("tiempo-critico");

        // Actualizar reloj activo/inactivo
        document.querySelectorAll(".reloj").forEach((r) => {
          if (r.classList.contains("reloj-blancas")) {
            data.reloj_activo === "blancas"
              ? (r.classList.add("reloj-activo"),
                r.classList.remove("reloj-inactivo"))
              : (r.classList.remove("reloj-activo"),
                r.classList.add("reloj-inactivo"));
          } else if (r.classList.contains("reloj-negras")) {
            data.reloj_activo === "negras"
              ? (r.classList.add("reloj-activo"),
                r.classList.remove("reloj-inactivo"))
              : (r.classList.remove("reloj-activo"),
                r.classList.add("reloj-inactivo"));
          }
        });

        // Verificar si el tiempo se agotó
        if (data.tiempo_blancas <= 0 || data.tiempo_negras <= 0) {
          alert(
            "¡Tiempo agotado para " +
              (data.tiempo_blancas <= 0 ? "blancas" : "negras") +
              "!"
          );
          location.reload();
        }
      }
    })
    .catch((e) => {
      console.error("Error en actualizarRelojes:", e);
    });
}

setInterval(actualizarRelojes, 1000);

// Manejar selección de avatar personalizado
document.addEventListener("DOMContentLoaded", function () {
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
          preview.style.maxWidth = "50px";
          preview.style.maxHeight = "50px";
          preview.style.borderRadius = "50%";
          preview.style.marginLeft = "10px";
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

// Funciones para modales de guardar/cargar partidas
function cerrarModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
  }
}

// Event listeners para botones de guardar/cargar
document.addEventListener("DOMContentLoaded", function () {
  const btnGuardar = document.getElementById("btnGuardar");
  const btnCargar = document.getElementById("btnCargar");

  if (btnGuardar) {
    btnGuardar.addEventListener("click", function () {
      // Crear un formulario oculto y enviarlo
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "abrir_modal_guardar";
      input.value = "1";

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    });
  }

  if (btnCargar) {
    btnCargar.addEventListener("click", function () {
      // Crear un formulario oculto y enviarlo
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "abrir_modal_cargar";
      input.value = "1";

      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    });
  }
});
