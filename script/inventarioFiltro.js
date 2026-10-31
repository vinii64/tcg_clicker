/* Filtro por edición del inventario (Todas / Normal / Brillante / ...).
   Solo muestra u oculta cartas que ya están en la página: no hace falta recargar. */
(function () {
    var chips  = document.querySelectorAll('.ed-chip');
    var cartas = document.querySelectorAll('.inventario .carta[data-ed]');
    var vacio  = document.getElementById('ed-vacio');
    if (!chips.length) return;

    function filtrar(ed) {
        var visibles = 0;
        cartas.forEach(function (c) {
            var mostrar = (ed === 'todas' || c.dataset.ed === ed);
            c.hidden = !mostrar;
            if (mostrar) visibles++;
        });
        chips.forEach(function (ch) {
            ch.classList.toggle('activo', ch.dataset.ed === ed);
        });
        if (vacio) vacio.hidden = visibles > 0;
    }

    chips.forEach(function (ch) {
        ch.addEventListener('click', function () {
            if (!ch.disabled) filtrar(ch.dataset.ed);
        });
    });
})();
