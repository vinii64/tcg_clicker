fetch("usuarios.php")
    .then(respuesta => respuesta.json())
    .then(data => {console.log(data);})
    .catch(error => {console.error("Error:", error);});
     
