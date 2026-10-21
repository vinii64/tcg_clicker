# tcg_clicker
TCG y Clicker sin G y con un poco de clicker.


1- tcg? / clicker

2- clicks = moneda

3- distintos packs y calidades de cartas

4- efectos visuales de cartas

5- tienda

6- sesiones de admin y usuario

7- base de datos:
- cartas
- usuarios
- inventario de usuario

8- (?) boton info

9- debug mode

10- MEGA GOD PACK

11- cartas alternas

12- biblioteca de cartas

13- sets / ediciones de cartas












##### RAREZAS #####
- 1 comun
- 2 especial
- 3 raro
- 4 epico
- 5 legendario
- 6 mitico
- 7 divino

comun
Foil → 76–85 (10%)
Holo → 86–92 (7%)
Polychrome → 93–97 (5%)
Negative → 98–100 (3%)

https://uiverse.io/dylanharriscameron/tiny-shrimp-95 efecto 3d




## Intercambios entre usuarios
1. Importar `sql/intercambios.sql` en la base `tcgdb` (crea `intercambios` e `intercambio_cartas`).
2. Archivos nuevos: `intercambios.php` (recibidas / enviadas / historial / elegir usuario), `proponer.php` (armar propuesta),
   `trade_lib.php` (lógica), `carta_img.php` (imagen de una carta), `nav.php` (navbar con aviso de propuestas pendientes).
3. Flujo: A elige usuario -> marca cartas que ofrece y cartas que pide -> B acepta / rechaza, A puede cancelar.
   Al aceptar se revalidan los inventarios de ambos y el canje se hace en una transacción (todo o nada).
