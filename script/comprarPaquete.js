document.addEventListener("click", function(e){
    if(e.target && e.target.id == "comprar"){
        let contador = parseInt(document.getElementById("contador").textContent);
        if(contador <= 0){
            alert("No tenés puntos suficientes");
        } else {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.querySelector(".caja").innerHTML = this.responseText;
            }
            };
        xmlhttp.open("GET", "comprarPaquete.php", true);
        xmlhttp.send();
        }
    }
});