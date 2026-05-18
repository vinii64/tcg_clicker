document.addEventListener("click", async function(e){

    if(e.target && e.target.id == "comprar"){

        let contador = parseInt(document.getElementById("contador").textContent);

        if(contador <= 0){

            alert("No tenés puntos suficientes");

            return;
        }

        try {

            // Comprar paquete
            const response = await fetch("comprarPaquete.php");

            const nuevosPuntos = await response.text();

            // Actualizar contador
            document.getElementById("contador").textContent = nuevosPuntos;

            // Obtener carta random
            getRandomWikiCard();

        } catch(error){

            console.error("Error:", error);

        }
    }
});



async function getRandomWikiCard() {

    const url = "https://es.wikipedia.org/w/api.php?action=query&generator=random&prop=pageimages|extracts&piprop=thumbnail|original&pithumbsize=400&exintro&explaintext&exsentences=2&format=json&origin=*";

    try {

        const response = await fetch(url);

        const data = await response.json();

        const pages = data.query.pages;

        const pageId = Object.keys(pages)[0];

        const article = pages[pageId];

        const title = article.title;

        const description = article.extract || "Sin descripción disponible.";

        // Si no tiene imagen, buscar otro artículo
        if (!article.original || !article.original.source) {

            return getRandomWikiCard();
        }

        const imageUrl = article.original.source;

        // Insertar datos
        document.getElementById("card-title").innerText = title;

        document.getElementById("card-desc").innerText = description;

        const imgElement = document.getElementById("card-image");

        imgElement.src = imageUrl;

        imgElement.style.display = "block";

    } catch (error) {

        console.error("Error al obtener datos:", error);
    }
}