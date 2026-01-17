// Funciones del dashboard
function realizarPedido(motoId) {
  if (confirm("¿Estás seguro de que quieres realizar este pedido?")) {
    const data = {
      accion: "realizar_pedido",
      moto_id: motoId,
      cantidad: 1,
    }

    fetch("procesar_pedido.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Pedido realizado correctamente")
          location.reload()
        } else {
          alert("Error: " + data.message)
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        alert("Error al procesar el pedido")
      })
  }
}

function cancelarPedido(pedidoId) {
  if (confirm("¿Estás seguro de que quieres cancelar este pedido?")) {
    const data = {
      accion: "cancelar_pedido",
      pedido_id: pedidoId,
    }

    fetch("procesar_pedido.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Pedido cancelado correctamente")
          location.reload()
        } else {
          alert("Error: " + data.message)
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        alert("Error al cancelar el pedido")
      })
  }
}

// Función para filtrar motos
function filtrarMotos() {
  const filtro = document.getElementById("filtroMarca").value.toLowerCase()
  const motos = document.querySelectorAll(".moto-card")

  motos.forEach((moto) => {
    const marca = moto.querySelector("h3").textContent.toLowerCase()
    if (filtro === "" || marca.includes(filtro)) {
      moto.style.display = "block"
    } else {
      moto.style.display = "none"
    }
  })
}

// Función para ordenar motos por precio
function ordenarPorPrecio() {
  const contenedor = document.querySelector(".motos-grid")
  const motos = Array.from(document.querySelectorAll(".moto-card"))

  motos.sort((a, b) => {
    const precioA = Number.parseFloat(a.querySelector(".moto-price").textContent.replace("$", "").replace(",", ""))
    const precioB = Number.parseFloat(b.querySelector(".moto-price").textContent.replace("$", "").replace(",", ""))
    return precioA - precioB
  })

  motos.forEach((moto) => contenedor.appendChild(moto))
}

// Inicializar funciones cuando se carga la página
document.addEventListener("DOMContentLoaded", () => {
  // Agregar animaciones suaves
  const cards = document.querySelectorAll(".moto-card")
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`
  })

  // Manejar errores de imágenes
  const imagenes = document.querySelectorAll(".moto-image img")
  imagenes.forEach((img) => {
    img.addEventListener("error", function () {
      this.src = "images/default-moto.jpg"
    })
  })
})
