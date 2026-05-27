// Delegación de eventos para capturar el botón de comprar infinitamente
document.addEventListener('click', function(event) {
    if (event.target && event.target.id === 'comprar') {
        comprarPaqueteDeCartas();
    }
});

// Función principal que conecta con PHP
async function comprarPaqueteDeCartas() {
    try {
        const response = await fetch("comprarPaquete.php");
        const data = await response.json();

        if (data.status === 'success') {
            // 1. Actualizamos el contador de puntos en el HTML
            document.querySelector('.caja').innerHTML = data.html_puntos;

            // 2. Capturamos el contenedor de las cartas
            const contenedorCartas = document.getElementById('contenedor');
            contenedorCartas.className = "grid-tcg"; 
            
            // 3. Inyectamos la estructura de la barra de carga inicializada en 0%
            // Inyectamos la estructura visual de la barra de progreso (Optimizada para fondo blanco)
            contenedorCartas.innerHTML = `
                <div id="loading-container" style="width: 100%; max-width: 400px; margin: 40px auto; text-align: center; font-family: sans-serif;">
        
                 <!-- Texto con color oscuro y sombra suave para fondo claro -->
                 <p id="loading-text" style="color: #222; margin-bottom: 12px; font-size: 14px; font-weight: 600; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.15); letter-spacing: 0.5px;">
                     Abriendo paquete... 0%
                 </p>
        
                <!-- Contenedor de la barra -->
                <div style="width: 100%; height: 12px; background-color: #eaeaea; border-radius: 6px; overflow: hidden; border: 1px solid #ddd; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                 <div id="loading-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #ff0055, #5500ff); transition: width 0.3s ease;"></div>
                 </div>
        
                </div>`;

            // Variables para rastrear los elementos de carga
            const loadingBar = document.getElementById('loading-bar');
            const loadingText = document.getElementById('loading-text');

            // 4. Solicitamos las cartas una a una actualizando la barra de carga
            let cartasHTML = "";
            
            // Carta 1
            cartasHTML += await obtenerCartaWikipedia(0);
            loadingBar.style.width = "33%";
            loadingText.innerText = "Obteniendo primera carta... 33%";

            // Carta 2
            cartasHTML += await obtenerCartaWikipedia(1);
            loadingBar.style.width = "66%";
            loadingText.innerText = "Desbloqueando rarezas... 66%";

            // Carta 3
            cartasHTML += await obtenerCartaWikipedia(2);
            loadingBar.style.width = "100%";
            loadingText.innerText = "¡Paquete listo! 100%";

            // Un pequeño retraso de 400ms para que el usuario aprecie el 100% antes del despliegue
            setTimeout(() => {
                // 5. Reemplazamos la barra de carga por las 3 cartas finales
                contenedorCartas.innerHTML = cartasHTML;
                // 6. Encendemos el motor de Atropos
                activarCartasAtropos();
            }, 400);

        } else if (data.status === 'no_points') {
            alert('No tienes puntos suficientes.');
        }
    } catch (error) {
        console.error("Error en la transacción:", error);
    }
}

// Función auxiliar que busca una sola página con imagen en Wikipedia y devuelve su HTML estructurado
async function obtenerCartaWikipedia(index) {
    const url = "https://es.wikipedia.org/w/api.php?action=query&generator=random&prop=pageimages|extracts&piprop=thumbnail|original&pithumbsize=400&exintro&explaintext&exsentences=2&format=json&origin=*";
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        const pages = data.query.pages;
        const pageId = Object.keys(pages)[0];
        const article = pages[pageId];

        // Si el artículo no trae imagen original, volvemos a intentar para esta ranura de carta
        if (!article.original || !article.original.source) {
            return await obtenerCartaWikipedia(index); 
        }

        const titulo = article.title;
        const descripcion = article.extract || "Sin descripción disponible.";
        const imagenUrl = article.original.source;

        // --- SISTEMA DE RAREZAS EN JAVASCRIPT ---
        const rarezaRoll = Math.floor(Math.random() * 100) + 1;
        let nombreRareza = "Común";
        if (rarezaRoll > 50 && rarezaRoll <= 65) nombreRareza = "Especial";
        else if (rarezaRoll > 65 && rarezaRoll <= 75) nombreRareza = "Épico";
        else if (rarezaRoll > 75 && rarezaRoll <= 80) nombreRareza = "Legendario";
        else if (rarezaRoll > 80 && rarezaRoll <= 99) nombreRareza = "Mítico";
        else if (rarezaRoll === 100) nombreRareza = "Divino";

        // --- SISTEMA DE EFECTOS VISUALES ---
        const rollEfecto = Math.floor(Math.random() * 100) + 1;
        let claseEfecto = "normal";
        if (rollEfecto > 75 && rollEfecto <= 85) claseEfecto = "foil";
        else if (rollEfecto > 85 && rollEfecto <= 92) claseEfecto = "holo";
        else if (rollEfecto > 92 && rollEfecto <= 97) claseEfecto = "policromo";
        else if (rollEfecto > 97) claseEfecto = "negativo";

        // Retornamos el String de HTML combinando el formato Atropos y la estructura de títulos/descripción
        return `
<div class='atropos mi-carta-tcg' data-index='${index}'>
    <div class='atropos-scale'>
        <div class='atropos-rotate'>
            <div class='atropos-inner'>
                
                <!-- CUERPO VISUAL DE LA CARTA -->
                <div class='contenedor-visual'>
                    
                    <!-- 1. Imagen de Fondo de Wikipedia (Nivel más bajo) -->
                    <img class='carta-imagen' src='${imagenUrl}' alt='${titulo}'>
                    
                    <!-- 2. Sombreado para que las letras blancas resalten -->
                    <div style='position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, rgba(0,0,0,0.5) 0%, transparent 30%, transparent 60%, rgba(0,0,0,0.85) 100%); z-index: 2; pointer-events: none;'></div>
                    
                    <!-- 3. Elementos de Texto -->
                    <div style='position: absolute; top: 12px; left: 0; width: 100%; padding: 0 15px; box-sizing: border-box; z-index: 3;'>
                        <h3 style='margin: 0; color: #fff; font-family: sans-serif; font-size: 1.1em; font-weight: bold; text-shadow: 1px 1px 4px rgba(0,0,0,0.8); text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>
                            ${titulo}
                        </h3>
                    </div>
                    
                    <div style='position: absolute; bottom: 12px; left: 0; width: 100%; padding: 0 15px; box-sizing: border-box; z-index: 3;'>
                        <p style='margin: 0; font-family: sans-serif; font-size: 11px; color: #e0e0e0; line-height: 1.3; height: 52px; overflow-y: auto; text-shadow: 1px 1px 3px rgba(0,0,0,0.9); text-align: left;'>
                            ${descripcion}
                        </p>
                    </div>

                </div> <!-- Fin de contenedor-visual -->

                <!-- 4. CAPA DE BRILLO (Mover FUERA de contenedor-visual pero DENTRO de atropos-inner) -->
                <!-- Añadimos data-atropos-offset para que flote en 3D sobre el diseño anterior -->
                <div class='capa-brillo ${claseEfecto}' id='brillo-${index}' data-atropos-offset='5'></div>

            </div>
        </div>
    </div>
    
    <!-- PIE DE PÁGINA (Fuera del efecto 3D) -->
    <p style='color: #aaa; text-align: center; margin-top: 10px; font-family: monospace; font-size: 12px;'>
        [ ${nombreRareza} - ${claseEfecto.toUpperCase()} ]
    </p>
</div>`;

    } catch (error) {
        console.error("Error al conectar con Wikipedia, reintentando carta...", error);
        return await obtenerCartaWikipedia(index);
    }
}

// Inicializador de Atropos (Mantiene el efecto holográfico al mover el ratón)
function activarCartasAtropos() {
    document.querySelectorAll('.mi-carta-tcg').forEach(elemento => {
        const index = elemento.getAttribute('data-index');
        
        if (typeof Atropos !== 'undefined') {
            Atropos({
                el: elemento,
                activeOffset: 40,
                shadowScale: 1.05,
                onRotate(x, y) {
                    const xPercentage = ((x + 20) / 40) * 100;
                    const yPercentage = ((y + 20) / 40) * 100;
                    
                    const brillo = document.getElementById(`brillo-${index}`);
                    if (brillo) {
                        brillo.style.backgroundPosition = `${xPercentage}% ${yPercentage}%`;
                    }
                }
            });
        }
    });
}