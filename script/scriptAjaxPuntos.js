document.getElementById("contar").addEventListener("click",function(){
    var ajax = new XMLHttpRequest();
    ajax.open("POST","update.php","true")
    ajax.setRequestHeader("content-type","application/x-www-form-urlencoded");
    ajax.onreadystatechange = function(){
        if(ajax.readyState == 4 && ajax.status == 200)
        {
            document.getElementById("contador").innerHTML = ajax.responseText;
        }
    };
    ajax.send("id=1");
});