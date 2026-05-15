
let clicks = 0;

function alertaPuntos(){
    alert("Puntos insuficientes");
    return;
}

function contar() {
    clicks++;
    actualizarContador();
    sumarClick();
}

function sumarClick() {
    fetch("sumar_click.php", {
        method: "POST"
    })
    .then(res => res.text())
    .then(data => console.log(data))
    .catch(err => console.error(err));
}
/*
function comprar() {
    if (clicks < 10) {
        alert("Puntos insuficientes");
        return;
    }

    clicks -= 10;
    actualizarContador();

    const contenedor = document.getElementById("contenedor");
    contenedor.innerHTML = "";

    const carta = document.createElement("div");
    carta.id = "carta"; 

    const imagen = document.createElement("img");
    const cartaNum = Math.floor(Math.random() * 6);
    imagen.src = `cartas/${cartaNum}.png`;

    const brillo = document.createElement("div");
    brillo.className = "glare";

    carta.appendChild(imagen);
    carta.appendChild(brillo);
    contenedor.appendChild(carta);

    aplicarEfectoCarta(carta, brillo);
}
*/





/*
function crearCarta() {

}
*/


function aplicarEfectoCarta(card, brillo) {
    card.addEventListener("mousemove", (e) => {
        const rect = card.getBoundingClientRect();

        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const rotateX = -(y - rect.height / 2) / 10;
        const rotateY = (x - rect.width / 2) / 10;

        card.style.transform = `perspective(600px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;

        const percentX = (x / rect.width) * 100;
        const percentY = (y / rect.height) * 100;

        brillo.style.background = `radial-gradient(circle at ${percentX}% ${percentY}%, rgba(255,255,255,0.5), transparent 60%)`;
        brillo.style.opacity = "1";
    });

    card.addEventListener("mouseleave", () => {
        card.style.transform = "perspective(600px) rotateX(0) rotateY(0)";
        brillo.style.opacity = "0";
    });
}

/*
function comprar()
{
    if (clicks < 10)
    {
     alert("Puntos insuficientes");
        return;
    }
    clicks -= 10;
    actualizarContador();
    crearCarta();

    aplicarEfectoCarta(carta, brillo);
}
*/


