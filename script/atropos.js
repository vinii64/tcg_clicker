  document.querySelectorAll('.mi-carta-tcg').forEach(elemento => {
            const index = elemento.getAttribute('data-index');
            
            Atropos({
                el: elemento,
                activeOffset: 40,
                shadowScale: 1.05,
                onRotate(x, y) {
                    const xPercentage = ((x + 20) / 40) * 100;
                    const yPercentage = ((y + 20) / 40) * 100;
                    
                    const brillo = document.getElementById(`brillo-${index}`);
                    if(brillo) {
                        brillo.style.backgroundPosition = `${xPercentage}% ${yPercentage}%`;
                    }
                }
            });
        });