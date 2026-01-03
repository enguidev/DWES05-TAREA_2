// Modal de configuración
const modal = document.getElementById("modalConfig");
const btnConfig = document.getElementById("btnConfig");
const closeModal = document.querySelector(".close-modal");
const btnCancelar = document.querySelector(".btn-cancelar-config");

btnConfig.onclick = () => (modal.style.display = "block");
closeModal.onclick = () => (modal.style.display = "none");
btnCancelar.onclick = () => (modal.style.display = "none");
window.onclick = (e) => {
  if (e.target == modal) modal.style.display = "none";
};

// Actualizar relojes con AJAX
function actualizarRelojes() {
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
