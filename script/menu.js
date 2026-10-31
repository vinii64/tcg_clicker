/* Menú lateral para celulares.
   Toma los links que ya están dentro de <nav> y los mueve a un panel que se desliza desde la izquierda.
   En escritorio no cambia nada (el CSS deja los links donde estaban). */
(function () {
    var nav = document.querySelector('nav');
    if (!nav || nav.classList.contains('nav-js')) return;

    var links = Array.prototype.filter.call(nav.children, function (el) {
        return el.tagName === 'A';
    });

    /* título de la barra = página actual */
    var archivo = location.pathname.split('/').pop() || 'index.php';
    var titulo = 'Menú';
    links.forEach(function (a) {
        var destino = (a.getAttribute('href') || '').split('?')[0];
        if (destino === archivo) {
            a.classList.add('actual');
            titulo = (a.childNodes[0] ? a.childNodes[0].textContent : a.textContent).trim() || titulo;
        }
    });

    /* botón hamburguesa */
    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'nav-toggle';
    toggle.setAttribute('aria-label', 'Abrir menú');
    toggle.setAttribute('aria-controls', 'nav-menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span></span><span></span><span></span>';
    if (nav.querySelector('.badge')) toggle.classList.add('tiene-aviso');

    var tituloEl = document.createElement('span');
    tituloEl.className = 'nav-titulo';
    tituloEl.textContent = titulo;

    var overlay = document.createElement('div');
    overlay.className = 'nav-overlay';

    /* panel con los links */
    var menu = document.createElement('div');
    menu.className = 'nav-menu';
    menu.id = 'nav-menu';

    var head = document.createElement('div');
    head.className = 'nav-menu-head';
    head.innerHTML = '<span>Menú</span>';
    var cerrar = document.createElement('button');
    cerrar.type = 'button';
    cerrar.className = 'nav-cerrar';
    cerrar.setAttribute('aria-label', 'Cerrar menú');
    cerrar.innerHTML = '&times;';
    head.appendChild(cerrar);
    menu.appendChild(head);

    links.forEach(function (a) { menu.appendChild(a); });

    nav.appendChild(toggle);
    nav.appendChild(tituloEl);
    nav.appendChild(overlay);
    nav.appendChild(menu);
    nav.classList.add('nav-js');

    function abrir() {
        nav.classList.add('abierto');
        document.body.classList.add('menu-abierto');
        toggle.setAttribute('aria-expanded', 'true');
    }
    function cerrarMenu() {
        nav.classList.remove('abierto');
        document.body.classList.remove('menu-abierto');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', abrir);
    cerrar.addEventListener('click', cerrarMenu);
    overlay.addEventListener('click', cerrarMenu);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrarMenu();
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 640) cerrarMenu();
    });
})();
